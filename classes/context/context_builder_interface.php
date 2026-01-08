<?php
/**
 * Interface for context builders that generate markdown for AI processing.
 *
 * Defines the contract for all context builder implementations.
 * Each builder is responsible for constructing markdown context
 * appropriate for its specific use case (course, section, module, etc.).
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\context;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for context builders.
 */
interface context_builder_interface {

    /**
     * Build and return the markdown context.
     *
     * @return string Markdown-formatted context ready for AI processing.
     */
    public function build(): string;
}
