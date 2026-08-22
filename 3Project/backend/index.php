<?php
declare(strict_types=1);
session_start();    

use App\Utils\Database;
$env = parse_ini_file(__DIR__ . '/.env');

foreach ($env as $key => $value) {
    putenv("$key=$value");
}


require __DIR__ . '/utils/Database.php';
require __DIR__ . '/utils/ViewModel.php';


require __DIR__ . '/routes/Router.php';


// Models

require __DIR__ . '/models/User.php';
require __DIR__ . '/models/Course.php';
require __DIR__ . '/models/CourseTeacher.php';
require __DIR__ . '/models/CourseStudent.php';
require __DIR__ . '/models/Question.php';
require __DIR__ . '/models/Choice.php';
require __DIR__ . '/models/Exam.php';
require __DIR__ . '/models/ExamQuestion.php';
require __DIR__ . '/models/ExamResult.php';
require __DIR__ . '/models/StudentAnswer.php';

// Controllers

require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/CourseController.php';
require __DIR__ . '/controllers/QuestionController.php';

// Routes


require __DIR__ . '/routes/api.php';

$uri = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$basePath =
    '/newSummerTraining/3Project/backend';

if (str_starts_with($uri, $basePath)) {
    $uri = substr(
        $uri,
        strlen($basePath)
    );
}

$publicRoutes = [
    '/login',
    '/signup'
];

$isPublicRoute = in_array(
    $uri,
    $publicRoutes,
    true
);

$isLoggedIn = isset($_SESSION['user_id']);

// if (!$isPublicRoute) {////////////////////////////////////////////////clears cache so a user can't go back to the prev page if logged out for ex
//     header(
//         'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
//     );
//     header('Pragma: no-cache');
//     header('Expires: 0');
// }

if (!$isPublicRoute && !$isLoggedIn) {
    header(
        'Location: /newSummerTraining/3Project/backend/login',
        true,
        303
    );

    exit;
}

try {
    \App\Routes\Router::dispatch();
} catch (\Throwable $exception) {
    error_log($exception->__toString());

    http_response_code(500);

    $view = new \App\Utils\ViewModel('errors/500');
    $view->render();
}
