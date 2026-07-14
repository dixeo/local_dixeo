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
 * Service for extracting content from Moodle modules.
 *
 * Handles content retrieval from various module types (page, label, book, etc.)
 * and provides both raw and processed content for AI context building.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

/**
 * Service for extracting content from Moodle modules.
 */
class module_content_extractor {
    /** @var html_helper HTML processing helper. */
    private html_helper $htmlhelper;

    /**
     * Constructor.
     *
     * @param html_helper|null $htmlhelper Optional HTML helper (creates new if not provided).
     */
    public function __construct(?html_helper $htmlhelper = null) {
        $this->htmlhelper = $htmlhelper ?? new html_helper();
    }

    /**
     * Get raw content from a module based on its type.
     *
     * Returns the HTML content as stored in the database.
     *
     * @param \cm_info $cm The course module info.
     * @return string|null The raw HTML content, or null if not applicable.
     */
    public function get_raw_content(\cm_info $cm): ?string {
        global $DB;

        return match ($cm->modname) {
            'page' => $this->get_page_full_content($cm->instance),
            'label' => $DB->get_field('label', 'intro', ['id' => $cm->instance]),
            'book' => $this->get_book_content($cm->instance),
            'url' => $DB->get_field('url', 'intro', ['id' => $cm->instance]),
            'resource' => $DB->get_field('resource', 'intro', ['id' => $cm->instance]),
            default => null,
        };
    }

    /**
     * Get module content preview (truncated and cleaned).
     *
     * Used for displaying module content in context summaries.
     *
     * @param \cm_info $cm The course module info.
     * @param int $maxlength Maximum preview length.
     * @return string|null The content preview, or null if not available.
     */
    public function get_preview(\cm_info $cm, int $maxlength = html_helper::PREVIEW_LENGTH): ?string {
        $content = $this->get_raw_content($cm);

        if ($content === null) {
            return null;
        }

        $cleaned = $this->htmlhelper->clean_html($content);

        return $this->htmlhelper->truncate_text($cleaned, $maxlength);
    }

    /**
     * Get module excerpt for sibling context.
     *
     * Shorter than preview, used for adjacent module descriptions.
     *
     * @param \cm_info $cm The course module info.
     * @param int $maxlength Maximum excerpt length.
     * @return string|null The excerpt, or null if content unavailable.
     */
    public function get_excerpt(\cm_info $cm, int $maxlength = html_helper::EXCERPT_LENGTH): ?string {
        $content = $this->get_raw_content($cm);

        if ($content === null) {
            return null;
        }

        $cleaned = $this->htmlhelper->clean_html($content);

        return $this->htmlhelper->truncate_text($cleaned, $maxlength);
    }

    /**
     * Get full module content cleaned for AI processing.
     *
     * HTML is converted to plain text for generation operations.
     *
     * @param \cm_info $cm The course module info.
     * @return string|null The full cleaned content, or null if not available.
     */
    public function get_full_content(\cm_info $cm): ?string {
        $content = $this->get_raw_content($cm);

        if ($content === null) {
            return null;
        }

        return $this->htmlhelper->clean_html($content);
    }

    /**
     * Get full module content for edit operations.
     *
     * Preserves HTML structure for editing, with special handling for page modules.
     * When $autosavedrafthtml is non-empty after trim, it replaces the in-editor body from Tiny autosave;
     * for page modules the introduction stays loaded from the database and only the main content uses the draft.
     *
     * @param \cm_info $cm The course module info.
     * @param string|null $autosavedrafthtml Optional HTML from tiny_autosave.
     * @return string|null The full content for editing, or null if not available.
     */
    public function get_full_content_for_edit(\cm_info $cm, ?string $autosavedrafthtml = null): ?string {
        $draft = $autosavedrafthtml !== null ? trim($autosavedrafthtml) : '';
        $usedraft = $draft !== '';

        if ($cm->modname === 'page') {
            return $usedraft
                ? $this->get_page_full_content_with_content_override($cm->instance, $draft)
                : $this->get_page_full_content($cm->instance);
        }

        if ($usedraft) {
            return $draft;
        }

        return $this->get_raw_content($cm);
    }

    /**
     * Get full content from a page module.
     *
     * Includes both intro and content fields with labels.
     * Returns raw HTML - cleaning is done by the caller if needed.
     *
     * @param int $pageid The page instance ID.
     * @return string|null The full page content as raw HTML, or null if not found.
     */
    private function get_page_full_content(int $pageid): ?string {
        global $DB;

        $page = $DB->get_record('page', ['id' => $pageid], 'intro, content');

        if (!$page) {
            return null;
        }

        $result = '';

        if (!empty($page->intro)) {
            $result .= "**Introduction:**\n" . $page->intro . "\n\n";
        }

        if (!empty($page->content)) {
            $result .= "**Content:**\n" . $page->content;
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Page intro from DB plus content body from autosave draft (Dixeo editor edits content only).
     *
     * @param int $pageid Page instance id.
     * @param string $contentdraft HTML for the content field.
     * @return string|null
     */
    private function get_page_full_content_with_content_override(int $pageid, string $contentdraft): ?string {
        global $DB;

        $page = $DB->get_record('page', ['id' => $pageid], 'intro, content', IGNORE_MISSING);
        if (!$page) {
            return null;
        }

        $result = '';
        if (!empty($page->intro)) {
            $result .= "**Introduction:**\n" . $page->intro . "\n\n";
        }
        if ($contentdraft !== '') {
            $result .= "**Content:**\n" . $contentdraft;
        }

        return $result !== '' ? $result : null;
    }

    /**
     * Get content from a book module.
     *
     * Returns intro and first few chapters.
     *
     * @param int $bookid The book instance ID.
     * @return string|null The book content preview.
     */
    private function get_book_content(int $bookid): ?string {
        global $DB;

        $book = $DB->get_record('book', ['id' => $bookid]);

        if (!$book) {
            return null;
        }

        // Limit to first 3 chapters to avoid excessive context.
        $chapters = $DB->get_records(
            'book_chapters',
            ['bookid' => $bookid],
            'pagenum ASC',
            'id,title,content',
            0,
            3
        );

        if (empty($chapters)) {
            return $book->intro;
        }

        $content = $book->intro . "\n\n";

        foreach ($chapters as $chapter) {
            $content .= "## {$chapter->title}\n{$chapter->content}\n\n";
        }

        return $content;
    }
}
