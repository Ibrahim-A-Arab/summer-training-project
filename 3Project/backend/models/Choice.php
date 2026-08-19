<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Choice
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $choices = $this->db->select(
            'SELECT id, question_id, choice_text, is_correct
            FROM choices
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $choices[0] ?? null;
    }

    public function getByQuestionId(int $questionId): array
    {
        return $this->db->select(
            'SELECT id, question_id, choice_text, is_correct
            FROM choices
            WHERE question_id = :question_id
            ORDER BY id',
            ['question_id' => $questionId]
        );
    }

    public function getCorrectByQuestionId(int $questionId): array
    {
        return $this->db->select(
            'SELECT id, question_id, choice_text
            FROM choices
            WHERE question_id = :question_id
            AND is_correct = 1',
            ['question_id' => $questionId]
        );
    }

    public function create(
        int $questionId,
        string $text,
        bool $isCorrect
    ): int {
        $this->db->execute(
            'INSERT INTO choices
                (question_id, choice_text, is_correct)
            VALUES
                (:question_id, :choice_text, :is_correct)',
            [
                'question_id' => $questionId,
                'choice_text' => $text,
                'is_correct' => (int) $isCorrect
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $text,
        bool $isCorrect
    ): bool {
        return $this->db->execute(
            'UPDATE choices
            SET choice_text = :choice_text,
                is_correct = :is_correct
            WHERE id = :id',
            [
                'id' => $id,
                'choice_text' => $text,
                'is_correct' => (int) $isCorrect
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM choices WHERE id = :id',
            ['id' => $id]
        );
    }

    public function deleteByQuestionId(int $questionId): bool
    {
        return $this->db->execute(
            'DELETE FROM choices WHERE question_id = :question_id',
            ['question_id' => $questionId]
        );
    }
}