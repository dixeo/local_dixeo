<?php
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

defined('MOODLE_INTERNAL') || die();

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
