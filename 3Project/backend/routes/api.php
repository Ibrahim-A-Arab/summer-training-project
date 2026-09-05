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
    ['CourseController', 'create']
);

Router::get(
    '/api/courses/questions/{courseId}',
    ['QuestionController', 'getByCourse']
);

Router::get(
    '/api/courses/questions/create/{courseId}',
    ['QuestionController', 'create']
);

Router::post(
    '/api/courses/questions/{courseId}',
    ['QuestionController', 'create']
);

Router::get(
    '/api/questions/edit/{id}',
    ['QuestionController', 'edit']
);

Router::get(
    '/api/questions/{id}',
    ['QuestionController', 'getById']
);

Router::post(
    '/api/questions/update/{id}',
    ['QuestionController', 'update']
);

Router::post(
    '/api/questions/delete/{id}',
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
    '/student/courses/enroll/{courseId}',
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
    '/student/exams/submit/{examId}',
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
    '/teacher/courses/co-teach/{courseId}',
    ['TeacherCourseController', 'coTeach']
);

Router::get(
    '/teacher/courses/exams/create/{courseId}',
    ['TeacherExamController', 'create']
);

Router::post(
    '/teacher/courses/exams/create/{courseId}',
    ['TeacherExamController', 'create']
);

Router::get(
    '/teacher/exams/{examId}',
    ['TeacherExamController', 'show']
);

Router::get(
    '/teacher/exams/test/{examId}',
    ['TeacherExamController', 'test']
);

Router::post(
    '/teacher/exams/test/{examId}',
    ['TeacherExamController', 'submitTest']
);

Router::get(
    '/teacher/exams/edit/{examId}',
    ['TeacherExamController', 'edit']
);

Router::post(
    '/teacher/exams/edit/{examId}',
    ['TeacherExamController', 'edit']
);

Router::post(
    '/teacher/exams/delete/{examId}',
    ['TeacherExamController', 'delete']
);
