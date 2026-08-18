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

    public function getAll(): array{
        return $this->db->select(
            'SELECT * FROM questions'
        );
    }
    public function getById(int $id): ?array
    {
        $questions = $this->db->select(
            'SELECT * FROM questions
            WHERE question_id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $questions[0] ?? null;
    }

    public function create(int $courseId, string $questionText, float $weight): bool
    {
        return $this->db->execute(
            'INSERT INTO questions (course_id, question_text, weight)
            VALUES (:course_id, :question_text, :weight)',
            [
                'course_id' => $courseId,
                'question_text' => $questionText,
                'weight' => $weight
            ]
        );
    }

    public function update(
        int $id,
        int $courseId,
        string $questionText,
        float $weight
    ): bool
    {
        return $this->db->execute(
            'UPDATE questions
            SET course_id = :course_id,
                question_text = :question_text,
                weight = :weight
            WHERE question_id = :id',
            [
                'id' => $id,
                'course_id' => $courseId,
                'question_text' => $questionText,
                'weight' => $weight
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM questions
            WHERE question_id = :id',
            ['id' => $id]
        );
    }
}
