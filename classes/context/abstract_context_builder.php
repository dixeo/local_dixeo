<?php
/**
 * Abstract base class for context builders with shared helper methods.
 *
 * Provides common functionality used across all context builder implementations:
 * - Module visibility/accessibility checks
 * - Course metadata formatting
 * - Section name resolution
 * - Adjacent module discovery
 * - Category path resolution using Moodle's built-in API
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\context;

defined('MOODLE_INTERNAL') || die();

use local_dixeo\service\html_helper;
use local_dixeo\service\module_content_extractor;

/**
 * Abstract base class providing shared context building utilities.
 */
abstract class abstract_context_builder implements context_builder_interface {

    /** @var html_helper HTML processing helper. */
    protected html_helper $htmlHelper;

    /** @var module_content_extractor Module content extractor. */
    protected module_content_extractor $contentExtractor;

    /**
     * Constructor with optional dependency injection.
     *
     * @param html_helper|null $htmlHelper Optional HTML helper.
     * @param module_content_extractor|null $contentExtractor Optional content extractor.
     */
    public function __construct(
        ?html_helper $htmlHelper = null,
        ?module_content_extractor $contentExtractor = null
    ) {
        $this->htmlHelper = $htmlHelper ?? new html_helper();
        $this->contentExtractor = $contentExtractor ?? new module_content_extractor($this->htmlHelper);
    }

    /**
     * Check if a module is accessible (visible and available to users).
     *
     * Provides consistent visibility filtering across all context builders.
     *
     * @param \cm_info $cm The course module info.
     * @return bool True if module should be included in context.
     */
    protected function is_module_accessible(\cm_info $cm): bool {
        return $cm->visible && $cm->available;
    }

    /**
     * Filter modules to only include accessible ones.
     *
     * @param array $modules Array of cm_info objects.
     * @return array Filtered array of accessible modules.
     */
    protected function get_accessible_modules(array $modules): array {
        return array_filter($modules, fn($cm) => $this->is_module_accessible($cm));
    }

    /**
     * Get section name using the course format's naming convention.
     *
     * Falls back to format-specific default if no custom name is set.
     *
     * @param object $course The course object.
     * @param object $section The section object or section_info.
     * @return string The formatted section name.
     */
    protected function get_section_name(object $course, object $section): string {
        if (!empty($section->name)) {
            return format_string($section->name);
        }

        $format = course_get_format($course);

        return $format->get_section_name($section);
    }

    /**
     * Get course category path using Moodle's built-in API.
     *
     * Uses core_course_category to avoid manual DB queries in loops.
     *
     * @param object $course The course object (must have 'category' property).
     * @return string Category path (e.g., "Science > Life Sciences") or "Unknown".
     */
    protected function get_course_category_path(object $course): string {
        $category = \core_course_category::get($course->category, IGNORE_MISSING);

        if (!$category) {
            return 'Unknown';
        }

        return $category->get_nested_name(false, ' > ');
    }

    /**
     * Get course modules in a specific section.
     *
     * @param \course_modinfo $modinfo The course module info object.
     * @param int $sectionnum The section number.
     * @return array Array of cm_info objects keyed by cmid.
     */
    protected function get_cms_in_section(\course_modinfo $modinfo, int $sectionnum): array {
        $sections = $modinfo->get_sections();
        $cmids = $sections[$sectionnum] ?? [];
        $modules = [];

        foreach ($cmids as $cmid) {
            $modules[$cmid] = $modinfo->get_cm($cmid);
        }

        return $modules;
    }

    /**
     * Find adjacent modules (previous and next) for a given module ID.
     *
     * Returns only visible and available modules.
     *
     * @param array $modules Array of cm_info objects from the section.
     * @param int $cmid The current module ID.
     * @return array ['prev' => cm_info|null, 'next' => cm_info|null]
     */
    protected function find_adjacent_modules(array $modules, int $cmid): array {
        $accessibleModules = $this->get_accessible_modules($modules);
        $moduleIds = array_keys($accessibleModules);
        $currentIndex = array_search($cmid, $moduleIds);

        if ($currentIndex === false) {
            return ['prev' => null, 'next' => null];
        }

        $prevModule = null;
        $nextModule = null;

        if ($currentIndex > 0) {
            $prevModule = $accessibleModules[$moduleIds[$currentIndex - 1]];
        }

        if ($currentIndex < count($moduleIds) - 1) {
            $nextModule = $accessibleModules[$moduleIds[$currentIndex + 1]];
        }

        return ['prev' => $prevModule, 'next' => $nextModule];
    }

    /**
     * Build course metadata lines for markdown output.
     *
     * @param object $course The course object.
     * @param int $headingLevel Markdown heading level (default 2 for ##).
     * @return array Lines of markdown for course metadata.
     */
    protected function build_course_metadata_lines(object $course, int $headingLevel = 2): array {
        $heading = str_repeat('#', $headingLevel);
        $lines = [];

        $lines[] = "{$heading} Course";
        $lines[] = "- **Name:** {$course->fullname}";
        $lines[] = "- **Category:** {$this->get_course_category_path($course)}";
        $lines[] = "- **Format:** {$course->format}";

        return $lines;
    }

    /**
     * Build adjacent sections context lines.
     *
     * @param \course_modinfo $modinfo The course module info.
     * @param object $course The course object.
     * @param int $sectionnum The current section number.
     * @return array Lines describing adjacent sections.
     */
    protected function build_adjacent_sections(\course_modinfo $modinfo, object $course, int $sectionnum): array {
        $lines = [];

        $prevSection = $sectionnum > 0 ? $modinfo->get_section_info($sectionnum - 1) : null;
        $nextSection = $modinfo->get_section_info($sectionnum + 1);

        if ($prevSection && $prevSection->visible) {
            $prevName = $this->get_section_name($course, $prevSection);
            $lines[] = "- Previous: {$prevName}";
        }

        if ($nextSection && $nextSection->visible) {
            $nextName = $this->get_section_name($course, $nextSection);
            $lines[] = "- Next: {$nextName}";
        }

        return $lines;
    }

    /**
     * Get module position within its section.
     *
     * @param int $cmid The course module ID.
     * @param \course_modinfo $modinfo The course module info object.
     * @param int $sectionnum The section number.
     * @return string Position string (e.g., "3/7 in section").
     */
    protected function get_module_position(int $cmid, \course_modinfo $modinfo, int $sectionnum): string {
        $sectionModules = $this->get_cms_in_section($modinfo, $sectionnum);
        $accessibleModules = $this->get_accessible_modules($sectionModules);
        $moduleIds = array_keys($accessibleModules);
        $position = array_search($cmid, $moduleIds);

        if ($position === false) {
            return 'unknown';
        }

        return ($position + 1) . '/' . count($accessibleModules) . ' in section';
    }

    /**
     * Truncate and return the final context string.
     *
     * @param array $lines Array of markdown lines.
     * @return string Truncated markdown context.
     */
    protected function finalize_context(array $lines): string {
        return $this->htmlHelper->truncate_context(implode("\n", $lines));
    }
}
