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
 * Event when a remote Dixeo job is cancelled.
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\event;

/**
 * Fired after a successful Dixeo job cancellation request.
 *
 * Includes the remote job UUID only — no prompts, messages, or content.
 */
class job_cancelled extends \core\event\base {
    /**
     * Init method.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventjobcancelled', 'local_dixeo');
    }

    /**
     * Non-localised description for logs.
     *
     * @return string
     */
    public function get_description(): string {
        $jobid = clean_param((string) ($this->other['jobid'] ?? ''), PARAM_TEXT);
        return get_string('eventjobcancelleddesc', 'local_dixeo', (object) [
            'userid' => $this->userid,
            'courseid' => $this->courseid,
            'jobid' => $jobid,
        ]);
    }

    /**
     * Relevant URL.
     *
     * @return \moodle_url|null
     */
    public function get_url() {
        if ($this->courseid) {
            return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
        }
        return null;
    }

    /**
     * Create an event for a cancelled job.
     *
     * @param string $jobid Remote job UUID.
     * @param int $courseid Course the job is bound to (0 if unknown).
     * @param int $userid User who cancelled the job.
     * @return self
     */
    public static function create_for_job(string $jobid, int $courseid, int $userid): self {
        $context = \context_system::instance();
        $resolvedcourseid = 0;

        if ($courseid > 0) {
            try {
                $context = \context_course::instance($courseid);
                $resolvedcourseid = $courseid;
            } catch (\Throwable $e) {
                // Course may already be gone; keep a system-context audit record.
                $context = \context_system::instance();
            }
        }

        $data = [
            'context' => $context,
            'userid' => $userid,
            'other' => [
                'jobid' => $jobid,
            ],
        ];
        if ($resolvedcourseid > 0) {
            $data['courseid'] = $resolvedcourseid;
        }

        return self::create($data);
    }

    /**
     * Custom validation.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (empty($this->other['jobid'])) {
            throw new \coding_exception('The \'jobid\' value must be set in other.');
        }
    }

    /**
     * Object id mapping for backup/restore.
     *
     * @return false
     */
    public static function get_objectid_mapping() {
        return false;
    }

    /**
     * Other mapping for backup/restore.
     *
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}
