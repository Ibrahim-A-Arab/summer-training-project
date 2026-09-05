<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;

class Choice
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $choices = $this->db->select(
            'SELECT id, question_id, choice_text, is_correct
            FROM choices
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $choices[0] ?? null;
    }

    public function getByQuestionId(int $questionId): array
    {
        return $this->db->select(
            'SELECT id, question_id, choice_text, is_correct
            FROM choices
            WHERE question_id = :question_id
            ORDER BY id',
            ['question_id' => $questionId]
        );
    }

    public function getCorrectByQuestionId(int $questionId): array
    {
        return $this->db->select(
            'SELECT id, question_id, choice_text
            FROM choices
            WHERE question_id = :question_id
            AND is_correct = 1',
            ['question_id' => $questionId]
        );
    }

    public function validateForQuestionType(
        string $questionType,
        mixed $submittedChoices,
        mixed $correctAnswer = null
    ): array {
        if ($questionType === 'TrueOrFalse') {
            if (!in_array(
                $correctAnswer,
                ['true', 'false'],
                true
            )) {
                return [
                    'valid' => false,
                    'error' => 'Select True or False.'
                ];
            }

            return [
                'valid' => true,
                'choices' => [
                    [
                        'text' => 'True',
                        'is_correct' => $correctAnswer === 'true'
                    ],
                    [
                        'text' => 'False',
                        'is_correct' => $correctAnswer === 'false'
                    ]
                ]
            ];
        }

        if ($questionType !== 'MCQ') {
            return [
                'valid' => false,
                'error' => 'Select a valid question type.'
            ];
        }

        if (
            !is_array($submittedChoices)
            || count($submittedChoices) < 2
        ) {
            return [
                'valid' => false,
                'error' => 'At least two choices are required.'
            ];
        }

        $choices = [];
        $hasCorrectChoice = false;

        foreach ($submittedChoices as $choice) {
            if (!is_array($choice)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid choice data.'
                ];
            }

            $choiceText = trim(
                (string) ($choice['text'] ?? '')
            );

            if ($choiceText === '') {
                return [
                    'valid' => false,
                    'error' => 'Choice text cannot be empty.'
                ];
            }

            $isCorrect = isset($choice['correct']);
            $hasCorrectChoice = $hasCorrectChoice || $isCorrect;

            $choices[] = [
                'text' => $choiceText,
                'is_correct' => $isCorrect
            ];
        }

        if (!$hasCorrectChoice) {
            return [
                'valid' => false,
                'error' => 'Select at least one correct choice.'
            ];
        }

        return [
            'valid' => true,
            'choices' => $choices
        ];
    }

    public function create(
        int $questionId,
        string $text,
        bool $isCorrect
    ): int {
        $this->db->execute(
            'INSERT INTO choices
                (question_id, choice_text, is_correct)
            VALUES
                (:question_id, :choice_text, :is_correct)',
            [
                'question_id' => $questionId,
                'choice_text' => $text,
                'is_correct' => (int) $isCorrect
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update(
        int $id,
        string $text,
        bool $isCorrect
    ): bool {
        return $this->db->execute(
            'UPDATE choices
            SET choice_text = :choice_text,
                is_correct = :is_correct
            WHERE id = :id',
            [
                'id' => $id,
                'choice_text' => $text,
                'is_correct' => (int) $isCorrect
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM choices WHERE id = :id',
            ['id' => $id]
        );
    }

    public function deleteByQuestionId(int $questionId): bool
    {
        return $this->db->execute(
            'DELETE FROM choices WHERE question_id = :question_id',
            ['question_id' => $questionId]
        );
    }
}
