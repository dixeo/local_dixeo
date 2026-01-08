<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\api\exception;

/**
 * Exception for validation errors (HTTP 400).
 *
 * Thrown when the API request contains invalid input data.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validation_exception extends api_exception {

    /** @var array List of validation violations. */
    protected array $violations;

    /**
     * Constructor.
     *
     * @param string $message Human-readable error message.
     * @param array $violations List of validation violations.
     * @param array $details Additional error details.
     */
    public function __construct(
        string $message = 'Validation error',
        array $violations = [],
        array $details = []
    ) {
        $this->violations = $violations;
        parent::__construct('validation_error', $message, 400, $details);
    }

    /**
     * Get the validation violations.
     *
     * @return array List of violations, each containing 'field' and 'message'.
     */
    public function get_violations(): array {
        return $this->violations;
    }

    /**
     * Get a formatted string of all violations.
     *
     * @return string Human-readable list of violations.
     */
    public function get_violations_string(): string {
        $messages = [];
        foreach ($this->violations as $violation) {
            $field = $violation['field'] ?? 'unknown';
            $message = $violation['message'] ?? 'Invalid value';
            $messages[] = "{$field}: {$message}";
        }
        return implode('; ', $messages);
    }
}
