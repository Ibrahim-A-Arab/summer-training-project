<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\ViewModel;
use App\Models\CourseStudent;
use App\Models\Exam;

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
        $studentId = (int) $_SESSION['user_id'];

        $courses = (new CourseStudent())
            ->getCoursesByStudent($studentId);

        $availableExams = (new Exam())
            ->getAvailableForStudent($studentId);

        return new ViewModel('dashboards/student', [
            'name' => $name,
            'courseCount' => count($courses),
            'upcomingExamCount' => count($availableExams)
        ]);
    }

        http_response_code(403);

        return new ViewModel('errors/403');
    }
}