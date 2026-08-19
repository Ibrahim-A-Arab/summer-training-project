<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class StudentAnswer
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByResult(int $resultId): array
    {
        return $this->db->select(
            'SELECT sa.id,
                    sa.exam_results_id,
                    sa.question_id,
                    sa.choice_id,
                    c.choice_text,
                    c.is_correct
            FROM student_answers sa
            JOIN choices c ON c.id = sa.choice_id
            WHERE sa.exam_results_id = :result_id
            ORDER BY sa.question_id',
            ['result_id' => $resultId]
        );
    }

    public function getByResultAndQuestion(
        int $resultId,
        int $questionId
    ): array {
        return $this->db->select(
            'SELECT sa.id,
                    sa.exam_results_id,
                    sa.question_id,
                    sa.choice_id,
                    c.choice_text
            FROM student_answers sa
            JOIN choices c ON c.id = sa.choice_id
            WHERE sa.exam_results_id = :result_id
                AND sa.question_id = :question_id',
            [
                'result_id' => $resultId,
                'question_id' => $questionId
            ]
        );
    }

    public function create(
    int $resultId,
    int $questionId,
    int $choiceId
): ?int {
    $affectedRows = $this->db->executeAffected(
        'INSERT INTO student_answers
            (exam_results_id, question_id, choice_id)

        SELECT er.id, eq.question_id, c.id

        FROM exam_results er

        JOIN exam_questions eq
            ON eq.exam_id = er.exam_id
            AND eq.question_id = :question_id

        JOIN choices c
            ON c.question_id = eq.question_id
            AND c.id = :choice_id

        WHERE er.id = :result_id
            AND er.submitted_at IS NULL',
        [
            'result_id' => $resultId,
            'question_id' => $questionId,
            'choice_id' => $choiceId
        ]
    );

    if ($affectedRows !== 1) {
        return null;
    }

    return $this->db->lastInsertId();
}

    public function deleteForQuestion(
        int $resultId,
        int $questionId
    ): bool {
        return $this->db->execute(
            'DELETE FROM student_answers
            WHERE exam_results_id = :result_id
                AND question_id = :question_id',
            [
                'result_id' => $resultId,
                'question_id' => $questionId
            ]
        );
    }

    public function isCorrect(
        int $resultId,
        int $questionId
    ): bool {
        $rows = $this->db->select(
            'SELECT COUNT(*) AS incorrect_count
            FROM student_answers sa
            JOIN choices c ON c.id = sa.choice_id
            WHERE sa.exam_results_id = :result_id
                AND sa.question_id = :question_id
                AND c.is_correct = 0',
            [
                'result_id' => $resultId,
                'question_id' => $questionId
            ]
        );

        $answers = $this->getByResultAndQuestion(
            $resultId,
            $questionId
        );

        return $answers !== []
            && (int) $rows[0]['incorrect_count'] === 0;
    }
}