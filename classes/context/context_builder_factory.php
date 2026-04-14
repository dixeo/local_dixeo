<?php
/**
 * Factory for creating context builder instances.
 *
 * Provides convenient static methods to create the appropriate context
 * builder based on the use case. Encapsulates builder construction and
 * allows for shared dependency injection.
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
 * Factory for creating context builders.
 */
class context_builder_factory {

    /** @var html_helper|null Shared HTML helper instance. */
    private static ?html_helper $sharedHtmlHelper = null;

    /** @var module_content_extractor|null Shared content extractor instance. */
    private static ?module_content_extractor $sharedContentExtractor = null;

    /**
     * Create a course context builder.
     *
     * @param int $courseId The course ID.
     * @param int|null $targetSection Target section for tiered detail (teaching mode).
     * @param string $mode Context mode: 'teaching' or 'assessment'.
     * @return course_context_builder The configured builder.
     */
    public static function course(
        int $courseId,
        ?int $targetSection = null,
        string $mode = course_context_builder::MODE_TEACHING
    ): course_context_builder {
        return new course_context_builder(
            $courseId,
            $targetSection,
            $mode,
            self::getHtmlHelper(),
            self::getContentExtractor()
        );
    }

    /**
     * Create a section context builder.
     *
     * @param int $sectionId The section ID (course_sections.id).
     * @return section_context_builder The configured builder.
     */
    public static function section(int $sectionId): section_context_builder {
        return new section_context_builder(
            $sectionId,
            self::getHtmlHelper(),
            self::getContentExtractor()
        );
    }

    /**
     * Create a module generation context builder.
     *
     * @param int $cmid The course module ID.
     * @return module_generation_context_builder The configured builder.
     */
    public static function moduleGeneration(int $cmid): module_generation_context_builder {
        return new module_generation_context_builder(
            $cmid,
            self::getHtmlHelper(),
            self::getContentExtractor()
        );
    }

    /**
     * Create a module edit context builder.
     *
     * @param int $cmid The course module ID.
     * @param string|null $autosaveDraftHtml Optional HTML from tiny_autosave (null = use saved module content only).
     * @return module_edit_context_builder The configured builder.
     */
    public static function moduleEdit(int $cmid, ?string $autosaveDraftHtml = null): module_edit_context_builder {
        return new module_edit_context_builder(
            $cmid,
            self::getHtmlHelper(),
            self::getContentExtractor(),
            $autosaveDraftHtml
        );
    }

    /**
     * Create a slide edit context builder.
     *
     * @param int $cmid The slideshow course module ID.
     * @param int $slideid The slideshow_slide row ID being edited.
     * @return slide_edit_context_builder The configured builder.
     */
    public static function slide_edit(int $cmid, int $slideid): slide_edit_context_builder {
        return new slide_edit_context_builder(
            $cmid,
            $slideid,
            self::getHtmlHelper(),
            self::getContentExtractor()
        );
    }

    /**
     * Build course context directly (convenience method).
     *
     * @param int $courseId The course ID.
     * @param int|null $targetSection Target section for tiered detail.
     * @param string $mode Context mode: 'teaching' or 'assessment'.
     * @return string The built markdown context.
     */
    public static function buildCourseContext(
        int $courseId,
        ?int $targetSection = null,
        string $mode = course_context_builder::MODE_TEACHING
    ): string {
        return self::course($courseId, $targetSection, $mode)->build();
    }

    /**
     * Build section context directly (convenience method).
     *
     * @param int $sectionId The section ID.
     * @return string The built markdown context.
     */
    public static function buildSectionContext(int $sectionId): string {
        return self::section($sectionId)->build();
    }

    /**
     * Build module generation context directly (convenience method).
     *
     * @param int $cmid The course module ID.
     * @return string The built markdown context.
     */
    public static function buildModuleGenerationContext(int $cmid): string {
        return self::moduleGeneration($cmid)->build();
    }

    /**
     * Build module edit context directly (convenience method).
     *
     * @param int $cmid The course module ID.
     * @param string|null $autosaveDraftHtml Optional HTML from tiny_autosave (null = use saved module content only).
     * @return string The built markdown context.
     */
    public static function buildModuleEditContext(int $cmid, ?string $autosaveDraftHtml = null): string {
        return self::moduleEdit($cmid, $autosaveDraftHtml)->build();
    }

    /**
     * Build slide edit context directly (convenience method).
     *
     * @param int $cmid The slideshow course module ID.
     * @param int $slideid The slideshow_slide row ID being edited.
     * @return string The built markdown context.
     */
    public static function build_slide_edit_context(int $cmid, int $slideid): string {
        return self::slide_edit($cmid, $slideid)->build();
    }

    /**
     * Build context for structure-based module fill.
     *
     * Combines course context with module metadata (title/summary) so the AI
     * generates content coherent with the planned module identity.
     * Used when creating modules from a course structure where name/intro
     * are already defined and only content needs to be generated.
     *
     * @param int $courseId The course ID.
     * @param int|null $targetSection Target section for tiered detail.
     * @param string $mode Context mode: 'teaching' or 'assessment'.
     * @param string $title The module title from the course structure.
     * @param string $summary The module summary from the course structure.
     * @return string The built markdown context with module metadata prepended.
     */
    public static function buildModuleFillContext(
        int $courseId,
        ?int $targetSection,
        string $mode,
        string $title,
        string $summary = ''
    ): string {
        $coursecontext = self::buildCourseContext($courseId, $targetSection, $mode);

        $lines = ['## Module to Fill'];
        $lines[] = "- **Title:** {$title}";
        if (!empty($summary)) {
            $lines[] = "- **Summary:** {$summary}";
        }
        $lines[] = '';

        return implode("\n", $lines) . $coursecontext;
    }

    /**
     * Get or create shared HTML helper.
     *
     * @return html_helper The shared instance.
     */
    private static function getHtmlHelper(): html_helper {
        if (self::$sharedHtmlHelper === null) {
            self::$sharedHtmlHelper = new html_helper();
        }

        return self::$sharedHtmlHelper;
    }

    /**
     * Get or create shared content extractor.
     *
     * @return module_content_extractor The shared instance.
     */
    private static function getContentExtractor(): module_content_extractor {
        if (self::$sharedContentExtractor === null) {
            self::$sharedContentExtractor = new module_content_extractor(self::getHtmlHelper());
        }

        return self::$sharedContentExtractor;
    }

    /**
     * Reset shared instances (useful for testing).
     *
     * @return void
     */
    public static function reset(): void {
        self::$sharedHtmlHelper = null;
        self::$sharedContentExtractor = null;
    }
}
