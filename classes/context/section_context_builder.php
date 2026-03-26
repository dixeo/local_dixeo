<?php
/**
 * Context builder for section-level context.
 *
 * Constructs markdown context for a specific section including:
 * - Course name
 * - Section name and summary
 * - All modules in the section with content
 * - Adjacent sections for navigation context
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
 * Builds section-level context markdown for AI processing.
 */
class section_context_builder extends abstract_context_builder {

    /** @var int The section ID (course_sections.id). */
    private int $sectionId;

    /** @var object|null Cached section record. */
    private ?object $section = null;

    /** @var object|null Cached course object. */
    private ?object $course = null;

    /** @var \course_modinfo|null Cached modinfo. */
    private ?\course_modinfo $modinfo = null;

    /**
     * Constructor.
     *
     * @param int $sectionId The section ID (course_sections.id).
     * @param html_helper|null $htmlHelper Optional HTML helper.
     * @param module_content_extractor|null $contentExtractor Optional content extractor.
     */
    public function __construct(
        int $sectionId,
        ?html_helper $htmlHelper = null,
        ?module_content_extractor $contentExtractor = null
    ) {
        parent::__construct($htmlHelper, $contentExtractor);
        $this->sectionId = $sectionId;
    }

    /**
     * Build and return the section context markdown.
     *
     * @return string Markdown-formatted section context.
     */
    public function build(): string {
        $this->loadSectionData();

        $lines = [];
        $lines[] = '# Section Context';
        $lines[] = '';
        $lines[] = "## Course: {$this->course->fullname}";
        $lines[] = '';

        $sectionName = $this->get_section_name($this->course, $this->section);
        $lines[] = "## Section: {$sectionName}";
        $lines[] = '';

        if (!empty($this->section->summary)) {
            $lines[] = '### Section Summary';
            $lines[] = $this->htmlHelper->clean_html($this->section->summary);
            $lines[] = '';
        }

        $lines = array_merge($lines, $this->buildSectionModules());

        $lines[] = '### Adjacent Sections';
        $lines[] = '';
        $lines = array_merge($lines, $this->build_adjacent_sections(
            $this->modinfo,
            $this->course,
            $this->section->section
        ));

        return $this->finalize_context($lines);
    }

    /**
     * Load section, course, and modinfo data.
     *
     * @return void
     * @throws \dml_exception If section or course not found.
     */
    private function loadSectionData(): void {
        global $DB;

        if ($this->section === null) {
            $this->section = $DB->get_record('course_sections', ['id' => $this->sectionId], '*', MUST_EXIST);
            $this->course = $DB->get_record('course', ['id' => $this->section->course], '*', MUST_EXIST);
            $this->modinfo = get_fast_modinfo($this->course);
        }
    }

    /**
     * Build module list for the section.
     *
     * @return array Lines of markdown for section modules.
     */
    private function buildSectionModules(): array {
        $sectionInfo = $this->modinfo->get_section_info($this->section->section);

        if (!$sectionInfo) {
            return [];
        }

        $modules = $this->get_cms_in_section($this->modinfo, $this->section->section);

        if (empty($modules)) {
            return [];
        }

        $lines = [];
        $lines[] = '### Modules in this Section';
        $lines[] = '';

        foreach ($modules as $cm) {
            if (!$this->is_module_accessible($cm)) {
                continue;
            }

            $fileannotation = $this->get_file_annotation($cm);
            $lines[] = "#### [{$cm->modname}] {$cm->name}{$fileannotation}";

            $content = $this->contentExtractor->get_preview($cm);

            if (!empty($content)) {
                $lines[] = $content;
            }

            $lines[] = '';
        }

        return $lines;
    }
}
