<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class CourseTeacher
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function assignTeacher(
        int $courseId,
        int $teacherId
    ): bool {
        return $this->db->execute(
            'INSERT INTO course_teachers (course_id, teacher_id)
            VALUES (:course_id, :teacher_id)',
            [
                'course_id' => $courseId,
                'teacher_id' => $teacherId
            ]
        );
    }

    public function removeTeacher(// remove as a teacher (not deleted) //removes the relation
        int $courseId,
        int $teacherId
    ): bool {
        return $this->db->execute(
            'DELETE FROM course_teachers
            WHERE course_id = :course_id
            AND teacher_id = :teacher_id',
            [
                'course_id' => $courseId,
                'teacher_id' => $teacherId
            ]
        );
    }

    public function getTeachersByCourse(int $courseId): array// teachers of a course
    {
        return $this->db->select(
            'SELECT u.id, u.personal_id, u.name, u.email
            FROM course_teachers ct
            JOIN users u ON u.id = ct.teacher_id
            WHERE ct.course_id = :course_id
            ORDER BY u.name',
            ['course_id' => $courseId]
        );
    }

    public function getCoursesByTeacher(int $teacherId): array// courses of one teacher
    {
        return $this->db->select(
            'SELECT c.id, c.course_code, c.course_name
            FROM course_teachers ct
            JOIN courses c ON c.id = ct.course_id
            WHERE ct.teacher_id = :teacher_id
            ORDER BY c.course_name',
            ['teacher_id' => $teacherId]
        );
    }

    public function isAssigned(
        int $courseId,
        int $teacherId
    ): bool {
        return $this->db->select(
            'SELECT id
            FROM course_teachers
            WHERE course_id = :course_id
            AND teacher_id = :teacher_id
            LIMIT 1',
            [
                'course_id' => $courseId,
                'teacher_id' => $teacherId
            ]
        ) !== [];// checks that the query returned 1 row atleast 
    }
}