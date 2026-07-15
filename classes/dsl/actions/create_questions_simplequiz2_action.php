<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * DSL action for creating simplequiz2 questions.
 *
 * Transforms AI-generated questions into SimpleQuiz JSON format and
 * updates the simplequiz2 module record. Unlike standard quiz questions,
 * SimpleQuiz stores questions as JSON in a single field rather than
 * using Moodle's question bank.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\dsl\actions;

use local_dixeo\dsl\dsl_exception;
use local_dixeo\dsl\value_resolver;

/**
 * Action handler for creating simplequiz2 questions.
 *
 * SimpleQuiz stores questions differently from standard Moodle quizzes:
 * - Questions are stored as JSON in the 'questions' field of simplequiz2 table
 * - Format: { "0": { "text": "...", "answers": [{ "text": "...", "iscorrect": 0/1 }] } }
 *
 * Expected action format:
 * {
 *   "action": "create_questions",
 *   "module_ref": "$module",
 *   "foreach": "$.questions",
 *   "fields": {
 *     "questiontext": {"source": "$.text"},
 *     "options": {"source": "$.options"},
 *     "correct_answer": {"source": "$.answer"}
 *   }
 * }
 *
 * API format (input):
 * {
 *   "text": "What is a cell?",
 *   "options": ["Basic unit of life", "An organism", "A tissue"],
 *   "answer": 0
 * }
 *
 * SimpleQuiz format (output):
 * {
 *   "text": "What is a cell?",
 *   "answers": [
 *     {"text": "Basic unit of life", "iscorrect": 1},
 *     {"text": "An organism", "iscorrect": 0},
 *     {"text": "A tissue", "iscorrect": 0}
 *   ]
 * }
 */
class create_questions_simplequiz2_action {
    use action_validation;

    /**
     * Execute the create_questions action for simplequiz2.
     *
     * @param array $action The action specification.
     * @param value_resolver $resolver The value resolver.
     * @return array Array with 'updated' status and question count.
     * @throws dsl_exception If creation fails.
     */
    public function execute(array $action, value_resolver $resolver): array {
        global $DB;

        $this->validate_action($action);

        // Resolve the module reference to get simplequiz2 info.
        $moduleref = $action['module_ref'];
        $moduledata = $resolver->resolve_source($moduleref, 'module_ref');

        if (!is_array($moduledata) || !isset($moduledata['id'])) {
            throw new dsl_exception(
                "module_ref did not resolve to valid module data",
                'create_questions_simplequiz2',
                ['module_ref' => $moduleref]
            );
        }

        $simplequiz2id = (int) $moduledata['id'];

        // Resolve the questions collection.
        $foreachpath = $action['foreach'];
        $questions = $resolver->resolve_source($foreachpath, 'foreach');

        if (!is_array($questions)) {
            throw new dsl_exception(
                "foreach path '$foreachpath' did not resolve to an array",
                'create_questions_simplequiz2',
                ['path' => $foreachpath]
            );
        }

        $fieldsspec = $action['fields'] ?? [];
        $simplequiz2questions = [];

        foreach ($questions as $index => $questionitem) {
            $itemdata = is_object($questionitem) ? (array) $questionitem : $questionitem;

            if (!is_array($itemdata)) {
                throw new dsl_exception(
                    "Question at index $index is not an array or object",
                    'create_questions_simplequiz2',
                    ['index' => $index]
                );
            }

            // Create resolver with this question's data for per-item field resolution.
            $itemresolver = $resolver->with_ai_data($itemdata);
            $resolvedfields = $itemresolver->resolve_fields($fieldsspec);

            // Transform API format to SimpleQuiz format.
            $simplequiz2questions[$index] = $this->transform_question($resolvedfields);
        }

        // Update the simplequiz2 record with JSON-encoded questions.
        $record = new \stdClass();
        $record->id = $simplequiz2id;
        $record->questions = json_encode($simplequiz2questions);
        $record->timemodified = time();

        $DB->update_record('simplequiz2', $record);

        return [
            'updated' => true,
            'question_count' => count($simplequiz2questions),
        ];
    }

    /**
     * Transform API question format to SimpleQuiz format.
     *
     * API format:
     * - questiontext: "Question text"
     * - options: ["Answer A", "Answer B", "Answer C"]
     * - correct_answer: 0 (0-based index of correct answer)
     *
     * SimpleQuiz format:
     * - text: "Question text"
     * - answers: [{ text: "Answer A", iscorrect: 1 }, { text: "Answer B", iscorrect: 0 }]
     *
     * @param array $fields The resolved field values.
     * @return \stdClass The question in SimpleQuiz format.
     * @throws dsl_exception If required fields are missing.
     */
    protected function transform_question(array $fields): \stdClass {
        $questiontext = $fields['questiontext'] ?? '';
        $options = $fields['options'] ?? [];
        $correctanswer = $fields['correct_answer'] ?? 0;

        if (!is_array($options) || count($options) < 2) {
            throw new dsl_exception(
                'SimpleQuiz question requires at least 2 options',
                'create_questions_simplequiz2',
                ['options_count' => is_array($options) ? count($options) : 0]
            );
        }

        // Ensure correct_answer is an integer.
        $correctindex = is_numeric($correctanswer) ? (int) $correctanswer : 0;

        // Build the question object.
        $question = new \stdClass();
        $question->text = $questiontext;
        $question->answers = [];

        foreach ($options as $index => $optiontext) {
            $answer = new \stdClass();
            $answer->text = is_string($optiontext) ? $optiontext : (string) $optiontext;
            $answer->iscorrect = ($index === $correctindex) ? 1 : 0;
            $question->answers[] = $answer;
        }

        return $question;
    }

    /**
     * Validate the action specification.
     *
     * @param array $action The action to validate.
     * @throws dsl_exception If validation fails.
     */
    protected function validate_action(array $action): void {
        $this->require_action_fields($action, ['module_ref', 'foreach'], 'create_questions_simplequiz2');
    }
}
