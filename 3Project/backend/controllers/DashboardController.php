<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\ViewModel;

class DashboardController
{
    public function index(): ViewModel
    {
        $role = $_SESSION['role'] ?? '';
        $name = $_SESSION['name'] ?? '';

        if ($role === 'teacher') {
            return new ViewModel('dashboards/teacher',[
                'name' => $name
            ]);
        }

        if ($role === 'student') {
            return new ViewModel('dashboards/student',[
                'name' => $name
            ]);
        }

        http_response_code(403);

        return new ViewModel('errors/403');
    }
}