<?php

use App\Routes\Router;

Router::get(
    '/api/questions',
    ['QuestionController', 'getAll']
);
// Display the create form
Router::get(
    '/api/courses/{courseId}/questions/create',
    ['QuestionController', 'create']
);
// Display the edit form
Router::get(
    '/api/questions/{id}/edit',
    ['QuestionController', 'edit']
);

Router::get(
    '/api/questions/{id}',
    ['QuestionController', 'getById']
);






// Save a new question
Router::post(
    '/api/courses/{courseId}/questions',
    ['QuestionController', 'store']
);



// Update a question
Router::post(
    '/api/questions/{id}/update',
    ['QuestionController', 'update']
);

// Delete a question
Router::post(
    '/api/questions/{id}/delete',
    ['QuestionController', 'delete']
);
