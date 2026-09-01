<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CourseStudent;
use App\Utils\ViewModel;
use App\Models\Course;
use App\Models\Exam;

class StudentCourseController
{
    public function index(): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $studentId = (int) $_SESSION['user_id'];

        $courses = (new CourseStudent())
            ->getCoursesByStudent($studentId);

        return new ViewModel('student/courses/index', [
            'courses' => $courses
        ]);
    }

    public function enrollmentList(): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $studentId = (int) $_SESSION['user_id'];
        $search = trim($_GET['search'] ?? '');

        $courses = (new CourseStudent())
            ->getAvailableCourses($studentId, $search);

        return new ViewModel('student/courses/enroll', [
            'courses' => $courses,
            'search' => $search
        ]);
    }

    public function enroll(string $courseId): never
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);
            exit;   
        }

        $courseId = (int) $courseId;
        $studentId = (int) $_SESSION['user_id'];

        $courseModel = new Course();
        $enrollmentModel = new CourseStudent();

        if (!$courseModel->exists($courseId)) {
            http_response_code(404);
            exit;
        }

        if (!$enrollmentModel->isEnrolled($courseId, $studentId)) {
            $enrollmentModel->enroll($courseId, $studentId);
        }

        header(
            'Location: /newSummerTraining/3Project/backend/student/courses',
            true,
            303
        );

        exit;
    }

    public function show(string $courseId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'student') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $courseId = (int) $courseId;
        $studentId = (int) $_SESSION['user_id'];

        $courseModel = new Course();
        $enrollmentModel = new CourseStudent();

        $course = $courseModel->getById($courseId);

        if (
            $course === null
            || !$enrollmentModel->isEnrolled($courseId, $studentId)
        ) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $exams = (new Exam())->getByCourseId($courseId);

        return new ViewModel('student/courses/show', [
            'course' => $course,
            'exams' => $exams
        ]);
    }

    
}