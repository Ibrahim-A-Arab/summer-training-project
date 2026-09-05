<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class ExamQuestion
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function addQuestion(
        int $examId,
        int $questionId,
        int $position,
        float $weight
    ): bool {

        if ($weight <= 0 || $weight > 100) {
            return false;
        }

        return $this->db->execute(
            'INSERT INTO exam_questions
                (exam_id, question_id, position, weight)
            VALUES
                (:exam_id, :question_id, :position, :weight)',
            [
                'exam_id' => $examId,
                'question_id' => $questionId,
                'position' => $position,
                'weight' => $weight
            ]
        );
    }

    public function removeQuestion(
        int $examId,
        int $questionId
    ): bool {
        return $this->db->execute(
            'DELETE FROM exam_questions
            WHERE exam_id = :exam_id
                AND question_id = :question_id',
            [
                'exam_id' => $examId,
                'question_id' => $questionId
            ]
        );
    }

    public function validateSelection(
        int $courseId,
        mixed $submittedQuestions,
        Question $questionModel
    ): array {
        if (
            !is_array($submittedQuestions)
            || $submittedQuestions === []
        ) {
            return [
                'valid' => false,
                'error' => 'Add at least one question.'
            ];
        }

        $questions = [];
        $position = 1;

        foreach ($submittedQuestions as $questionId => $data) {
            if (!is_array($data)) {
                return [
                    'valid' => false,
                    'error' => 'One or more questions are invalid.'
                ];
            }

            $questionId = (int) $questionId;
            $weight = (float) ($data['weight'] ?? 0);
            $question = $questionModel->getById($questionId);

            if (
                $question === null
                || (int) $question['course_id'] !== $courseId
                || $weight <= 0
                || $weight > 100
            ) {
                return [
                    'valid' => false,
                    'error' => 'One or more questions are invalid.'
                ];
            }

            $questions[] = [
                'id' => $questionId,
                'weight' => $weight,
                'position' => $position++
            ];
        }

        return [
            'valid' => true,
            'questions' => $questions
        ];
    }

    public function removeAllByExam(int $examId): bool
    {
        return $this->db->execute(
            'DELETE FROM exam_questions
            WHERE exam_id = :exam_id',
            ['exam_id' => $examId]
        );
    }

    public function getQuestionsByExam(int $examId): array
    {
        return $this->db->select(
            'SELECT q.id,
                    q.course_id,
                    q.question_text,
                    q.question_type,
                    eq.weight,
                    eq.position
            FROM exam_questions eq
            JOIN questions q
                ON q.id = eq.question_id
            WHERE eq.exam_id = :exam_id
            ORDER BY eq.position',
            ['exam_id' => $examId]
        );
    }

    public function updatePosition(
        int $examId,
        int $questionId,
        int $position
    ): bool {
        return $this->db->execute(
            'UPDATE exam_questions
            SET position = :position
            WHERE exam_id = :exam_id
                AND question_id = :question_id',
            [
                'exam_id' => $examId,
                'question_id' => $questionId,
                'position' => $position
            ]
        );
    }

    public function containsQuestion(
        int $examId,
        int $questionId
    ): bool {
        return $this->db->select(
            'SELECT id
            FROM exam_questions
            WHERE exam_id = :exam_id
                AND question_id = :question_id
            LIMIT 1',
            [
                'exam_id' => $examId,
                'question_id' => $questionId
            ]
        ) !== [];
    }

    public function updateWeight(
        int $examId,
        int $questionId,
        float $weight
    ): bool {

        if ($weight <= 0 || $weight > 100) {
            return false;
        }
        
        return $this->db->execute(
            'UPDATE exam_questions
            SET weight = :weight
            WHERE exam_id = :exam_id
            AND question_id = :question_id',
            [
                'exam_id' => $examId,
                'question_id' => $questionId,
                'weight' => $weight
            ]
        );
    }
}
