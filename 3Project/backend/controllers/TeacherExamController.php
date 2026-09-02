<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Choice;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Utils\Database;
use App\Utils\ViewModel;

class TeacherExamController
{
    private Course $courseModel;
    private CourseTeacher $courseTeacherModel;
    private Question $questionModel;
    private Exam $examModel;
    private ExamQuestion $examQuestionModel;
    private ExamResult $examResultModel;
    private Choice $choiceModel;
    private StudentAnswer $studentAnswerModel;

    public function __construct()
    {
        $this->courseModel = new Course();
        $this->courseTeacherModel = new CourseTeacher();
        $this->questionModel = new Question();
        $this->examModel = new Exam();
        $this->examQuestionModel = new ExamQuestion();
        $this->examResultModel = new ExamResult();
        $this->choiceModel = new Choice();
        $this->studentAnswerModel = new StudentAnswer();
    }

    public function create(string $courseId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $courseId = (int) $courseId;
        $teacherId = (int) $_SESSION['user_id'];

        $course = $this->courseModel->getById($courseId);

        if ($course === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        if (!$this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }


        $questions = $this->questionModel
            ->getByCourseId($courseId);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $questions
            ]);
        }

        $name = trim($_POST['exam_name'] ?? '');
        $startInput = $_POST['start_time'] ?? '';
        $endInput = $_POST['end_time'] ?? '';
        $shuffle = isset($_POST['shuffle_questions']);
        $submittedQuestions = $_POST['questions'] ?? [];

        if (
            $name === ''
            || $startInput === ''
            || $endInput === ''
        ) {
            http_response_code(422);

            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $questions,
                'error' => 'Complete all exam information.',
                'oldName' => $name,
                'oldStartTime' => $startInput,
                'oldEndTime' => $endInput,
                'oldShuffle' => $shuffle
            ]);
        }

        $timezone = new \DateTimeZone('Asia/Jerusalem');

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $startInput,
            $timezone
        );

        $end = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $endInput,
            $timezone
        );

        if ($start === false || $end === false || $end <= $start) {
            http_response_code(422);

            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $questions,
                'error' => 'End time must be after start time.',
                'oldName' => $name,
                'oldStartTime' => $startInput,
                'oldEndTime' => $endInput,
                'oldShuffle' => $shuffle
            ]);
        }

        $validatedQuestions = [];
        $position = 1;

        foreach ($submittedQuestions as $questionId => $data) {

            
            $questionId = (int) $questionId;
            $weight = (float) ($data['weight'] ?? 0);

            $question = $this->questionModel->getById($questionId);

            if (
                $question === null
                || (int) $question['course_id'] !== $courseId
                || $weight <= 0
                || $weight > 100
            ) {
                http_response_code(422);

                return new ViewModel('teacher/exams/create', [
                    'course' => $course,
                    'questions' => $questions,
                    'error' => 'One or more questions are invalid.',
                    'oldName' => $name,
                    'oldStartTime' => $startInput,
                    'oldEndTime' => $endInput,
                    'oldShuffle' => $shuffle
                ]);
            }

            $validatedQuestions[] = [
                'id' => $questionId,
                'weight' => $weight,
                'position' => $position++
            ];
        }

        $database = Database::getInstance();

        try {
            $database->beginTransaction();

            $examId = $this->examModel->create(
                $courseId,
                $name,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                $shuffle
            );


            foreach ($validatedQuestions as $question) {
                if (!$this->examQuestionModel->addQuestion(
                    $examId,
                    $question['id'],
                    $question['position'],
                    $question['weight']
                )) {
                    throw new \RuntimeException(
                        'Could not add question to exam.'
                    );
                }
            }

            $database->commit();
        } catch (\Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            throw $exception;
        }

        header(
            'Location: /newSummerTraining/3Project/backend/teacher/courses/'
                . $courseId,
            true,
            303
        );

        exit;


    }

    public function show(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];

        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $courseId = (int) $exam['course_id'];

        if (!$this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        $totalMarks = 0.0;

        foreach ($questions as $question) {
            $totalMarks += (float) $question['weight'];
        }

        $attempt = $this->examResultModel
            ->getByExamAndStudent($examId, $teacherId);

        $hasAnyAttempt = $this->examResultModel
            ->hasAnyAttempt($examId);

        $studentResults = $this->examResultModel
            ->getStudentResultsByExam($examId);

        $showReview =
            (int) ($_SESSION['review_exam_id'] ?? 0)
            === $examId;

        unset($_SESSION['review_exam_id']);

        $reviewQuestions = [];

        if ($showReview && $attempt !== null) {
        
            foreach ($questions as $question) {
                $questionId = (int) $question['id'];

                $choices = $this->choiceModel
                    ->getByQuestionId($questionId);

                $submittedAnswers = $this->studentAnswerModel
                    ->getByResultAndQuestion(
                        (int) $attempt['id'],
                        $questionId
                    );

                $selectedChoiceIds = array_map(
                    static fn(array $answer): int =>
                        (int) $answer['choice_id'],
                    $submittedAnswers
                );

                $isCorrect = $this->studentAnswerModel->isCorrect(
                    (int) $attempt['id'],
                    $questionId
                );

                foreach ($choices as &$choice) {
                    $choice['is_selected'] = in_array(
                        (int) $choice['id'],
                        $selectedChoiceIds,
                        true
                    );
                }

                unset($choice);

                $question['choices'] = $choices;
                $question['is_correct'] = $isCorrect;
                $question['earned_mark'] = $isCorrect
                    ? (float) $question['weight']
                    : 0.0;

                $reviewQuestions[] = $question;
            }
        }

        return new ViewModel('teacher/exams/show', [
            'exam' => $exam,
            'course' => $this->courseModel->getById($courseId),
            'questions' => $questions,
            'totalMarks' => $totalMarks,
            'attempt' => $attempt,
            'hasAnyAttempt' => $hasAnyAttempt,
            'studentResults' => $studentResults,
            'showReview' => $showReview,
            'reviewQuestions' => $reviewQuestions
        ]);
    }

    public function test(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];
        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        if (!$this->courseTeacherModel->isAssigned(
            (int) $exam['course_id'],
            $teacherId
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        if ($this->examResultModel->getByExamAndStudent(
            $examId,
            $teacherId
        ) !== null) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        foreach ($questions as &$question) {
            $question['choices'] = $this->choiceModel
                ->getByQuestionId((int) $question['id']);
        }

        unset($question);

        if ((bool) $exam['shuffle_questions']) {
            shuffle($questions);
        }

        return new ViewModel('student/exams/show', [
            'exam' => $exam,
            'questions' => $questions,
            'formAction' =>
                '/newSummerTraining/3Project/backend/teacher/exams/'
                . 'test/'
                . $examId,
            'pageHeading' => 'Test Exam',
            'introText' =>
                'Complete this exam as a teacher test attempt.',
            'statusLabel' => 'Teacher test'
        ]);
    }

    public function submitTest(string $examId): never
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            exit;
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];
        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);
            exit;
        }

        if (!$this->courseTeacherModel->isAssigned(
            (int) $exam['course_id'],
            $teacherId
        )) {
            http_response_code(403);
            exit;
        }


        if ($this->examResultModel->getByExamAndStudent(
            $examId,
            $teacherId
        ) !== null) {
            http_response_code(403);
            exit;
        }

        $submittedAnswers = $_POST['answers'] ?? [];

        if (!is_array($submittedAnswers)) {
            $submittedAnswers = [];
        }

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        $database = Database::getInstance();


        try {
            $database->beginTransaction();

            $resultId = $this->examResultModel->create(
                $examId,
                $teacherId
            );

            foreach ($questions as $question) {
                $questionId = (int) $question['id'];
                $choiceIds = $submittedAnswers[$questionId] ?? [];

                if (!is_array($choiceIds)) {
                    $choiceIds = [$choiceIds];
                }

                $choiceIds = array_unique(
                    array_map('intval', $choiceIds)
                );

                foreach ($choiceIds as $choiceId) {
                    if ($choiceId <= 0) {
                        continue;
                    }

                    if ($this->studentAnswerModel->create(
                        $resultId,
                        $questionId,
                        $choiceId
                    ) === null) {
                        throw new \RuntimeException(
                            'An invalid answer was submitted.'
                        );
                    }
                }
            }

            $mark = 0.0;

            foreach ($questions as $question) {
                if ($this->studentAnswerModel->isCorrect(
                    $resultId,
                    (int) $question['id']
                )) {
                    $mark += (float) $question['weight'];
                }
            }

            if (!$this->examResultModel->submit($resultId, $mark)) {
                throw new \RuntimeException(
                    'The exam could not be submitted.'
                );
            }

            $database->commit();
        } catch (\Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            error_log($exception->__toString());
            http_response_code(500);
            exit;
        }

        $_SESSION['review_exam_id'] = $examId;

        header(
            'Location: /newSummerTraining/3Project/backend/teacher/exams/'
                . $examId,
            true,
            303
        );

        exit;
    }

    public function edit(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];
        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $courseId = (int) $exam['course_id'];

        if (!$this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($exam['start_time']);

        if (
            $now >= $start
            || $this->examResultModel->hasAnyAttempt($examId)
        ) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $course = $this->courseModel->getById($courseId);
        $questions = $this->questionModel->getByCourseId($courseId);
        $selectedQuestions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        return new ViewModel('teacher/exams/create', [
            'course' => $course,
            'questions' => $questions,
            'exam' => $exam,
            'selectedQuestions' => $selectedQuestions,
            'oldName' => $exam['exam_name'],
            'oldStartTime' => (new \DateTimeImmutable(
                $exam['start_time']
            ))->format('Y-m-d\TH:i'),
            'oldEndTime' => (new \DateTimeImmutable(
                $exam['end_time']
            ))->format('Y-m-d\TH:i'),
            'oldShuffle' => (bool) $exam['shuffle_questions']
        ]);
    }

    public function update(string $examId): ViewModel
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];
        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);

            return new ViewModel('errors/404');
        }

        $courseId = (int) $exam['course_id'];

        if (!$this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $now = new \DateTimeImmutable();
        $currentStart = new \DateTimeImmutable($exam['start_time']);

        if (
            $now >= $currentStart
            || $this->examResultModel->hasAnyAttempt($examId)
        ) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $name = trim($_POST['exam_name'] ?? '');
        $startInput = $_POST['start_time'] ?? '';
        $endInput = $_POST['end_time'] ?? '';
        $shuffle = isset($_POST['shuffle_questions']);
        $submittedQuestions = $_POST['questions'] ?? [];

        $timezone = new \DateTimeZone('Asia/Jerusalem');
        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $startInput,
            $timezone
        );
        $end = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $endInput,
            $timezone
        );

        $course = $this->courseModel->getById($courseId);
        $courseQuestions = $this->questionModel
            ->getByCourseId($courseId);

        if (
            $name === ''
            || $start === false
            || $end === false
            || $start <= new \DateTimeImmutable('now', $timezone)
            || $end <= $start
            || !is_array($submittedQuestions)
            || $submittedQuestions === []
        ) {
            http_response_code(422);

            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $courseQuestions,
                'exam' => $exam,
                'selectedQuestions' => $this->examQuestionModel
                    ->getQuestionsByExam($examId),
                'error' =>
                    'Complete the exam correctly and add at least one question.',
                'oldName' => $name,
                'oldStartTime' => $startInput,
                'oldEndTime' => $endInput,
                'oldShuffle' => $shuffle
            ]);
        }

        $validatedQuestions = [];
        $position = 1;

        foreach ($submittedQuestions as $questionId => $data) {
                        
            $questionId = (int) $questionId;
            $weight = (float) ($data['weight'] ?? 0);
            $question = $this->questionModel->getById($questionId);

            if (
                $question === null
                || (int) $question['course_id'] !== $courseId
                || $weight <= 0
                || $weight > 100
            ) {
                http_response_code(422);

                return new ViewModel('teacher/exams/create', [
                    'course' => $course,
                    'questions' => $courseQuestions,
                    'exam' => $exam,
                    'selectedQuestions' => $this->examQuestionModel
                        ->getQuestionsByExam($examId),
                    'error' => 'One or more questions are invalid.',
                    'oldName' => $name,
                    'oldStartTime' => $startInput,
                    'oldEndTime' => $endInput,
                    'oldShuffle' => $shuffle
                ]);
            }

            $validatedQuestions[] = [
                'id' => $questionId,
                'weight' => $weight,
                'position' => $position++
            ];
        }

        $database = Database::getInstance();

        try {
            $database->beginTransaction();

            $this->examModel->update(
                $examId,
                $name,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                $shuffle
            );

            $this->examQuestionModel->removeAllByExam($examId);

            foreach ($validatedQuestions as $question) {
                if (!$this->examQuestionModel->addQuestion(
                    $examId,
                    $question['id'],
                    $question['position'],
                    $question['weight']
                )) {
                    throw new \RuntimeException(
                        'Could not update the exam questions.'
                    );
                }
            }

            $database->commit();
        } catch (\Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            throw $exception;
        }

        header(
            'Location: /newSummerTraining/3Project/backend/teacher/exams/'
                . $examId,
            true,
            303
        );

        exit;
    }

    public function delete(string $examId): never
    {
        if (($_SESSION['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            exit;
        }

        $examId = (int) $examId;
        $teacherId = (int) $_SESSION['user_id'];
        $exam = $this->examModel->getById($examId);

        if ($exam === null) {
            http_response_code(404);
            exit;
        }

        $courseId = (int) $exam['course_id'];

        if (!$this->courseTeacherModel->isAssigned(
            $courseId,
            $teacherId
        )) {
            http_response_code(403);
            exit;
        }

        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($exam['start_time']);

        if (
            $now >= $start
            || $this->examResultModel->hasAnyAttempt($examId)
        ) {
            http_response_code(403);
            exit;
        }

        $this->examModel->delete($examId);

        header(
            'Location: /newSummerTraining/3Project/backend/teacher/courses/'
                . $courseId,
            true,
            303
        );

        exit;
    }
}
