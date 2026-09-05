<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Choice;
use App\Models\CourseStudent;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Utils\ViewModel;
use App\Models\ExamResult;

class StudentExamController
{
    private Exam $examModel;
    private ExamResult $examResultModel;
    private CourseStudent $courseStudentModel;
    private ExamQuestion $examQuestionModel;
    private Choice $choiceModel;

    public function __construct()
    {
        $this->examModel = new Exam();
        $this->examResultModel = new ExamResult();
        $this->courseStudentModel = new CourseStudent();
        $this->examQuestionModel = new ExamQuestion();
        $this->choiceModel = new Choice();
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

        if (!$this->examModel->isAvailable($exam)) {
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

        if (!$this->examModel->isAvailable($exam)) {
            http_response_code(403);
            exit;
        }


        // One result/attempt per student and exam.
        if (!$this->examResultModel->canCreateAttempt(
            $examId,
            $studentId
        )) {
            http_response_code(403);
            exit;
        }

        $submittedAnswers = $_POST['answers'] ?? [];

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        try {
            $this->examResultModel->submitAttempt(
                $examId,
                $studentId,
                $questions,
                $submittedAnswers
            );
        } catch (\Throwable $exception) {
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
