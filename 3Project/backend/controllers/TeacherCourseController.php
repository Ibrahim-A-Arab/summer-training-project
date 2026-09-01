<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseStudent;
use App\Models\CourseTeacher;
use App\Models\Exam;
use App\Utils\ViewModel;

class TeacherCourseController
{
    public function show(string $courseId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $courseId = (int) $courseId;
        $teacherId = (int) ($_SESSION['user_id'] ?? 0);

        $course = (new Course())->getById($courseId);   

        if ($course === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $isAssigned = (new CourseTeacher())->isAssigned(
            $courseId,
            $teacherId
        );

        if (!$isAssigned) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $students = (new CourseStudent())
            ->getStudentsByCourse($courseId);

        $exams = (new Exam())
            ->getByCourseId($courseId);

        return new ViewModel('teacher/courses/show', [
            'course' => $course,
            'students' => $students,
            'exams' => $exams,
            'studentCount' => count($students)
        ]);
    }

    public function coTeach(string $courseId): never
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            exit;
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $courseId = (int) $courseId;

        $course = (new Course())->getById($courseId);

        if ($course === null) {
            http_response_code(404);
            exit;
        }

        $courseTeacherModel = new CourseTeacher();

        if (!$courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            $courseTeacherModel->assignTeacher(
                $courseId,
                $teacherId
            );
        }

        header(
            'Location: /newSummerTraining/3Project/backend/api/courses',
            true,
            303
        );

        exit;
    }
}