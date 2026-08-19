<?php
declare(strict_types=1);
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

require __DIR__ . '/controllers/CourseController.php';
require __DIR__ . '/controllers/QuestionController.php';

// Routes

require __DIR__ . '/routes/api.php';


try {
    \App\Routes\Router::dispatch();
} catch (\Throwable $exception) {
    error_log($exception->__toString());

    http_response_code(500);

    $view = new \App\Utils\ViewModel('errors/500');
    $view->render();
}
