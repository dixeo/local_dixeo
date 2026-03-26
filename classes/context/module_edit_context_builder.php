<?php
/**
 * Context builder for module editing/regeneration operations.
 *
 * Constructs markdown context specifically designed for AI edit operations:
 * - Surrounding context (course, section, adjacent modules with excerpts)
 * - Module to edit with clear markers for AI processing
 * - Current content with delimiters for identification
 *
 * The output format is optimized for AI to understand what needs editing
 * and what context to use for generating replacement content.
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
 * Builds module edit context markdown for AI processing.
 */
class module_edit_context_builder extends abstract_context_builder {
    use module_data_loader;

    /**
     * Constructor.
     *
     * @param int $cmid The course module ID.
     * @param html_helper|null $htmlHelper Optional HTML helper.
     * @param module_content_extractor|null $contentExtractor Optional content extractor.
     */
    public function __construct(
        int $cmid,
        ?html_helper $htmlHelper = null,
        ?module_content_extractor $contentExtractor = null
    ) {
        parent::__construct($htmlHelper, $contentExtractor);
        $this->cmid = $cmid;
    }

    /**
     * Build and return the module edit context markdown.
     *
     * @return string Markdown-formatted edit context.
     */
    public function build(): string {
        $this->loadModuleData();

        $lines = [];
        $lines[] = '# Edit Context';
        $lines[] = '';

        // Surrounding context section.
        $lines[] = '## SURROUNDING_CONTEXT';
        $lines[] = '';
        $lines = array_merge($lines, $this->buildSurroundingContext());

        // Separator.
        $lines[] = '---';
        $lines[] = '';

        // Module to edit section.
        $lines = array_merge($lines, $this->buildModuleToEditSection());

        return $this->finalize_context($lines);
    }

    /**
     * Build surrounding context (course, section, adjacent modules).
     *
     * @return array Lines of markdown for surrounding context.
     */
    private function buildSurroundingContext(): array {
        $lines = [];

        // Course metadata with total sections count.
        $lines[] = '### Course';
        $lines[] = "- **Name:** {$this->course->fullname}";
        $lines[] = "- **Category:** {$this->get_course_category_path($this->course)}";
        $lines[] = "- **Format:** {$this->course->format}";

        $totalSections = count($this->modinfo->get_section_info_all());
        $lines[] = "- **Total Sections:** {$totalSections}";
        $lines[] = '';

        // Current section info.
        $sectionName = $this->get_section_name($this->course, $this->section);
        $lines[] = "### Current Section: {$sectionName} (Section {$this->section->section})";

        if (!empty($this->section->summary)) {
            $lines[] = $this->htmlHelper->clean_html($this->section->summary);
        }
        $lines[] = '';

        // Adjacent modules with excerpts.
        $lines = array_merge($lines, $this->buildAdjacentModulesDetailed());

        return $lines;
    }

    /**
     * Build module to edit section with markers.
     *
     * @return array Lines of markdown for module to edit.
     */
    private function buildModuleToEditSection(): array {
        $lines = [];

        $lines[] = '## MODULE_TO_EDIT';
        $lines[] = "- **Type:** {$this->cminfo->modname}";
        $lines[] = "- **Name:** {$this->cminfo->name}";

        $position = $this->get_module_position($this->cmid, $this->modinfo, $this->cminfo->sectionnum);
        $lines[] = "- **Position:** {$position}";
        $lines[] = '';

        // Current content with clear markers for AI.
        $lines[] = '## CURRENT_CONTENT';
        $lines[] = '>>> MODULE TO EDIT <<<';

        $content = $this->contentExtractor->get_full_content_for_edit($this->cminfo);

        if (!empty($content)) {
            $lines[] = $content;
        }

        $lines[] = '>>> END MODULE TO EDIT <<<';

        return $lines;
    }

    /**
     * Build detailed adjacent modules context with excerpts.
     *
     * @return array Lines describing adjacent modules with content excerpts.
     */
    private function buildAdjacentModulesDetailed(): array {
        $lines = [];
        $sectionModules = $this->get_cms_in_section($this->modinfo, $this->cminfo->sectionnum);
        $adjacent = $this->find_adjacent_modules($sectionModules, $this->cmid);

        if ($adjacent['prev'] !== null) {
            $lines = array_merge($lines, $this->buildModuleDetailBlock('Previous Module', $adjacent['prev']));
        }

        if ($adjacent['next'] !== null) {
            $lines = array_merge($lines, $this->buildModuleDetailBlock('Next Module', $adjacent['next']));
        }

        return $lines;
    }

    /**
     * Build a detailed module block with type, name, and excerpt.
     *
     * @param string $label Block label (e.g., "Previous Module").
     * @param \cm_info $cm The course module info.
     * @return array Lines for the module block.
     */
    private function buildModuleDetailBlock(string $label, \cm_info $cm): array {
        $lines = [];
        $lines[] = "### {$label}";
        $fileannotation = $this->get_file_annotation($cm);
        $lines[] = "- **Type:** {$cm->modname}";
        $lines[] = "- **Name:** {$cm->name}{$fileannotation}";

        $excerpt = $this->contentExtractor->get_excerpt($cm);

        if ($excerpt !== null) {
            $lines[] = "- **Excerpt:** {$excerpt}";
        }

        $lines[] = '';

        return $lines;
    }
}
