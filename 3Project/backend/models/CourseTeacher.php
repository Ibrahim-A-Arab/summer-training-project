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

    public function assignTeacher(  //all teachers of a course get the same privlages (can make a host or creator special or distinct)
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

    public function getAvailableCourses(
        int $teacherId,
        string $search = ''
    ): array {
        $sql = '
            SELECT c.id, c.course_code, c.course_name
            FROM courses c
            WHERE NOT EXISTS (
                SELECT 1
                FROM course_teachers ct
                WHERE ct.course_id = c.id
                AND ct.teacher_id = :teacher_id
            )
        ';

        $params = [
            'teacher_id' => $teacherId
        ];

        $search = trim($search);

        if ($search !== '') {
            $normalizedCode = preg_replace(
                '/[\s-]+/',
                '',
                strtolower($search)
            );

            $sql .= '
                AND (
                    LOWER(c.course_name) LIKE :name_search
                    OR REPLACE(
                        REPLACE(LOWER(c.course_code), " ", ""),
                        "-",
                        ""
                    ) LIKE :code_search
                )
            ';

            $params['name_search'] =
                '%' . strtolower($search) . '%';

            $params['code_search'] =
                '%' . $normalizedCode . '%';
        }

        $sql .= ' ORDER BY c.course_name';

        return $this->db->select($sql, $params);
    }
}