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
            'SELECT id, course_code, course_name
            FROM courses
            ORDER BY course_name'
        );  
    }

    public function getById(int $id): ?array
    {
        $courses = $this->db->select(
            'SELECT id, course_code, course_name
            FROM courses
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $courses[0] ?? null;// null if no such id 
    }

    public function getByCode(string $code): ?array 
    {
        $courses = $this->db->select(
            'SELECT id, course_code, course_name
            FROM courses
            WHERE course_code = :code
            LIMIT 1',
            ['code' => $code]
        );

        return $courses[0] ?? null;
    }

    public function exists(int $id): bool
    {
        return $this->getById($id) !== null;
    }

    public function create(string $code, string $name): int
    {
        $this->db->execute(
            'INSERT INTO courses (course_code, course_name)
            VALUES (:code, :name)',
            [
                'code' => $code,
                'name' => $name
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $code,
        string $name
    ): bool {
        return $this->db->execute(
            'UPDATE courses
            SET course_code = :code,
                course_name = :name
            WHERE id = :id',
            [
                'id' => $id,
                'code' => $code,
                'name' => $name
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM courses WHERE id = :id',
            ['id' => $id]
        );
    }
}