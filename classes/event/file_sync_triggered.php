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
 * Event when a course file synchronisation run is started.
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\event;

/**
 * Fired when Dixeo begins uploading or clearing course files remotely.
 *
 * Does not include filenames, hashes, or file contents.
 */
class file_sync_triggered extends \core\event\base {
    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_dixeo_course_ai';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventfilesynctriggered', 'local_dixeo');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('eventfilesynctriggereddesc', 'local_dixeo', (object) [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
        ]);
    }

    /**
     * Relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    /**
     * Create an event for a started course file sync run.
     *
     * @param int $courseid Course ID.
     * @param int $userid User associated with the sync (session user when available).
     * @param int $objectid local_dixeo_course_ai row id.
     * @return self
     */
    public static function create_for_course(int $courseid, int $userid, int $objectid): self {
        return self::create([
            'context' => \context_course::instance($courseid),
            'objectid' => $objectid,
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
    }

    /**
     * Object id mapping for backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'local_dixeo_course_ai', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
