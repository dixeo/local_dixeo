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

use local_dixeo\task\poll_image_generation_job;

/**
 * Queues and deduplicates adhoc tasks that poll async image generation jobs.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class image_poll_manager {

    /** @var int Stop chaining after this many minute-long poll windows (~1h). */
    public const MAX_CHAIN_SEGMENTS = 60;

    /** Apply job bytes to Moodle course overview files. {@see course_image_writer::apply_from_job_result()} objectid = course id. */
    public const SCOPE_COURSE_OVERVIEW = 'course_overview';

    /** Apply job bytes to format_dixeo chapter image file area. objectid = course_sections.id. */
    public const SCOPE_FORMAT_SECTION = 'format_section';

    /**
     * Canonical classname for DB / manager APIs.
     *
     * @return string
     */
    public static function task_classname(): string {
        return poll_image_generation_job::class;
    }

    /**
     * Remove queued (not running) poll tasks for the same course, scope, and target object.
     *
     * Dedupes per logical job (e.g. one course overview image or one section chapter image), without
     * cancelling polls for other scopes or sections on the same course.
     *
     * @param int $courseid
     * @param string $scope One of the SCOPE_* constants.
     * @param int $objectid course_sections.id for {@see self::SCOPE_FORMAT_SECTION}.
     * @return int Number of deleted rows.
     */
    public static function delete_queued_poll_tasks(int $courseid, string $scope = self::SCOPE_COURSE_OVERVIEW, ?int $objectid = null): int {
        global $DB;

        $tasks = \core\task\manager::get_adhoc_tasks(self::task_classname(), false, true);
        $deleted = 0;
        foreach ($tasks as $task) {
            $data = $task->get_custom_data();
            if (!is_object($data)) {
                continue;
            }
            if ((int) ($data->courseid ?? 0) !== $courseid) {
                continue;
            }
            
            // Define task scope.
            $taskscope = isset($data->scope) && (string) $data->scope !== ''
                ? (string) $data->scope
                : self::SCOPE_COURSE_OVERVIEW;

            // Define task object.
            if ($scope === self::SCOPE_COURSE_OVERVIEW) {
                $taskobject = (int) ($data->courseid ?? 0);
            } else if ($scope === self::SCOPE_FORMAT_SECTION) {
                $taskobject = (int) ($data->objectid ?? 0);
            }

            if ($taskscope !== $scope || $taskobject !== $objectid) {
                continue;
            }
            $id = $task->get_id();
            if (!$id) {
                continue;
            }
            $record = $DB->get_record('task_adhoc', ['id' => $id], 'id, timestarted', IGNORE_MISSING);
            if (!$record || (int) $record->timestarted > 0) {
                continue;
            }
            $DB->delete_records('task_adhoc', ['id' => $id]);
            $deleted++;
        }
        return $deleted;
    }

    /**
     * Queue a new poll task after clearing other queued polls for the same course, scope, and object.
     *
     * @param int $courseid Owning course (used for dedupe).
     * @param string $imagejobid Remote job id.
     * @param int $userid User context for capabilities and file ownership.
     * @param int $chainseq Zero-based chain segment (each segment polls up to ~60s).
     * @param string $scope One of the SCOPE_* constants.
     * @param int|null $objectid Target id: course id for {@see self::SCOPE_COURSE_OVERVIEW}, section id for {@see self::SCOPE_FORMAT_SECTION}. Null defaults to courseid for overview scope.
     * @return void
     */
    public static function queue_poll_task(
        int $courseid,
        string $imagejobid,
        int $userid,
        int $chainseq = 0,
        string $scope = self::SCOPE_COURSE_OVERVIEW,
        ?int $objectid = null
    ): void {
        if ($objectid === null || $objectid < 1) {
            $objectid = $courseid;
        }

        self::delete_queued_poll_tasks($courseid, $scope, $objectid);

        $task = new poll_image_generation_job();
        $task->set_custom_data([
            'courseid' => $courseid,
            'imagejobid' => $imagejobid,
            'userid' => $userid,
            'chainseq' => $chainseq,
            'scope' => $scope,
            'objectid' => $objectid,
        ]);
        $task->set_userid($userid);
        \core\task\manager::queue_adhoc_task($task);
    }
}
