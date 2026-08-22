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

    public function getByCourseId(int $courseId): array //all question bank of a course
    {
        return $this->db->select(
            'SELECT id, course_id, question_text
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

    public function update(
        int $id,
        int $courseId,
        string $questionText,
    ): bool {
        return $this->db->execute(
            'UPDATE questions
            SET course_id = :course_id,
                question_text = :question_text
            WHERE id = :id',
            [
                'id' => $id,
                'course_id' => $courseId,
                'question_text' => $questionText
            ]
        );
    }

    public function delete(int $id): bool
    {
        $questionModel = new Question();
        $questionId = (int) $id;

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
}
