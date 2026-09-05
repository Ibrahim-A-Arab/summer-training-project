<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Question
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->select(
            'SELECT * FROM questions'
        );
    }
    public function getById(int $id): ?array
    {
        $questions = $this->db->select(
            'SELECT * FROM questions
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $questions[0] ?? null;
    }

    public function getByCourseId(int $courseId): array
    {
        return $this->db->select(
            'SELECT id, course_id, question_text, question_type
            FROM questions
            WHERE course_id = :course_id
            ORDER BY id',
            ['course_id' => $courseId]
        );
    }

    public function exists(int $id): bool
    {
        return $this->getById($id) !== null;
    }

    public function validate(
        string $questionText,
        string $questionType
    ): array {
        $questionText = trim($questionText);

        if ($questionText === '') {
            return [
                'valid' => false,
                'error' => 'Question is required.'
            ];
        }

        if (!in_array(
            $questionType,
            ['MCQ', 'TrueOrFalse'],
            true
        )) {
            return [
                'valid' => false,
                'error' => 'Select a valid question type.'
            ];
        }

        return [
            'valid' => true,
            'question_text' => $questionText,
            'question_type' => $questionType
        ];
    }


    public function create(
        int $courseId,
        string $questionText,
        string $questionType
    ): int {
        $this->db->execute(
            'INSERT INTO questions (
            course_id,
            question_text,
            question_type
        ) VALUES (
            :course_id,
            :question_text,
            :question_type
        )',
            [
                'course_id' => $courseId,
                'question_text' => $questionText,
                'question_type' => $questionType
            ]
        );

        return $this->db->lastInsertId();
    }

    public function createWithChoices(
        int $courseId,
        string $questionText,
        string $questionType,
        array $choices
    ): int {
        $choiceModel = new Choice();

        try {
            $this->db->beginTransaction();

            $questionId = $this->create(
                $courseId,
                $questionText,
                $questionType
            );

            foreach ($choices as $choice) {
                $choiceModel->create(
                    $questionId,
                    $choice['text'],
                    $choice['is_correct']
                );
            }

            $this->db->commit();

            return $questionId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function update(
        int $id,
        int $courseId,
        string $questionText,
        string $questionType
    ): bool {
        return $this->db->execute(
            'UPDATE questions
            SET course_id = :course_id,
                question_text = :question_text,
                question_type = :question_type
            WHERE id = :id',
            [
                'id' => $id,
                'course_id' => $courseId,
                'question_text' => $questionText,
                'question_type' => $questionType
            ]
        );
    }

    public function updateWithChoices(
        int $id,
        int $courseId,
        string $questionText,
        string $questionType,
        array $choices
    ): bool {
        $choiceModel = new Choice();

        try {
            $this->db->beginTransaction();

            if (!$this->update(
                $id,
                $courseId,
                $questionText,
                $questionType
            )) {
                throw new \RuntimeException('Could not update question.');
            }

            if (!$choiceModel->deleteByQuestionId($id)) {
                throw new \RuntimeException(
                    'Could not replace question choices.'
                );
            }

            foreach ($choices as $choice) {
                $choiceModel->create(
                    $id,
                    $choice['text'],
                    $choice['is_correct']
                );
            }

            $this->db->commit();

            return true;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM questions
            WHERE id = :id',
            ['id' => $id]
        );
    }

    public function isUsedInExam(int $id): bool
    {
        return $this->db->select(
            'SELECT id
            FROM exam_questions
            WHERE question_id = :question_id
            LIMIT 1',
            [
                'question_id' => $id
            ]
        ) !== [];
    }

    public function canModify(int $id): bool
    {
        return !$this->isUsedInExam($id);
    }
}
