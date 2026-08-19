<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Exam
{
    private Database $db;   

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            ORDER BY start_time DESC'
        );
    }

    public function getById(int $id): ?array
    {
        $exams = $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $exams[0] ?? null;
    }

    public function getByCourseId(int $courseId): array// exams of a course
    {
        return $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            WHERE course_id = :course_id
            ORDER BY start_time DESC',
            ['course_id' => $courseId]
        );
    }

    public function getAvailableForStudent(int $studentId): array
    {
        return $this->db->select(
            'SELECT e.id, e.course_id, e.exam_name,
                    e.start_time, e.end_time,
                    e.shuffle_questions
            FROM exams e
            JOIN course_students cs
                ON cs.course_id = e.course_id
            WHERE cs.student_id = :student_id
            AND NOW() BETWEEN e.start_time AND e.end_time
            ORDER BY e.end_time',
            ['student_id' => $studentId]
        );
    }

    public function create(
        int $courseId,
        string $name,
        string $startTime,
        string $endTime,
        bool $shuffle
    ): int {
        $this->db->execute(
            'INSERT INTO exams
                (course_id, exam_name, start_time,
                end_time, shuffle_questions)
            VALUES
                (:course_id, :exam_name, :start_time,
                :end_time, :shuffle)',
            [
                'course_id' => $courseId,
                'exam_name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'shuffle' => (int) $shuffle
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $name,
        string $startTime,
        string $endTime,
        bool $shuffle
    ): bool {
        return $this->db->execute(
            'UPDATE exams
            SET exam_name = :exam_name,
                start_time = :start_time,
                end_time = :end_time,
                shuffle_questions = :shuffle
            WHERE id = :id',
            [
                'id' => $id,
                'exam_name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'shuffle' => (int) $shuffle
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM exams WHERE id = :id',
            ['id' => $id]
        );
    }
}