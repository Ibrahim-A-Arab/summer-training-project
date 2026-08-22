START TRANSACTION;

-- Password for both users: password
INSERT INTO users (
    id,
    personal_id,
    name,
    email,
    password_hash,
    role
) VALUES
(
    1,
    'T1001',
    'Test Teacher',
    'teacher@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    'teacher'
),
(
    2,
    'S1001',
    'Test Student',
    'student@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    'student'
);

INSERT INTO courses (
    id,
    course_code,
    course_name
) VALUES
(
    1,
    'COMP1330',
    'Introduction to Programming'
);

INSERT INTO course_teachers (
    id,
    course_id,
    teacher_id
) VALUES
(
    1,
    1,
    1
);

INSERT INTO course_students (
    id,
    course_id,
    student_id
) VALUES
(
    1,
    1,
    2
);

INSERT INTO questions (
    id,
    course_id,
    question_text
) VALUES
(
    1,
    1,
    'What is 2 + 2?'
),
(
    2,
    1,
    'Which values are even numbers?'
);

INSERT INTO choices (
    id,
    question_id,
    choice_text,
    is_correct
) VALUES
-- Question 1
(
    1,
    1,
    '3',
    FALSE
),
(
    2,
    1,
    '4',
    TRUE
),
(
    3,
    1,
    '5',
    FALSE
),

-- Question 2: multiple correct answers
(
    4,
    2,
    '2',
    TRUE
),
(
    5,
    2,
    '3',
    FALSE
),
(
    6,
    2,
    '4',
    TRUE
),
(
    7,
    2,
    '5',
    FALSE
);

COMMIT;