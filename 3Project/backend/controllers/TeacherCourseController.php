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
    private Course $courseModel;
    private CourseTeacher $courseTeacherModel;
    private CourseStudent $courseStudentModel;
    private Exam $examModel;

    public function __construct()
    {
        $this->courseModel = new Course();
        $this->courseTeacherModel = new CourseTeacher();
        $this->courseStudentModel = new CourseStudent();
        $this->examModel = new Exam();
    }
    
    public function show(string $courseId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $courseId = (int) $courseId;
        $teacherId = (int) ($_SESSION['user_id'] ?? 0);

        $course = $this->courseModel->getById($courseId);   

        if ($course === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $isAssigned = $this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        );

        if (!$isAssigned) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $students = $this->courseStudentModel
            ->getStudentsByCourse($courseId);

        $exams = $this->examModel
            ->getByCourseId($courseId);

        foreach ($exams as &$exam) {
            $exam['status'] = $this->examModel->getStatus($exam);
        }

        unset($exam);

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

        $course = $this->courseModel->getById($courseId);

        if ($course === null) {
            http_response_code(404);
            exit;
        }


        if ($this->courseTeacherModel->canAssign(
            $courseId,
            $teacherId
        )) {
            $this->courseTeacherModel->assignTeacher(
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
