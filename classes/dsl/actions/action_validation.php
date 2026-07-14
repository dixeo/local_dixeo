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
 * Trait for common DSL action validation.
 *
 * Provides a reusable field validation helper for DSL action classes,
 * reducing duplication of the validate_action() pattern.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\dsl\actions;

use local_dixeo\dsl\dsl_exception;

/**
 * Trait providing common action field validation.
 */
trait action_validation {

    /**
     * Validate that required fields are present in the action specification.
     *
     * @param array $action The action to validate.
     * @param array $requiredfields List of required field names.
     * @param string $actionname The action name for error messages.
     * @throws dsl_exception If any required field is missing.
     */
    protected function require_action_fields(array $action, array $requiredfields, string $actionname): void {
        foreach ($requiredfields as $field) {
            if (!isset($action[$field])) {
                throw new dsl_exception(
                    "{$actionname} action requires '{$field}' field",
                    $actionname,
                    ['action' => $action]
                );
            }
        }
    }
}
