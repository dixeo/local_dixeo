<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Adds mod_coursecertificate to a course from designer-style configuration.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_certificate_service {

    /**
     * Try to add a course certificate activity.
     *
     * @param int $courseid
     * @param bool $generationenabled Admin setting: certificate generation on.
     * @param int $templateid Template id from tool_certificate (must exist).
     * @param string $location 'summary' or 'last' (after content sections, excluding resources).
     * @param string $activityname Translated activity name.
     * @param string $sectiontitle Translated section title when $location is last.
     * @param string $sectionintro Section summary HTML when $location is last.
     * @param string|null $excludeidnumber Skip CMs with this idnumber when building completion gate (e.g. designer uploads).
     * @return string|false 'summary'|'last' if a certificate was added; false if skipped.
     */
    public function try_add_coursecertificate_activity(
        int $courseid,
        bool $generationenabled,
        int $templateid,
        string $location,
        string $activityname,
        string $sectiontitle,
        string $sectionintro,
        ?string $excludeidnumber = null
    ) {
        global $DB;

        if (!$generationenabled || $templateid <= 0) {
            return false;
        }

        $pluginmanager = \core_plugin_manager::instance();
        if ($pluginmanager->get_plugin_info('mod_coursecertificate') === null
                || $pluginmanager->get_plugin_info('tool_certificate') === null) {
            return false;
        }

        if (!$DB->record_exists('tool_certificate_templates', ['id' => $templateid])) {
            return false;
        }

        $hascert = $DB->record_exists_sql(
            "SELECT 1 FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module
             WHERE cm.course = ? AND m.name = 'coursecertificate'
               AND cm.deletioninprogress = 0",
            [$courseid]
        );
        if ($hascert) {
            return false;
        }

        $course = get_course($courseid);
        $translations = get_string_manager()->get_list_of_translations();
        $fixlang = isset($translations[$course->lang]) && $course->lang !== '';
        if ($fixlang) {
            $defaultlang = current_language();
            \fix_current_language($course->lang);
        }

        try {
            if ($location === 'summary') {
                $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => 0], '*', MUST_EXIST);
                $trailing = 'summary';
            } else {
                $section = $this->append_course_section($courseid, $sectiontitle, $sectionintro);
                $trailing = 'last';
            }

            $availability = $this->build_completion_gate_availability_json($courseid, $excludeidnumber);

            $instance = (object) [
                'course' => $courseid,
                'name' => $activityname,
                'template' => $templateid,
                'automaticsend' => 0,
                'expirydatetype' => 0,
                'expirydateoffset' => 0,
                'intro' => '',
                'introformat' => FORMAT_HTML,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $instance->id = $DB->insert_record('coursecertificate', $instance);

            $moduleid = $DB->get_field('modules', 'id', ['name' => 'coursecertificate'], MUST_EXIST);
            $cm = (object) [
                'course' => $courseid,
                'module' => $moduleid,
                'instance' => $instance->id,
                'section' => $section->id,
                'visible' => 1,
                'visibleoncoursepage' => 1,
                'downloadcontent' => 1,
                'completion' => 0,
            ];
            if ($availability !== null) {
                $cm->availability = $availability;
            }
            $cm->id = $DB->insert_record('course_modules', $cm);
            \course_add_cm_to_section($course, $cm->id, $section->section);
            \rebuild_course_cache($courseid, true);

            return $trailing;
        } finally {
            if ($fixlang) {
                \fix_current_language($defaultlang);
            }
        }
    }

    /**
     * Build availability: student must complete all tracked content modules (core completion condition).
     *
     * @param int $courseid
     * @param string|null $excludeidnumber
     * @return string|null JSON or null if no gating modules.
     */
    private function build_completion_gate_availability_json(int $courseid, ?string $excludeidnumber): ?string {
        global $DB;

        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = :courseid
                   AND cm.completion > 0
                   AND cm.deletioninprogress = 0
                   AND m.name NOT IN ('coursecertificate', 'label')";
        $params = ['courseid' => $courseid];
        if ($excludeidnumber !== null && $excludeidnumber !== '') {
            $sql .= " AND (cm.idnumber IS NULL OR cm.idnumber <> :idn)";
            $params['idn'] = $excludeidnumber;
        }
        $cmids = $DB->get_fieldset_sql($sql, $params);
        if ($cmids === []) {
            return null;
        }
        $conds = [];
        foreach ($cmids as $cmid) {
            $conds[] = [
                'type' => 'completion',
                'cm' => (int) $cmid,
                'e' => COMPLETION_COMPLETE,
            ];
        }
        return json_encode([
            'op' => '&',
            'showc' => [true],
            'c' => $conds,
        ]);
    }

    /**
     * Append a new section at max(section)+1.
     *
     * @param int $courseid
     * @param string $name
     * @param string $summary
     * @return \stdClass Section record with id and section number.
     */
    private function append_course_section(int $courseid, string $name, string $summary): \stdClass {
        global $DB;

        $next = (int) $DB->get_field_sql(
            'SELECT COALESCE(MAX(section), 0) + 1 FROM {course_sections} WHERE course = ?',
            [$courseid]
        );
        $section = (object) [
            'course' => $courseid,
            'section' => $next,
            'name' => $name,
            'summary' => $summary,
            'summaryformat' => FORMAT_HTML,
            'visible' => 1,
            'timemodified' => time(),
        ];
        $section->id = $DB->insert_record('course_sections', $section);
        \rebuild_course_cache($courseid, true);

        return $section;
    }
}
