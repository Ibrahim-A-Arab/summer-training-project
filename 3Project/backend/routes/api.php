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