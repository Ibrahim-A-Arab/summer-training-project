<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Course
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->select(
            'SELECT course_id, course_code, course_name
            FROM courses
            ORDER BY course_name'
        );
    }

    public function exists(int $id): bool
    {
        return $this->db->select(
            'SELECT course_id FROM courses WHERE course_id = :id LIMIT 1',
            ['id' => $id]
        ) !== [];
    }
}
