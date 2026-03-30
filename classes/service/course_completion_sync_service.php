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

/**
 * Ensures course-level activity completion criteria exist for tracked modules.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_completion_sync_service {

    /**
     * Insert missing COMPLETION_CRITERIA_TYPE_ACTIVITY rows for each CM with completion enabled.
     *
     * Idempotent: skips when a criterion already exists for the same course + cm id.
     *
     * @param int $courseid
     * @return void
     */
    public function sync_activity_criteria_from_modules(int $courseid): void {
        global $DB;

        $sql = <<<SQL
        SELECT cm.id, m.name AS modulename
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = ?
           AND cm.completion > 0
           AND cm.deletioninprogress = 0
        SQL;
        $modules = $DB->get_records_sql($sql, [$courseid]);

        foreach ($modules as $cm) {
            if ($cm->modulename === 'label') {
                continue;
            }
            $exists = $DB->record_exists('course_completion_criteria', [
                'course' => $courseid,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
                'moduleinstance' => $cm->id,
            ]);
            if ($exists) {
                continue;
            }
            $row = (object) [
                'course' => $courseid,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
                'module' => $cm->modulename,
                'moduleinstance' => $cm->id,
            ];
            $DB->insert_record('course_completion_criteria', $row);
        }
    }
}
