<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Choice;
use App\Models\Course;
use App\Models\Question;
use App\Utils\Database;
use App\Utils\ViewModel;




class QuestionController
{
    public function getAll(): ViewModel
    {

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

    public function getById(string $id): ViewModel
    {
        $questionId = (int) $id;

        $questionModel = new Question();
        $choiceModel = new Choice();

        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null,
                'choices' => []
            ]);
        }

        $choices = $choiceModel->getByQuestionId(
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
        $questionType = $_POST['question_type'] ?? '';
        $courseId = (int) $courseId;
        $questionText = trim($_POST['question'] ?? '');
        $submittedChoices = $_POST['choices'] ?? [];

        $courseModel = new Course();

        // Confirm that the course exists.
        if (!$courseModel->exists($courseId)) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        // Validate the question text.
        if ($questionText === '') {
            http_response_code(422);

            return new ViewModel('questions/create', [
                'error' => 'Question is required.',
                'courseId' => $courseId,
                'oldQuestion' => $questionText,
                'oldChoices' => $submittedChoices
            ]);
        }

        $choices = [];

        if ($questionType === 'TrueOrFalse') {
            $correctAnswer = $_POST['correct_answer'] ?? null;

            if (!in_array($correctAnswer, ['true', 'false'], true)) {
                http_response_code(422);

                return new ViewModel('questions/create', [
                    'error' => 'Select True or False.',
                    'courseId' => $courseId,
                    'oldQuestion' => $questionText,
                    'questionType' => $questionType,
                    'correctAnswer' => $correctAnswer
                ]);
            }

            $choices = [
                [
                    'text' => 'True',
                    'is_correct' => $correctAnswer === 'true'
                ],
                [
                    'text' => 'False',
                    'is_correct' => $correctAnswer === 'false'
                ]
            ];
        } elseif ($questionType === 'MCQ') {
            $submittedChoices = $_POST['choices'] ?? [];

            if (
                !is_array($submittedChoices)
                || count($submittedChoices) < 2
            ) {
                http_response_code(422);

                return new ViewModel('questions/create', [
                    'error' => 'At least two choices are required.',
                    'courseId' => $courseId,
                    'oldQuestion' => $questionText,
                    'oldChoices' => $submittedChoices,
                    'questionType' => $questionType
                ]);
            }

            $hasCorrectChoice = false;

            foreach ($submittedChoices as $choice) {
                if (!is_array($choice)) {
                    http_response_code(422);

                    return new ViewModel('questions/create', [
                        'error' => 'Invalid choice data.',
                        'courseId' => $courseId,
                        'oldQuestion' => $questionText,
                        'oldChoices' => $submittedChoices,
                        'questionType' => $questionType
                    ]);
                }

                $choiceText = trim((string) ($choice['text'] ?? ''));
                $isCorrect = isset($choice['correct']);

                if ($choiceText === '') {
                    http_response_code(422);

                    return new ViewModel('questions/create', [
                        'error' => 'Choice text cannot be empty.',
                        'courseId' => $courseId,
                        'oldQuestion' => $questionText,
                        'oldChoices' => $submittedChoices,
                        'questionType' => $questionType
                    ]);
                }

                if ($isCorrect) {
                    $hasCorrectChoice = true;
                }

                $choices[] = [
                    'text' => $choiceText,
                    'is_correct' => $isCorrect
                ];
            }

            if (!$hasCorrectChoice) {
                http_response_code(422);

                return new ViewModel('questions/create', [
                    'error' => 'Select at least one correct choice.',
                    'courseId' => $courseId,
                    'oldQuestion' => $questionText,
                    'oldChoices' => $submittedChoices,
                    'questionType' => $questionType
                ]);
            }
        } else {
            http_response_code(422);

            return new ViewModel('questions/create', [
                'error' => 'Select a valid question type.',
                'courseId' => $courseId,
                'oldQuestion' => $questionText
            ]);
        }

        $db = Database::getInstance();
        $questionModel = new Question();
        $choiceModel = new Choice();

        $db->beginTransaction();

        try {
            // Save the question and get its new ID.
            $questionId = $questionModel->create(
                $courseId,
                $questionText,
                $questionType
            );

            // Save every choice under the new question.
            foreach ($choices as $choice) {
                $choiceModel->create(
                    $questionId,
                    $choice['text'],
                    $choice['is_correct']
                );
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }

        $this->redirectToQuestions($courseId);
    }

    public function edit(string $id): ViewModel
    {
        $questionId = (int) $id;

        $questionModel = new Question();
        $choiceModel = new Choice();

        $question = $questionModel->getById((int) $id);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/edit', [
                'question' => $question,
                'choices' => []
            ]);
        }



        if ($questionModel->isUsedInExam($questionId)) {
            http_response_code(409);

            return new ViewModel('questions/show', [
                'question' => $question,
                'choices' => $choiceModel->getByQuestionId(
                    $questionId
                ),
                'error' => 'This question cannot be edited because it is used by an exam.'
            ]);
        }

        return new ViewModel('questions/edit', [
            'question' => $question,
            'choices' => $choiceModel->getByQuestionId(
                $questionId
            )
        ]);
    }

    public function update(string $id): ViewModel
    {
        $db = Database::getInstance();

        $questionId = (int) $id;
        $questionType = $_POST['question_type'] ?? '';
        $questionText = trim($_POST['question'] ?? '');

        $questionModel = new Question();
        $choiceModel = new Choice();

        $question = $questionModel->getById($questionId);

        if ($question === null) {
            http_response_code(404);

            return new ViewModel('questions/show', [
                'question' => null,
                'choices' => []
            ]);
        }

        if ($questionModel->isUsedInExam($questionId)) {
            http_response_code(409);

            return new ViewModel('questions/show', [
                'question' => $question,
                'choices' => $choiceModel->getByQuestionId($questionId),
                'error' => 'This question cannot be edited because it is used by an exam.'
            ]);
        }

        $courseId = (int) $question['course_id'];
        $question['question_text'] = $questionText;

        if ($questionText === '') {
            http_response_code(422);

            return new ViewModel('questions/edit', [
                'question' => $question,
                'choices' => $_POST['choices'] ?? [],
                'questionType' => $questionType,
                'correctAnswer' => $_POST['correct_answer'] ?? null,
                'error' => 'Question is required.'
            ]);
        }

        $choices = [];

        if ($questionType === 'TrueOrFalse') {
            $correctAnswer = $_POST['correct_answer'] ?? null;

            if (!in_array($correctAnswer, ['true', 'false'], true)) {
                http_response_code(422);

                return new ViewModel('questions/edit', [
                    'question' => $question,
                    'choices' => [],
                    'questionType' => $questionType,
                    'correctAnswer' => $correctAnswer,
                    'error' => 'Select True or False.'
                ]);
            }

            $choices = [
                [
                    'text' => 'True',
                    'is_correct' => $correctAnswer === 'true'
                ],
                [
                    'text' => 'False',
                    'is_correct' => $correctAnswer === 'false'
                ]
            ];
        } elseif ($questionType === 'MCQ') {
            $submittedChoices = $_POST['choices'] ?? [];

            if (
                !is_array($submittedChoices)
                || count($submittedChoices) < 2
            ) {
                http_response_code(422);

                return new ViewModel('questions/edit', [
                    'question' => $question,
                    'choices' => $submittedChoices,
                    'questionType' => $questionType,
                    'error' => 'At least two choices are required.'
                ]);
            }

            $hasCorrectChoice = false;

            foreach ($submittedChoices as $choice) {
                if (!is_array($choice)) {
                    http_response_code(422);

                    return new ViewModel('questions/edit', [
                        'question' => $question,
                        'choices' => $submittedChoices,
                        'questionType' => $questionType,
                        'error' => 'Invalid choice data.'
                    ]);
                }

                $choiceText = trim(
                    (string) ($choice['text'] ?? '')
                );

                $isCorrect =
                    ($choice['correct'] ?? null) === '1';

                if ($choiceText === '') {
                    http_response_code(422);

                    return new ViewModel('questions/edit', [
                        'question' => $question,
                        'choices' => $submittedChoices,
                        'questionType' => $questionType,
                        'error' => 'Choice text cannot be empty.'
                    ]);
                }

                if ($isCorrect) {
                    $hasCorrectChoice = true;
                }

                $choices[] = [
                    'text' => $choiceText,
                    'is_correct' => $isCorrect
                ];
            }

            if (!$hasCorrectChoice) {
                http_response_code(422);

                return new ViewModel('questions/edit', [
                    'question' => $question,
                    'choices' => $submittedChoices,
                    'questionType' => $questionType,
                    'error' => 'Select at least one correct choice.'
                ]);
            }
        } else {
            http_response_code(422);

            return new ViewModel('questions/edit', [
                'question' => $question,
                'choices' => $_POST['choices'] ?? [],
                'questionType' => $questionType,
                'error' => 'Select a valid question type.'
            ]);
        }

        $db->beginTransaction();

        try {
            $questionModel->update(
                $questionId,
                $courseId,
                $questionText
            );

            $choiceModel->deleteByQuestionId($questionId);

            foreach ($choices as $choice) {
                $choiceModel->create(
                    $questionId,
                    $choice['text'],
                    $choice['is_correct']
                );
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }

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
