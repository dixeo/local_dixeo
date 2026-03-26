<?php
/**
 * Context builder for module generation operations.
 *
 * Constructs markdown context for generating new module content including:
 * - Course and section information
 * - Current module details and content
 * - Adjacent modules in the section for continuity
 *
 * Used primarily when AI needs to understand the surrounding context
 * to generate appropriate new content for a module.
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
 * Builds module generation context markdown for AI processing.
 */
class module_generation_context_builder extends abstract_context_builder {
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
     * Build and return the module generation context markdown.
     *
     * @return string Markdown-formatted module context.
     */
    public function build(): string {
        $this->loadModuleData();

        $lines = [];
        $lines[] = '# Module Context';
        $lines[] = '';
        $lines[] = "## Course: {$this->course->fullname}";
        $lines[] = '';

        $sectionName = $this->get_section_name($this->course, $this->section);
        $lines[] = "## Section: {$sectionName}";
        $lines[] = '';

        if (!empty($this->section->summary)) {
            $lines[] = $this->htmlHelper->clean_html($this->section->summary);
            $lines[] = '';
        }

        $lines[] = "## Module: {$this->cminfo->name}";
        $lines[] = "Type: {$this->cminfo->modname}";
        $lines[] = '';

        $content = $this->contentExtractor->get_full_content($this->cminfo);

        if (!empty($content)) {
            $lines[] = '### Current Content';
            $lines[] = $content;
            $lines[] = '';
        }

        $lines[] = '### Adjacent Modules in Section';
        $lines[] = '';
        $lines = array_merge($lines, $this->buildAdjacentModulesSimple());

        return $this->finalize_context($lines);
    }

    /**
     * Build simple adjacent modules context (names only).
     *
     * @return array Lines describing adjacent modules.
     */
    private function buildAdjacentModulesSimple(): array {
        $lines = [];
        $sectionModules = $this->get_cms_in_section($this->modinfo, $this->cminfo->sectionnum);
        $adjacent = $this->find_adjacent_modules($sectionModules, $this->cmid);

        if ($adjacent['prev'] !== null) {
            $prev = $adjacent['prev'];
            $fileannotation = $this->get_file_annotation($prev);
            $lines[] = "- Previous: [{$prev->modname}] {$prev->name}{$fileannotation}";
        }

        if ($adjacent['next'] !== null) {
            $next = $adjacent['next'];
            $fileannotation = $this->get_file_annotation($next);
            $lines[] = "- Next: [{$next->modname}] {$next->name}{$fileannotation}";
        }

        return $lines;
    }
}
