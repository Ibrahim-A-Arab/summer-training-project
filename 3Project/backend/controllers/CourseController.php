<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseTeacher;
use App\Utils\ViewModel;

class CourseController
{
    private const BASE_PATH = '/newSummerTraining/3Project/backend';
    private Course $courseModel;
    private CourseTeacher $courseTeacherModel;

    public function __construct()
    {
        $this->courseModel = new Course();
        $this->courseTeacherModel = new CourseTeacher();
    }
        public function index(): ViewModel
    {
        if (!$this->isTeacher()) {
            return $this->forbidden();
        }

        $teacherId = (int) $_SESSION['user_id'];
        $search = trim($_GET['search'] ?? '');


        $courses = $this->courseTeacherModel
            ->getCoursesByTeacher($teacherId);

        $availableCourses = $this->courseTeacherModel
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

        if($_SERVER['REQUEST_METHOD'] === 'GET'){
            return new ViewModel('courses/create');
        }

        $code = trim($_POST['course_code'] ?? '');
        $name = trim($_POST['course_name'] ?? '');
        $validation = $this->courseModel->validate($code, $name);

        if (!$validation['valid']) {
            http_response_code(422);

            return new ViewModel('courses/create', [
                'error' => $validation['error'],
                'oldCode' => $code,
                'oldName' => $name
            ]);
        }

        $this->courseModel->createWithTeacher(
            $validation['code'],
            $validation['name'],
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
