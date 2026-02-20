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

        // Strip invalid UTF-8 byte sequences to prevent json_encode() failures
        // when database content contains non-UTF-8 characters (e.g. from Word copy-paste).
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

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
        if ($this->byte_length($context) <= $maxsize) {
            return $context;
        }

        $truncateAt = $maxsize - self::TRUNCATION_SUFFIX_SPACE;
        $truncated = $this->utf8_strcut($context, 0, $truncateAt);

        // Try to truncate at a reasonable boundary (newline).
        $lastnewline = $this->utf8_strrpos($truncated, "\n");
        $boundaryLimit = $maxsize - self::TRUNCATION_BOUNDARY_SEARCH;

        if ($lastnewline !== false && $lastnewline > $boundaryLimit) {
            $truncated = $this->utf8_substr($truncated, 0, $lastnewline);
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
        if ($this->utf8_length($text) <= $maxlength) {
            return $text;
        }

        return $this->utf8_substr($text, 0, $maxlength - 3) . '...';
    }

    private function byte_length(string $text): int {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, '8bit');
        }

        return strlen($text);
    }

    private function utf8_length(string $text): int {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8');
        }

        return strlen($text);
    }

    private function utf8_substr(string $text, int $start, ?int $length = null): string {
        if (function_exists('mb_substr')) {
            if ($length === null) {
                return mb_substr($text, $start, null, 'UTF-8');
            }

            return mb_substr($text, $start, $length, 'UTF-8');
        }

        if ($length === null) {
            return substr($text, $start);
        }

        return substr($text, $start, $length);
    }

    private function utf8_strcut(string $text, int $start, int $length): string {
        if (function_exists('mb_strcut')) {
            return mb_strcut($text, $start, $length, 'UTF-8');
        }

        return substr($text, $start, $length);
    }

    private function utf8_strrpos(string $text, string $needle): int|false {
        if (function_exists('mb_strrpos')) {
            return mb_strrpos($text, $needle, 0, 'UTF-8');
        }

        return strrpos($text, $needle);
    }
}
