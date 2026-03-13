<?php
/**
 * Trait for loading module data shared between context builders.
 *
 * Provides common properties and lazy-loading logic for module, course,
 * and section data needed by module-level context builders.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\context;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait providing shared module data loading for context builders.
 */
trait module_data_loader {

    /** @var int The course module ID. */
    private int $cmid;

    /** @var object|null Cached course module record. */
    private ?object $cm = null;

    /** @var object|null Cached course object. */
    private ?object $course = null;

    /** @var \course_modinfo|null Cached modinfo. */
    private ?\course_modinfo $modinfo = null;

    /** @var \cm_info|null Cached cm_info object. */
    private ?\cm_info $cminfo = null;

    /** @var \section_info|null Cached section info. */
    private ?\section_info $section = null;

    /**
     * Load module, course, and related data.
     *
     * @return void
     * @throws \dml_exception If module or course not found.
     */
    private function loadModuleData(): void {
        global $DB;

        if ($this->cm === null) {
            $this->cm = get_coursemodule_from_id('', $this->cmid, 0, false, MUST_EXIST);
            $this->course = $DB->get_record('course', ['id' => $this->cm->course], '*', MUST_EXIST);
            $this->modinfo = get_fast_modinfo($this->course);
            $this->cminfo = $this->modinfo->get_cm($this->cmid);
            $this->section = $this->modinfo->get_section_info($this->cminfo->sectionnum);
        }
    }
}
