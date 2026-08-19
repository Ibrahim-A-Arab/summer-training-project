<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Course;
use App\Utils\ViewModel;

class CourseController
{
    public function create(): ViewModel
    {
        return new ViewModel('courses/create');
    }

    public function store(): ViewModel
    {
        $code = trim($_POST['course_code'] ?? '');
        $name = trim($_POST['course_name'] ?? '');

        if ($code === '' || $name === '') {
            http_response_code(422);

            return new ViewModel('courses/create', [
                'error' => 'Course code and name are required.',
                'oldCode' => $code,
                'oldName' => $name
            ]);
        }

        $courseId = (new Course())->create($code, $name);

        header(
            "Location: /newSummerTraining/3Project/backend/api/courses/$courseId/questions",
            true,
            303
        );

        exit;
    }
}