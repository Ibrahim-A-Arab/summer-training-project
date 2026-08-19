<?php

declare(strict_types=1);

use App\Routes\Router;

// Course routes

Router::get(
    '/api/courses/create',
    ['CourseController', 'create']
);

Router::post(
    '/api/courses',
    ['CourseController', 'store']
);

// Course question routes

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

// Question routes

Router::get(
    '/api/questions',
    ['QuestionController', 'getAll']
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