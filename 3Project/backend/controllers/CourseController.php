<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseTeacher;
use App\Utils\ViewModel;

class CourseController
{
    private const BASE_PATH = '/newSummerTraining/3Project/backend';

    public function index(): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

        $teacherId = (int) $_SESSION['user_id'];
        $search = trim($_GET['search'] ?? '');

        $courseTeacherModel = new CourseTeacher();

        $courses = $courseTeacherModel
            ->getCoursesByTeacher($teacherId);

        $availableCourses = $courseTeacherModel
            ->getAvailableCourses($teacherId, $search);

        return new ViewModel('courses/index', [
            'courses' => $courses,
            'availableCourses' => $availableCourses,
            'search' => $search
        ]);
    }

    public function create(): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

        return new ViewModel('courses/create');
    }

    public function store(): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

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

        $courseModel = new Course();

        if ($courseModel->getByCode($code) !== null) {
            http_response_code(422);

            return new ViewModel('courses/create', [
                'error' => 'This course code already exists.',
                'oldCode' => $code,
                'oldName' => $name
            ]);
        }


        $courseId = $courseModel->create($code, $name);

        (new CourseTeacher())->assignTeacher(
            $courseId,
            (int) $_SESSION['user_id']
        );

        header(
            'Location: ' . self::BASE_PATH . '/api/courses',
            true,
            303
        );

        exit;
    }

    private function isTeacher(): bool
    {
        return ($_SESSION['role'] ?? '') === 'teacher';
    }

    private function forbidden(): ViewModel
    {
        http_response_code(403);

        return new ViewModel('errors/403');
    }
}
