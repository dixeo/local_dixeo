<?php
/**
 * Helper service for HTML processing operations.
 *
 * Provides utilities for cleaning HTML, truncating content, and
 * converting HTML to plain text for AI context.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

/**
 * Helper class for HTML processing operations.
 */
class html_helper {

    /** @var int Maximum context size in bytes (500KB). */
    public const MAX_CONTEXT_SIZE = 512000;

    /** @var int Default preview length for module content. */
    public const PREVIEW_LENGTH = 500;

    /** @var int Default excerpt length for sibling modules. */
    public const EXCERPT_LENGTH = 200;

    /** @var int Truncation boundary search limit from end. */
    private const TRUNCATION_BOUNDARY_SEARCH = 500;

    /** @var int Truncation suffix space reserved. */
    private const TRUNCATION_SUFFIX_SPACE = 50;

    /**
     * Clean HTML content and convert to plain text.
     *
     * Strips HTML tags and normalizes whitespace for AI processing.
     *
     * @param string $html The HTML content to clean.
     * @return string Cleaned plain text.
     */
    public function clean_html(string $html): string {
        $text = html_to_text($html, 0, false);

        // Normalize whitespace to single spaces.
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Truncate context string to maximum allowed size.
     *
     * Attempts to truncate at a newline boundary for cleaner output.
     *
     * @param string $context The context string to truncate.
     * @param int $maxsize Maximum size in bytes (defaults to MAX_CONTEXT_SIZE).
     * @return string The truncated context with indicator if truncated.
     */
    public function truncate_context(string $context, int $maxsize = self::MAX_CONTEXT_SIZE): string {
        if (strlen($context) <= $maxsize) {
            return $context;
        }

        $truncated = substr($context, 0, $maxsize - self::TRUNCATION_SUFFIX_SPACE);

        // Try to truncate at a reasonable boundary (newline).
        $lastnewline = strrpos($truncated, "\n");
        $boundaryLimit = $maxsize - self::TRUNCATION_BOUNDARY_SEARCH;

        if ($lastnewline !== false && $lastnewline > $boundaryLimit) {
            $truncated = substr($truncated, 0, $lastnewline);
        }

        return $truncated . "\n\n[Context truncated due to size limit]";
    }

    /**
     * Truncate text to a maximum length with ellipsis.
     *
     * Used for previews and excerpts.
     *
     * @param string $text The text to truncate.
     * @param int $maxlength Maximum length in characters.
     * @return string The truncated text with ellipsis if truncated.
     */
    public function truncate_text(string $text, int $maxlength): string {
        if (strlen($text) <= $maxlength) {
            return $text;
        }

        return substr($text, 0, $maxlength - 3) . '...';
    }
}
