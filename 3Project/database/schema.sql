CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_id VARCHAR(32) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL
);


CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) UNIQUE NOT NULL,
    course_name VARCHAR(150) NOT NULL
);


CREATE TABLE course_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,

    UNIQUE (course_id, teacher_id),

    FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (teacher_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
);


CREATE TABLE course_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,

    UNIQUE (course_id, student_id),
    -- a student can't be taking the course twice at the same time

    FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
);


CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('MCQ','TrueOrFalse') NOT NULL DEFAULT 'MCQ',
	
    FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE CASCADE
    -- delete question of course if course is not used and can be deleted
);


CREATE TABLE choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (question_id)
        REFERENCES questions(id)
        ON DELETE CASCADE
    -- if a question is used by an exam (can't be deleted because of the restrict
    -- if a question isn't used, delete it and it's choices 
);


CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_name VARCHAR(150) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    shuffle_questions BOOLEAN NOT NULL DEFAULT FALSE, -- 

    CHECK (end_time > start_time),

    FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE CASCADE
    -- if a course gets deleted, delete its questions
);


CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    position INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL DEFAULT 1,-- ex: 0.25-0.50-1.00-5.00-50.00-100.00

    CHECK (weight > 0 AND weight <= 100),
    

    UNIQUE (exam_id, question_id),
    -- have to check that exam.course_id=question.course_id
    UNIQUE (exam_id, position),
    -- one unique with all three may put multiple question on the same pos
    -- it also may put same question multiple times (different pos)

    FOREIGN KEY (exam_id)
        REFERENCES exams(id)
        ON DELETE CASCADE,

    FOREIGN KEY (question_id)
        REFERENCES questions(id)
        ON DELETE RESTRICT
);


CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    mark DECIMAL(6,2),
    submitted_at DATETIME,

    UNIQUE (exam_id, student_id),

    FOREIGN KEY (exam_id)
        REFERENCES exams(id)
        ON DELETE CASCADE,

    FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


CREATE TABLE student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    exam_results_id INT NOT NULL,
    question_id INT NOT NULL,
    choice_id INT NOT NULL,

    UNIQUE (exam_results_id, question_id, choice_id),

    FOREIGN KEY (exam_results_id)
        REFERENCES exam_results(id)
        ON DELETE CASCADE,

    FOREIGN KEY (question_id)
        REFERENCES questions(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (choice_id)
        REFERENCES choices(id)
        ON DELETE RESTRICT
);