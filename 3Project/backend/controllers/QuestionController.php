<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Question;
use App\Models\Course;
use App\Utils\ViewModel;


class QuestionController
{
    public function getAll(): ViewModel{

        $questionModel = new Question();
        $questions = $questionModel->getAll();


        return new ViewModel('questions/index', [
            'questions' => $questions
        ]);
    }

    public function getByCourse(string $courseId): ViewModel
    {
        $courseId = (int) $courseId;

        $courseModel = new Course(); //check if the course even exists.
        $course = $courseModel->getById($courseId);

        if ($course === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $questions = (new Question())->getByCourseId($courseId);

        return new ViewModel('questions/index', [
            'questions' => $questions,
            'courseId' => $courseId
        ]);
    }

    public function getById(string $id): ViewModel{
        
        $questionModel = new Question();
        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null
            ]);
        }

        return new ViewModel('questions/show', [
            'question' => $question
        ]);
    }

    public function create(string $courseId): ViewModel
    {
        $courseId = (int) $courseId;

        if (!(new Course())->exists($courseId)) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        return new ViewModel('questions/create', [
            'courseId' => $courseId
        ]);
    }

    public function store(string $courseId): ViewModel
    {
        $courseId = (int) $courseId;
        $questionText = trim($_POST['question'] ?? '');
        $courseModel = new Course();

        if (!$courseModel->exists($courseId)) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        if ($questionText === '') {
            http_response_code(422);

            return new ViewModel('questions/create', [
                'error' => 'Question is required.',
                'courseId' => $courseId,
                'oldQuestion' => $questionText,
            ]);
        }


        $questionModel = new Question();
        $questionModel->create($courseId, $questionText);

        $this->redirectToQuestions($courseId);
    }

    public function edit(string $id): ViewModel
    {
        $questionModel = new Question();
        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);
        }

        return new ViewModel('questions/edit', [
            'question' => $question
        ]);
    }

    public function update(string $id): ViewModel
    {
        $questionModel = new Question();
        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null
            ]);
        }

        $questionText = trim($_POST['question'] ?? '');
        $courseId = (int) $question['course_id'];

        if ($questionText === '') {
            http_response_code(422);

            $question['question_text'] = $questionText;

            return new ViewModel('questions/edit', [
                'question' => $question,
                'error' => 'Question is required.'
            ]);
        }


        $questionModel->update(
            (int) $id,
            $courseId,
            $questionText
        );

        $this->redirectToQuestions($courseId);
    }

    public function delete(string $id): ViewModel
    {
        $questionModel = new Question();
        $questionId = (int) $id;

        $question = $questionModel->getById($questionId);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null
            ]);
        }

        if ($questionModel->isUsedInExam($questionId)) {
            http_response_code(409);

            return new ViewModel('questions/show', [
                'question' => $question,
                'error' => 'This question cannot be deleted because it is used by an exam.'
            ]);
        }

        $courseId = (int) $question['course_id'];

        $questionModel->delete($questionId);

        $this->redirectToQuestions($courseId);
    }

    private function redirectToQuestions(int $courseId): never
    {
        header(
            "Location: /newSummerTraining/3Project/backend/api/courses/$courseId/questions",
            true,
            303
        );

        exit;
    }
}
