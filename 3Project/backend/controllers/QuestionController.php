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
        $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
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
                'oldWeight' => $_POST['weight'] ?? '1.00'
            ]);
        }
        if ($weight === false || $weight === null || $weight <= 0) {
            http_response_code(422);

            return new ViewModel('questions/create', [
                'error' => 'Weight must be greater than zero.',
                'courseId' => $courseId,
                'oldQuestion' => $questionText,
                'oldWeight' => $_POST['weight'] ?? '1.00'
            ]);
        }

        $questionModel = new Question();
        $questionModel->create($courseId, $questionText, $weight);

        $this->redirectToQuestions();
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
        $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
        $courseId = (int) $question['course_id'];

        if ($questionText === '') {
            http_response_code(422);

            $question['question_text'] = $questionText;

            return new ViewModel('questions/edit', [
                'question' => $question,
                'error' => 'Question is required.'
            ]);
        }

        if ($weight === false || $weight === null || $weight <= 0) {
            http_response_code(422);

            $question['question_text'] = $questionText;
            $question['course_id'] = $courseId;
            $question['weight'] = $_POST['weight'] ?? '';

            return new ViewModel('questions/edit', [
                'question' => $question,
                'error' => 'Weight must be greater than zero.'
            ]);
        }

        $questionModel->update((int) $id, $courseId, $questionText, $weight);

        $this->redirectToQuestions();
    }

    public function delete(string $id): ViewModel
    {
        $questionModel = new Question();
        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null
            ]);
        }

        $questionModel->delete((int) $id);

        $this->redirectToQuestions();
    }

    private function redirectToQuestions(): never
    {
        header(
            'Location: /newSummerTraining/3Project/backend/api/questions',
            true,
            303
        );

        exit;
    }
}
