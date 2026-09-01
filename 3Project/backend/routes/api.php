<?php

declare(strict_types=1);

use App\Routes\Router;

Router::get(
    '/login',
    ['AuthController', 'showLogin']
);

Router::post(
    '/login',
    ['AuthController', 'login']
);

Router::post(
    '/logout',
    ['AuthController', 'logout']
);


Router::get(
    '/signup',
    ['AuthController', 'showSignup']
);

Router::post(
    '/signup',
    ['AuthController', 'signup']
);

Router::get(
    '/dashboard',
    ['DashboardController', 'index']
);

Router::get(
    '/api/courses',
    ['CourseController', 'index']
);

Router::get(
    '/api/courses/create',
    ['CourseController', 'create']
);

Router::post(
    '/api/courses',
    ['CourseController', 'store']
);

Router::get(
    '/api/courses/{courseId}/questions',
    ['QuestionController', 'getByCourse']
);

Router::get(
    '/api/courses/{courseId}/questions/create',
    ['QuestionController', 'create']
);

Router::post(
    '/api/courses/{courseId}/questions',
    ['QuestionController', 'store']
);

Router::get(
    '/api/questions/{id}/edit',
    ['QuestionController', 'edit']
);

Router::get(
    '/api/questions/{id}',
    ['QuestionController', 'getById']
);

Router::post(
    '/api/questions/{id}/update',
    ['QuestionController', 'update']
);

Router::post(
    '/api/questions/{id}/delete',
    ['QuestionController', 'delete']
);

Router::get(
    '/student/courses',
    ['StudentCourseController', 'index']
);

Router::get(
    '/student/courses/enroll',
    ['StudentCourseController', 'enrollmentList']
);

Router::post(
    '/student/courses/{courseId}/enroll',
    ['StudentCourseController', 'enroll']
);

Router::get(
    '/student/courses/{courseId}',
    ['StudentCourseController', 'show']
);

Router::get(
    '/student/exams/{examId}',
    ['StudentExamController', 'show']
);

Router::post(
    '/student/exams/{examId}/submit',
    ['StudentExamController', 'submit']
);

Router::get(
    '/teacher/courses/{courseId}',
    ['TeacherCourseController', 'show']
);

Router::get(
    '/home',
    ['AuthController', 'home']
);

Router::post(
    '/teacher/courses/{courseId}/co-teach',
    ['TeacherCourseController', 'coTeach']
);

Router::get(
    '/teacher/courses/{courseId}/exams/create',
    ['TeacherExamController', 'create']
);

Router::post(
    '/teacher/courses/{courseId}/exams',
    ['TeacherExamController', 'store']
);

Router::get(
    '/teacher/exams/{examId}',
    ['TeacherExamController', 'show']
);

Router::get(
    '/teacher/exams/{examId}/test',
    ['TeacherExamController', 'test']
);

Router::post(
    '/teacher/exams/{examId}/test',
    ['TeacherExamController', 'submitTest']
);

Router::get(
    '/teacher/exams/{examId}/edit',
    ['TeacherExamController', 'edit']
);

Router::post(
    '/teacher/exams/{examId}/update',
    ['TeacherExamController', 'update']
);

Router::post(
    '/teacher/exams/{examId}/delete',
    ['TeacherExamController', 'delete']
);
