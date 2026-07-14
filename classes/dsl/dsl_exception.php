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
 * Exception class for DSL interpreter errors.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\dsl;

/**
 * Exception thrown when DSL interpretation fails.
 *
 * Provides detailed error information for debugging DSL action execution.
 */
class dsl_exception extends \moodle_exception {
    /** @var string The action type that failed. */
    protected string $actiontype;

    /** @var array Additional context about the failure. */
    protected array $context;

    /**
     * Constructor.
     *
     * @param string $message Human-readable error message.
     * @param string $actiontype The DSL action type that failed (e.g., 'create_module').
     * @param array $context Additional debugging context.
     * @param \Throwable|null $previous Previous exception for chaining.
     */
    public function __construct(
        string $message,
        string $actiontype = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->actiontype = $actiontype;
        $this->context = $context;

        parent::__construct('dsl_error', 'local_dixeo', '', $message, $message);

        // Chain the previous exception if provided.
        if ($previous !== null) {
            // Store previous exception info in context for debugging.
            $this->context['previous_message'] = $previous->getMessage();
            $this->context['previous_class'] = get_class($previous);
        }
    }

    /**
     * Get the action type that failed.
     *
     * @return string The action type.
     */
    public function get_action_type(): string {
        return $this->actiontype;
    }

    /**
     * Get the additional context.
     *
     * @return array The context array.
     */
    public function get_context(): array {
        return $this->context;
    }

    /**
     * Create exception for invalid field source.
     *
     * @param string $source The invalid source string.
     * @param string $field The field name.
     * @return self
     */
    public static function invalid_source(string $source, string $field): self {
        return new self(
            "Invalid source format '$source' for field '$field'",
            'value_resolution',
            ['source' => $source, 'field' => $field]
        );
    }

    /**
     * Create exception for missing variable reference.
     *
     * @param string $varname The missing variable name.
     * @return self
     */
    public static function missing_variable(string $varname): self {
        return new self(
            "Variable '$varname' is not defined",
            'value_resolution',
            ['variable' => $varname]
        );
    }

    /**
     * Create exception for missing context key.
     *
     * @param string $key The missing context key.
     * @return self
     */
    public static function missing_context(string $key): self {
        return new self(
            "Context key '$key' is not defined",
            'value_resolution',
            ['key' => $key]
        );
    }

    /**
     * Create exception for invalid data path.
     *
     * @param string $path The invalid path.
     * @param array $data The data being traversed.
     * @return self
     */
    public static function invalid_path(string $path, array $data): self {
        return new self(
            "Path '$path' does not exist in data",
            'value_resolution',
            ['path' => $path, 'available_keys' => array_keys($data)]
        );
    }

    /**
     * Create exception for unknown action type.
     *
     * @param string $actiontype The unknown action type.
     * @return self
     */
    public static function unknown_action(string $actiontype): self {
        return new self(
            "Unknown action type '$actiontype'",
            $actiontype,
            ['action' => $actiontype]
        );
    }

    /**
     * Create exception for module creation failure.
     *
     * @param string $modulename The module type.
     * @param string $reason The failure reason.
     * @return self
     */
    public static function module_creation_failed(string $modulename, string $reason): self {
        return new self(
            "Failed to create module '$modulename': $reason",
            'create_module',
            ['module' => $modulename, 'reason' => $reason]
        );
    }
}
