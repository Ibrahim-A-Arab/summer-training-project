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

    public function validateRegistration(array $data): array
    {
        $personalId = trim((string) ($data['personal_id'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmation = (string) (
            $data['password_confirmation'] ?? ''
        );
        $role = (string) ($data['role'] ?? '');

        if (
            $personalId === ''
            || $name === ''
            || $email === ''
            || $password === ''
            || $confirmation === ''
            || $role === ''
        ) {
            return $this->invalid(
                'All fields are required, fill all fields.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->invalid('Enter a valid email address.');
        }

        if (!in_array($role, ['student', 'teacher'], true)) {
            return $this->invalid('Select a role to register.');
        }

        if (strlen($password) < 8) {
            return $this->invalid(
                'Password must contain at least 8 characters.'
            );
        }

        if ($password !== $confirmation) {
            return $this->invalid('Passwords do not match.');
        }

        if ($this->getByEmail($email) !== null) {
            return $this->invalid(
                'Email already registered, login to your account.'
            );
        }

        if ($this->getByPersonalId($personalId) !== null) {
            return $this->invalid(
                'Personal ID already registered, login to your account.'
            );
        }

        return [
            'valid' => true,
            'data' => [
                'personal_id' => $personalId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                'role' => $role
            ]
        ];
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->getByEmail(trim($email));

        if (
            $user === null
            || !password_verify($password, $user['password_hash'])
        ) {
            return null;
        }

        return $user;
    }

    private function invalid(string $error): array
    {
        return [
            'valid' => false,
            'error' => $error
        ];
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
