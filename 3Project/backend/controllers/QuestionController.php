<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Choice;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Question;
use App\Utils\ViewModel;

class QuestionController
{
    private Question $questionModel;
    private Choice $choiceModel;
    private Course $courseModel;
    private CourseTeacher $courseTeacherModel;

    public function __construct()
    {
        $this->questionModel = new Question();
        $this->choiceModel = new Choice();
        $this->courseModel = new Course();
        $this->courseTeacherModel = new CourseTeacher();
    }

    private const BASE_PATH = '/newSummerTraining/3Project/backend';

    public function getAll(): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

        $questions = $this->questionModel->getAll();


        return new ViewModel('questions/index', [
            'questions' => $questions
        ]);
    }

    public function getByCourse(string $courseId): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

        $courseId = (int) $courseId;
        $teacherId = (int) $_SESSION['user_id'];


        $course = $this->courseModel->getById($courseId);

        if ($course === null) {
            return $this->notFound();
        }

        if (!$this->isAssigned($courseId, $teacherId)) {
            return $this->forbidden();
        }

        $questions = $this->questionModel->getByCourseId($courseId);

        return new ViewModel('questions/index', [
            'course' => $course,
            'courseId' => $courseId,
            'questions' => $questions
        ]);
    }

    public function getById(string $id): ViewModel
    {
        $questionId = (int) $id;

        $question = $this->questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null,
                'choices' => []
            ]);
        }

        if (!$this->canAccessQuestion($question)) {
            return $this->forbidden();
        }

        $choices = $this->choiceModel->getByQuestionId(
            $questionId
        );

        return new ViewModel('questions/show', [
            'question' => $question,
            'choices' => $choices
        ]);
    }

    public function create(string $courseId): ViewModel
    {
        $courseId = (int) $courseId;

        if (!$this->courseModel->exists($courseId)) {
            return $this->notFound();
        }

        if (!$this->canAccessCourse($courseId)) {
            return $this->forbidden();
        }

        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            return new ViewModel('questions/create', [
                'courseId' => $courseId
            ]);
        }

        $questionType = $_POST['question_type'] ?? '';
        $courseId = (int) $courseId;
        $questionText = trim($_POST['question'] ?? '');
        $submittedChoices = $_POST['choices'] ?? [];


        $questionValidation = $this->questionModel->validate(
            $questionText,
            $questionType
        );

        $choiceValidation = $this->choiceModel
            ->validateForQuestionType(
                $questionType,
                $submittedChoices,
                $_POST['correct_answer'] ?? null
            );

        if (
            !$questionValidation['valid']
            || !$choiceValidation['valid']
        ) {
            http_response_code(422);

            return new ViewModel('questions/create', [
                'error' => $questionValidation['error']
                    ?? $choiceValidation['error'],
                'courseId' => $courseId,
                'oldQuestion' => $questionText,
                'oldChoices' => is_array($submittedChoices)
                    ? $submittedChoices
                    : [],
                'questionType' => $questionType,
                'correctAnswer' =>
                    $_POST['correct_answer'] ?? null
            ]);
        }

        $questionText = $questionValidation['question_text'];
        $questionType = $questionValidation['question_type'];
        $choices = $choiceValidation['choices'];

        $this->questionModel->createWithChoices(
            $courseId,
            $questionText,
            $questionType,
            $choices
        );

        $this->redirectToQuestions($courseId);
        
    }

    public function edit(string $id): ViewModel
    {
        $questionId = (int) $id;

        $question = $this->questionModel->getById((int) $id);

        if ($question === null) {
            return $this->notFound();
        }

        if (!$this->canAccessQuestion($question)) {
            return $this->forbidden();
        }



        if (!$this->questionModel->canModify($questionId)) {
            http_response_code(409);

            return new ViewModel('questions/show', [
                'question' => $question,
                'choices' => $this->choiceModel->getByQuestionId(
                    $questionId
                ),
                'error' => 'This question cannot be edited because it is used by an exam.'
            ]);
        }

        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            return new ViewModel('questions/edit', [
                'question' => $question,
                'choices' => $this->choiceModel->getByQuestionId(
                    $questionId
                )
            ]);
        }

        $questionId = (int) $id;
        $questionType = $_POST['question_type'] ?? '';
        $questionText = trim($_POST['question'] ?? '');

        $question = $this->questionModel->getById($questionId);


        $courseId = (int) $question['course_id'];
        $question['question_text'] = $questionText;

        $submittedChoices = $_POST['choices'] ?? [];

        $questionValidation = $this->questionModel->validate(
            $questionText,
            $questionType
        );

        $choiceValidation = $this->choiceModel
            ->validateForQuestionType(
                $questionType,
                $submittedChoices,
                $_POST['correct_answer'] ?? null
            );

        if (
            !$questionValidation['valid']
            || !$choiceValidation['valid']
        ) {
            http_response_code(422);

            return new ViewModel('questions/edit', [
                'question' => $question,
                'choices' => is_array($submittedChoices)
                    ? $submittedChoices
                    : [],
                'questionType' => $questionType,
                'correctAnswer' =>
                    $_POST['correct_answer'] ?? null,
                'error' => $questionValidation['error']
                    ?? $choiceValidation['error']
            ]);
        }

        $questionText = $questionValidation['question_text'];
        $choices = $choiceValidation['choices'];

        $this->questionModel->updateWithChoices(
            $questionId,
            $courseId,
            $questionText,
            $questionType,
            $choices
        );

        $this->redirectToQuestions($courseId);
    }



    public function delete(string $id): ViewModel
    {
        $questionId = (int) $id;

        $question = $this->questionModel->getById($questionId);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null
            ]);
        }

        if (!$this->canAccessQuestion($question)) {
            return $this->forbidden();
        }

        if (!$this->questionModel->canModify($questionId)) {
            http_response_code(409);

            return new ViewModel('questions/show', [
                'question' => $question,
                'choices' => $this->choiceModel
                    ->getByQuestionId($questionId),
                'error' => 'This question cannot be deleted because it is used by an exam.'
            ]);
        }

        $courseId = (int) $question['course_id'];

        $this->questionModel->delete($questionId);

        $this->redirectToQuestions($courseId);
    }

    private function redirectToQuestions(int $courseId): never
    {
        header(
            'Location: '
                . self::BASE_PATH
                . "/api/courses/questions/$courseId",
            true,
            303
        );

        exit;
    }

    private function isTeacher(): bool
    {
        return ($_SESSION['role'] ?? '') === 'teacher';
    }

    private function canAccessCourse(int $courseId): bool
    {
        if (!$this->isTeacher()) {
            return false;
        }

        return $this->isAssigned(
            $courseId,
            (int) ($_SESSION['user_id'] ?? 0)
        );
    }

    private function canAccessQuestion(array $question): bool
    {
        return $this->canAccessCourse(
            (int) $question['course_id']
        );
    }

    private function isAssigned(
        int $courseId,
        int $teacherId
    ): bool {
        return $this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        );
    }

    private function forbidden(): ViewModel
    {
        http_response_code(403);

        return new ViewModel('errors/403');
    }

    private function notFound(): ViewModel
    {
        http_response_code(404);

        return new ViewModel('errors/404');
    }
}
