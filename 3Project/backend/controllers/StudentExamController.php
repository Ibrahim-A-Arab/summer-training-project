<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Choice;
use App\Models\CourseStudent;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Utils\ViewModel;
use App\Models\ExamResult;
use App\Models\StudentAnswer;
use App\Utils\Database;

class StudentExamController
{
    private Exam $examModel;
    private ExamResult $examResultModel;
    private CourseStudent $courseStudentModel;
    private ExamQuestion $examQuestionModel;
    private Choice $choiceModel;
    private StudentAnswer $studentAnswerModel;

    public function __construct()
    {
        $this->examModel = new Exam();
        $this->examResultModel = new ExamResult();
        $this->courseStudentModel = new CourseStudent();
        $this->examQuestionModel = new ExamQuestion();
        $this->choiceModel = new Choice();
        $this->studentAnswerModel = new StudentAnswer();
    }
    public function show(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $studentId = (int) $_SESSION['user_id'];
        $examId = (int) $examId;

        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $courseId = (int) $exam['course_id'];


        if ($this->examResultModel->hasSubmitted($examId, $studentId)) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $isEnrolled = $this->courseStudentModel
            ->isEnrolled($courseId, $studentId);

        if (!$isEnrolled) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $timezone = new \DateTimeZone('Asia/Jerusalem');

        $now = new \DateTimeImmutable('now', $timezone);
        $start = new \DateTimeImmutable(
            $exam['start_time'],
            $timezone
        );
        $end = new \DateTimeImmutable(
            $exam['end_time'],
            $timezone
        );

        if ($now < $start || $now > $end) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);


        foreach ($questions as &$question) {
            $question['choices'] = $this->choiceModel
                ->getByQuestionId((int) $question['id']);
        }

        unset($question);

        if ((bool) $exam['shuffle_questions']) {
            shuffle($questions);
        }

        return new ViewModel('student/exams/show', [
            'exam' => $exam,
            'questions' => $questions
        ]);
    }

    public function submit(string $examId): never
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);
            exit;
        }

        $studentId = (int) $_SESSION['user_id'];
        $examId = (int) $examId;

        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);
            exit;
        }

        $courseId = (int) $exam['course_id'];

        if (!$this->courseStudentModel->isEnrolled($courseId, $studentId)) {
            http_response_code(403);
            exit;
        }

        $timezone = new \DateTimeZone('Asia/Jerusalem');
        $now = new \DateTimeImmutable('now', $timezone);
        $start = new \DateTimeImmutable($exam['start_time'], $timezone);
        $end = new \DateTimeImmutable($exam['end_time'], $timezone);

        if ($now < $start || $now > $end) {
            http_response_code(403);
            exit;
        }


        // One result/attempt per student and exam.
        if ($this->examResultModel->getByExamAndStudent($examId, $studentId) !== null) {
            http_response_code(403);
            exit;
        }

        $submittedAnswers = $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        $database = Database::getInstance();

        try {
            $database->beginTransaction();

            $resultId = $this->examResultModel->create($examId, $studentId);

            foreach ($questions as $question) {
                $questionId = (int) $question['id'];
                $choiceIds = $submittedAnswers[$questionId] ?? [];

                if (!is_array($choiceIds)) {
                    $choiceIds = [$choiceIds];
                }

                $choiceIds = array_unique(
                    array_map('intval', $choiceIds)
                );

                foreach ($choiceIds as $choiceId) {
                    if ($choiceId <= 0) {
                        continue;
                    }

                    if (
                        $this->studentAnswerModel->create(
                            $resultId,
                            $questionId,
                            $choiceId
                        ) === null
                    ) {
                        throw new \RuntimeException(
                            'An invalid answer was submitted.'
                        );
                    }
                }
            }

            $mark = 0.0;

            foreach ($questions as $question) {
                if (
                    $this->studentAnswerModel->isCorrect(
                        $resultId,
                        (int) $question['id']
                    )
                ) {
                    $mark += (float) $question['weight'];
                }
            }

            if (!$this->examResultModel->submit($resultId, $mark)) {
                throw new \RuntimeException(
                    'The exam could not be submitted.'
                );
            }

            $database->commit();
        } catch (\Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            error_log($exception->__toString());
            http_response_code(500);
            exit;
        }

        header(
            'Location: /newSummerTraining/3Project/backend/student/courses/'
                . $courseId,
            true,
            303
        );

        exit;
    }
}