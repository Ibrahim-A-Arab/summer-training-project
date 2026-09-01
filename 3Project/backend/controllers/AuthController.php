<?php
//just realized this
//should've been named authentication, because auth can be an abreviation for authorization too.
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Utils\ViewModel;

class AuthController
{
    public function showLogin(): ViewModel
    {
        return new ViewModel('auth/login');
    }

    public function showSignUp(): ViewModel
    {
        return new ViewModel('auth/signUp');
    }

    public function signup(): ViewModel
    {
        $personalId = trim($_POST['personal_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $role = $_POST['role'] ?? '';

        $oldData = [
            'oldPersonalId' => $personalId,
            'oldName' => $name,
            'oldEmail' => $email
        ];

        if (
            $personalId === ''
            || $name === ''
            || $email === ''
            || $password === ''
            || $passwordConfirmation === ''
            || $role === ''
        ) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'All fields are required, fill all fields.'
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'Enter a valid email address.'
            ]);
        }


        if (!in_array($role, ['student', 'teacher'], true)) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'select a role to register'
            ]);
        }

        if (strlen($password) < 8) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'Password must contain at least 8 characters.'
            ]);
        }

        if ($password !== $passwordConfirmation) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'Passwords do not match.'
            ]);
        }

        $userModel = new User();

        if ($userModel->getByEmail($email) !== null) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'email already registered, login to your account'
            ]);
        }

        if ($userModel->getByPersonalId($personalId) !== null) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => 'personal id is already registered, login to your account'
            ]);
        }

        $completedSignUp = $userModel->create(
            $personalId,
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role
        );

        if (!$completedSignUp) {
            http_response_code(500);
        
            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => "couldn't create the account, try again..."
            ]);
        }

        header(
            'location: /newSummerTraining/3Project/backend/login',
            true,
            303
        );

        exit;
    }

    public function login(): ViewModel
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = (new User())->getByEmail($email);

        if (
            $user === null
            || !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            http_response_code(422);

            return new ViewModel('auth/login', [
                'error' => 'Invalid email or password.',
                'email' => $email,
            ]);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        header(
            'Location: /newSummerTraining/3Project/backend/dashboard',
            true,
            303
        );

        exit;
    }

    public function logout(): never
    {
        $_SESSION = [];
        session_destroy();

        header(
            'Location: /newSummerTraining/3Project/backend/login',
            true,
            303
        );

        exit;
    }

    public function home(): ViewModel
    {
        return new ViewModel('home', [
            'name' => $_SESSION['name'] ?? ''
        ]);
    }
}
