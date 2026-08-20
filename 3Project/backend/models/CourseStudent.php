<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class CourseStudent
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function enroll(int $courseId, int $studentId): bool// creating a relation between a student and a course
    {
        return $this->db->execute(
            'INSERT INTO course_students (course_id, student_id)
            VALUES (:course_id, :student_id)',
            [
                'course_id' => $courseId,
                'student_id' => $studentId
            ]
        );
    }

    public function unenroll(int $courseId, int $studentId): bool// removing the relation (nothing is deleted)
    {
        return $this->db->execute(
            'DELETE FROM course_students
            WHERE course_id = :course_id
            AND student_id = :student_id',
            [
                'course_id' => $courseId,
                'student_id' => $studentId
            ]
        );
    }

    public function getStudentsByCourse(int $courseId): array// students of a course
    {
        return $this->db->select(
            'SELECT u.id, u.personal_id, u.name, u.email
            FROM course_students cs
            JOIN users u ON u.id = cs.student_id
            WHERE cs.course_id = :course_id
            ORDER BY u.name',
            ['course_id' => $courseId]
        );
    }

    public function getCoursesByStudent(int $studentId): array// courses are taken by one student
    {
        return $this->db->select(
            'SELECT c.id, c.course_code, c.course_name
            FROM course_students cs
            JOIN courses c ON c.id = cs.course_id
            WHERE cs.student_id = :student_id
            ORDER BY c.course_name',
            ['student_id' => $studentId]
        );
    }

    public function isEnrolled( //check
        int $courseId,
        int $studentId
    ): bool {
        return $this->db->select(
            'SELECT id
            FROM course_students
            WHERE course_id = :course_id
            AND student_id = :student_id
            LIMIT 1',
            [
                'course_id' => $courseId,
                'student_id' => $studentId
            ]
        ) !== [];
    }
}