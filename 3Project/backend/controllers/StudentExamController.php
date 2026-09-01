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
    public function show(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $studentId = (int) $_SESSION['user_id'];
        $examId = (int) $examId;

        $exam = (new Exam())->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $courseId = (int) $exam['course_id'];

        $resultModel = new ExamResult();

        if ($resultModel->hasSubmitted($examId, $studentId)) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $isEnrolled = (new CourseStudent())
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

        $questions = (new ExamQuestion())
            ->getQuestionsByExam($examId);

        $choiceModel = new Choice();

        foreach ($questions as &$question) {
            $question['choices'] = $choiceModel
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

        $exam = (new Exam())->getById($examId);

        if ($exam === null) {
            http_response_code(404);
            exit;
        }

        $courseId = (int) $exam['course_id'];

        if (!(new CourseStudent())->isEnrolled($courseId, $studentId)) {
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

        $resultModel = new ExamResult();

        // One result/attempt per student and exam.
        if ($resultModel->getByExamAndStudent($examId, $studentId) !== null) {
            http_response_code(403);
            exit;
        }

        $submittedAnswers = $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $questions = (new ExamQuestion())
            ->getQuestionsByExam($examId);

        $database = Database::getInstance();
        $answerModel = new StudentAnswer();

        try {
            $database->beginTransaction();

            $resultId = $resultModel->create($examId, $studentId);

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
                        $answerModel->create(
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
                    $answerModel->isCorrect(
                        $resultId,
                        (int) $question['id']
                    )
                ) {
                    $mark += (float) $question['weight'];
                }
            }

            if (!$resultModel->submit($resultId, $mark)) {
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