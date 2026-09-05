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

        $input = [
            'name' => $_POST['exam_name'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'shuffle' => isset($_POST['shuffle_questions'])
        ];

        $submittedQuestions = $_POST['questions'] ?? [];
        $validation = $this->examModel->validate($input);
        $selectionValidation = $this->examQuestionModel
            ->validateSelection(
                $courseId,
                $submittedQuestions,
                $this->questionModel
            );

        if (
            !$validation['valid']
            || !$selectionValidation['valid']
        ) {
            http_response_code(422);

            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $questions,
                'error' => $validation['error']
                    ?? $selectionValidation['error'],
                'oldName' => $input['name'],
                'oldStartTime' => $input['start_time'],
                'oldEndTime' => $input['end_time'],
                'oldShuffle' => $input['shuffle']
            ]);
        }

        $examData = $validation['data'];
        $validatedQuestions = $selectionValidation['questions'];

        $examId = $this->examModel->createWithQuestions(
            $courseId,
            $examData,
            $validatedQuestions
        );

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
            ->hasAnyStudentAttempt($examId);

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

        if (!$this->examResultModel->canCreateAttempt(
            $examId,
            $teacherId
        )) {
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

        if($_SERVER['REQUEST_METHOD'] === 'GET'){
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

        if ($this->examResultModel->getByExamAndStudent(
            $examId,
            $teacherId
        ) !== null) {
            http_response_code(403);
            exit;
        }

        $submittedAnswers = $_POST['answers'] ?? [];

        $questions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        try {
            $this->examResultModel->submitAttempt(
                $examId,
                $teacherId,
                $questions,
                $submittedAnswers
            );
        } catch (\Throwable $exception) {
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

        if (!$this->examModel->canModify(
            $exam,
            $this->examResultModel->hasAnyStudentAttempt($examId)
        )) {
            http_response_code(403);

            return new ViewModel('errors/403');
        }

        $course = $this->courseModel->getById($courseId);
        $questions = $this->questionModel->getByCourseId($courseId);
        $selectedQuestions = $this->examQuestionModel
            ->getQuestionsByExam($examId);

        if($_SERVER['REQUEST_METHOD'] === 'GET'){
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

        $input = [
            'name' => trim($_POST['exam_name'] ?? ''),
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'shuffle' => isset($_POST['shuffle_questions'])
        ];

        $validation = $this->examModel->validate($input);

        $submittedQuestions = $_POST['questions'] ?? [];
        $courseQuestions = $this->questionModel
            ->getByCourseId($courseId);
        $selectionValidation = $this->examQuestionModel
            ->validateSelection(
                $courseId,
                $submittedQuestions,
                $this->questionModel
            );

        if (
            !$validation['valid']
            || !$selectionValidation['valid']
        ) {
            http_response_code(422);

            return new ViewModel('teacher/exams/create', [
                'course' => $course,
                'questions' => $courseQuestions,
                'exam' => $exam,
                'selectedQuestions' => $this->examQuestionModel
                    ->getQuestionsByExam($examId),
                'error' => $validation['error']
                    ?? $selectionValidation['error'],
                'oldName' => $input['name'],
                'oldStartTime' => $input['start_time'],
                'oldEndTime' => $input['end_time'],
                'oldShuffle' => $input['shuffle']
            ]);
        }

        $examData = $validation['data'];
        $validatedQuestions = $selectionValidation['questions'];

        $this->examModel->updateWithQuestions(
            $examId,
            $examData,
            $validatedQuestions
        );

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

        if (!$this->examModel->canModify(
            $exam,
            $this->examResultModel->hasAnyStudentAttempt($examId)
        )) {
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
