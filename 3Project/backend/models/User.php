<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class User{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->select(    
            'SELECT * FROM users'
        );
    }

    public function getById(int $id):?array{
        $users = $this->db->select(
            'SELECT * FROM users
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );
        return $users[0] ?? null;
    }

    public function getByEmail(string $email): ?array
{
    $users = $this->db->select(
        'SELECT id, personal_id, name,
                email, password_hash, role
        FROM users
        WHERE email = :email
        LIMIT 1',
        ['email' => $email]
    );

    return $users[0] ?? null;
}

    public function getByPersonalId(String $personal_id):?array{
        $users = $this->db->select(
            'SELECT * FROM users
            WHERE personal_id = :personal_id
            LIMIT 1',
            ['personal_id' => $personal_id]
        );
        return $users[0] ?? null;
    }

    public function getByRole(String $role):array{
        return $this->db->select(
            'SELECT * FROM users 
            WHERE role = :role
            ORDER BY id',
            ['role' => $role]
        );
    }

    public function create(String $personal_id, String $name,
    String $email, String $password_hash, String $role):bool{
        return $this->db->execute(
            'INSERT INTO users (personal_id, name, email, password_hash, role)
            VALUES (:personal_id, :name, :email, :password_hash, :role)',
            [
                'personal_id' => $personal_id,
                'name' => $name,
                'email' => $email,
                'password_hash' => $password_hash,
                'role' => $role
            ]
        );
    }

    public function update(int $id, String $personal_id, 
    String $name, String $email, String $role):bool{
        return $this->db->execute(
            'UPDATE users
            SET personal_id = :personal_id,
                name = :name,
                email = :email,
                role = :role
            WHERE id = :id',
            [
                'id' => $id,
                'personal_id' => $personal_id,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        );
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->db->execute(
            'UPDATE users
            SET password_hash = :password_hash
            WHERE id = :id',
            [
                'id' => $id,
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                )
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM users WHERE id = :id',
            ['id' => $id]
        );
    }
}

?>