<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Exam
{
    private Database $db;   

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            ORDER BY start_time DESC'
        );
    }

    public function getById(int $id): ?array
    {
        $exams = $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $exams[0] ?? null;
    }

    public function getByCourseId(int $courseId): array// exams of a course
    {
        return $this->db->select(
            'SELECT id, course_id, exam_name,
                    start_time, end_time, shuffle_questions
            FROM exams
            WHERE course_id = :course_id
            ORDER BY start_time DESC',
            ['course_id' => $courseId]
        );
    }

    public function getAvailableForStudent(int $studentId): array
    {
        return $this->db->select(
            'SELECT e.id, e.course_id, e.exam_name,
                    e.start_time, e.end_time,
                    e.shuffle_questions
            FROM exams e
            JOIN course_students cs
                ON cs.course_id = e.course_id
            WHERE cs.student_id = :student_id
            AND NOW() BETWEEN e.start_time AND e.end_time
            ORDER BY e.end_time',
            ['student_id' => $studentId]
        );
    }

    public function create(
        int $courseId,
        string $name,
        string $startTime,
        string $endTime,
        bool $shuffle
    ): int {
        $this->db->execute(
            'INSERT INTO exams
                (course_id, exam_name, start_time,
                end_time, shuffle_questions)
            VALUES
                (:course_id, :exam_name, :start_time,
                :end_time, :shuffle)',
            [
                'course_id' => $courseId,
                'exam_name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'shuffle' => (int) $shuffle
            ]
        );

        return $this->db->lastInsertId();
    }

    public function createWithQuestions(
        int $courseId,
        array $examData,
        array $questions
    ): int {
        $examQuestionModel = new ExamQuestion();

        try {
            $this->db->beginTransaction();

            $examId = $this->create(
                $courseId,
                $examData['name'],
                $examData['start']->format('Y-m-d H:i:s'),
                $examData['end']->format('Y-m-d H:i:s'),
                $examData['shuffle']
            );

            foreach ($questions as $question) {
                if (!$examQuestionModel->addQuestion(
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

            $this->db->commit();

            return $examId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function update(
        int $id,
        string $name,
        string $startTime,
        string $endTime,
        bool $shuffle
    ): bool {
        return $this->db->execute(
            'UPDATE exams
            SET exam_name = :exam_name,
                start_time = :start_time,
                end_time = :end_time,
                shuffle_questions = :shuffle
            WHERE id = :id',
            [
                'id' => $id,
                'exam_name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'shuffle' => (int) $shuffle
            ]
        );
    }

    public function updateWithQuestions(
        int $id,
        array $examData,
        array $questions
    ): bool {
        $examQuestionModel = new ExamQuestion();

        try {
            $this->db->beginTransaction();

            if (!$this->update(
                $id,
                $examData['name'],
                $examData['start']->format('Y-m-d H:i:s'),
                $examData['end']->format('Y-m-d H:i:s'),
                $examData['shuffle']
            )) {
                throw new \RuntimeException('Could not update exam.');
            }

            if (!$examQuestionModel->removeAllByExam($id)) {
                throw new \RuntimeException(
                    'Could not replace exam questions.'
                );
            }

            foreach ($questions as $question) {
                if (!$examQuestionModel->addQuestion(
                    $id,
                    $question['id'],
                    $question['position'],
                    $question['weight']
                )) {
                    throw new \RuntimeException(
                        'Could not update the exam questions.'
                    );
                }
            }

            $this->db->commit();

            return true;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM exams WHERE id = :id',
            ['id' => $id]
        );
    }

    public function validate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $startInput = (string) ($data['start_time'] ?? '');
        $endInput = (string) ($data['end_time'] ?? '');

        if (
            $name === ''
            || $startInput === ''
            || $endInput === ''
        ) {
            return [
                'valid' => false,
                'error' => 'Complete all exam information.'
            ];
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

        if ($start === false || $end === false) {
            return [
                'valid' => false,
                'error' => 'Enter valid start and end times.'
            ];
        }

        if ($end <= $start) {
            return [
                'valid' => false,
                'error' => 'End time must be after start time.'
            ];
        }

        $now = new \DateTimeImmutable('now', $timezone);

        if ($start <= $now) {
            return [
                'valid' => false,
                'error' => 'Start time must be in the future.'
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'name' => $name,
                'start' => $start,
                'end' => $end,
                'shuffle' => (bool) ($data['shuffle'] ?? false)
            ]
        ];
    }

    public function getStatus(array $exam): string
    {
        $now = new \DateTimeImmutable();
        $start = new \DateTimeImmutable($exam['start_time']);
        $end = new \DateTimeImmutable($exam['end_time']);

        if ($now < $start) {
            return 'upcoming';
        }

        return $now <= $end ? 'available' : 'ended';
    }

    public function isAvailable(array $exam): bool
    {
        return $this->getStatus($exam) === 'available';
    }

    public function canModify(array $exam, bool $hasAttempts): bool
    {
        return !$hasAttempts
            && $this->getStatus($exam) === 'upcoming';
    }
}
