<?php
//just realized this
//should've been named authentication, because auth can be an abreviation for authorization too.
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Utils\ViewModel;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }
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

        $validation = $this->userModel->validateRegistration([
            'personal_id' => $personalId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'role' => $role
        ]);

        if (!$validation['valid']) {
            http_response_code(422);

            return new ViewModel('auth/signUp', [
                ...$oldData,
                'error' => $validation['error']
            ]);
        }

        $userData = $validation['data'];

        $completedSignUp = $this->userModel->create(
            $userData['personal_id'],
            $userData['name'],
            $userData['email'],
            $userData['password_hash'],
            $userData['role']
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

        $user = $this->userModel->authenticate(
            $email,
            $password
        );

        if ($user === null) {
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
