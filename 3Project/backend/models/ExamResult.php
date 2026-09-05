<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class ExamResult
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $results = $this->db->select(
            'SELECT id, exam_id, student_id,
                    mark, submitted_at
            FROM exam_results
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $results[0] ?? null;
    }

    public function getByExamAndStudent(
        int $examId,
        int $studentId
    ): ?array {
        $results = $this->db->select(
            'SELECT id, exam_id, student_id,
                    mark, submitted_at
            FROM exam_results
            WHERE exam_id = :exam_id
                AND student_id = :student_id
            LIMIT 1',
            [
                'exam_id' => $examId,
                'student_id' => $studentId
            ]
        );

        return $results[0] ?? null;
    }

    public function getByExam(int $examId): array
    {
        return $this->db->select(
            'SELECT er.id, er.exam_id,
                    er.student_id, u.name AS student_name,
                    er.mark, er.submitted_at
            FROM exam_results er
            JOIN users u ON u.id = er.student_id
            WHERE er.exam_id = :exam_id
            ORDER BY u.name',
            ['exam_id' => $examId]
        );
    }

    public function getStudentResultsByExam(int $examId): array
    {
        return $this->db->select(
            'SELECT er.id,
                    er.exam_id,
                    er.student_id,
                    er.mark,
                    er.submitted_at,
                    u.name AS student_name,
                    u.personal_id
            FROM exam_results er
            JOIN users u ON u.id = er.student_id
            WHERE er.exam_id = :exam_id
                AND u.role = :role
                AND er.submitted_at IS NOT NULL
            ORDER BY u.name',
            [
                'exam_id' => $examId,
                'role' => 'student'
            ]
        );
    }

    public function getByStudent(int $studentId): array
    {
        return $this->db->select(
            'SELECT er.id, er.exam_id,
                    e.exam_name, er.mark, er.submitted_at
            FROM exam_results er
            JOIN exams e ON e.id = er.exam_id
            WHERE er.student_id = :student_id
            ORDER BY e.start_time DESC',
            ['student_id' => $studentId]
        );
    }

    public function create(int $examId, int $studentId): int
    {
        $this->db->execute(
            'INSERT INTO exam_results (exam_id, student_id)
            VALUES (:exam_id, :student_id)',
            [
                'exam_id' => $examId,
                'student_id' => $studentId
            ]
        );

        return $this->db->lastInsertId();
    }

    public function submitAttempt(
        int $examId,
        int $studentId,
        array $questions,
        mixed $submittedAnswers
    ): int {
        $studentAnswerModel = new StudentAnswer();

        try {
            $this->db->beginTransaction();

            if (!$this->canCreateAttempt($examId, $studentId)) {
                throw new \RuntimeException(
                    'An attempt already exists for this exam.'
                );
            }

            $resultId = $this->create($examId, $studentId);

            if (!$studentAnswerModel->saveSubmission(
                $resultId,
                $questions,
                $submittedAnswers
            )) {
                throw new \RuntimeException(
                    'An invalid answer was submitted.'
                );
            }

            $mark = $studentAnswerModel->calculateMark(
                $resultId,
                $questions
            );

            if (!$this->submit($resultId, $mark)) {
                throw new \RuntimeException(
                    'The exam could not be submitted.'
                );
            }

            $this->db->commit();

            return $resultId;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function submit(int $id, float $mark): bool
    {
        if ($mark < 0) {
            return false;
        }

        return $this->db->execute(
            'UPDATE exam_results
            SET mark = :mark,
                submitted_at = NOW()
            WHERE id = :id
                AND submitted_at IS NULL',
            [
                'id' => $id,
                'mark' => $mark
            ]
        );
    }

    public function hasSubmitted(
        int $examId,
        int $studentId
    ): bool {
        return $this->db->select(
            'SELECT id
            FROM exam_results
            WHERE exam_id = :exam_id
                AND student_id = :student_id
                AND submitted_at IS NOT NULL
            LIMIT 1',
            [
                'exam_id' => $examId,
                'student_id' => $studentId
            ]
        ) !== [];
    }

    public function canCreateAttempt(
        int $examId,
        int $userId
    ): bool {
        return $this->getByExamAndStudent($examId, $userId)
            === null;
    }

    public function hasAnyStudentAttempt(int $examId): bool
    {
        return $this->db->select(
            'SELECT er.id
            FROM exam_results er
            JOIN users u ON u.id = er.student_id
            WHERE er.exam_id = :exam_id
                AND u.role = :role
            LIMIT 1',
            [
                'exam_id' => $examId,
                'role' => 'student'
            ]
        ) !== [];
    }
}
