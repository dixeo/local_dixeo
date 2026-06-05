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

/**
 * Orchestrates manual (non-AI) module uploads from multipart form data.
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

use coding_exception;
use context_course;
use context_user;
use local_dixeo\dsl\interpreter;
use local_dixeo\external\service_factory;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Generic manual upload orchestration for SCORM and File activities.
 */
class manual_upload_service {

    /** @var string[] Supported modtype values. */
    private const SUPPORTED_MODTYPES = ['scorm', 'resource'];

    /** @var scorm_creation_service */
    private scorm_creation_service $scormservice;

    /** @var resource_upload_service */
    private resource_upload_service $resourceservice;

    /**
     * @param scorm_creation_service|null $scormservice Optional SCORM service.
     * @param resource_upload_service|null $resourceservice Optional resource service.
     */
    public function __construct(
        ?scorm_creation_service $scormservice = null,
        ?resource_upload_service $resourceservice = null
    ) {
        $this->scormservice = $scormservice ?? new scorm_creation_service();
        $this->resourceservice = $resourceservice ?? new resource_upload_service();
    }

    /**
     * Create a module from an uploaded file.
     *
     * @param string $modtype Module type: scorm or resource.
     * @param int $courseid Course ID.
     * @param int $sectionnum Section number.
     * @param int|null $beforemod Optional cmid to insert before.
     * @param string $name Activity name.
     * @param array $uploadedfile $_FILES entry shape.
     * @return array{cmid: int, id: int}
     * @throws coding_exception|moodle_exception
     */
    public function create_from_upload(
        string $modtype,
        int $courseid,
        int $sectionnum,
        ?int $beforemod,
        string $name,
        array $uploadedfile
    ): array {
        if (!in_array($modtype, self::SUPPORTED_MODTYPES, true)) {
            throw new coding_exception('Unsupported manual upload modtype: ' . $modtype);
        }

        $name = trim($name);
        if ($name === '') {
            throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
        }

        $this->validate_course_access($courseid);
        $draftitemid = $this->stage_upload_to_draft($courseid, $uploadedfile);

        $context = interpreter::build_context($courseid, $sectionnum, $modtype, $beforemod ?: null);
        $sectionid = (int) $context['sectionid'];
        $resolvedsectionnum = (int) $context['sectionnum'];
        $resolvedbeforemod = !empty($context['beforemod']) ? (int) $context['beforemod'] : null;

        if ($modtype === 'scorm') {
            $result = $this->scormservice->create_from_draft(
                $courseid,
                $sectionid,
                $resolvedsectionnum,
                $resolvedbeforemod,
                $name,
                $draftitemid
            );
        } else {
            $result = $this->resourceservice->create_from_draft(
                $courseid,
                $sectionid,
                $resolvedsectionnum,
                $resolvedbeforemod,
                $name,
                $draftitemid
            );
        }

        service_factory::get_file_sync_service()
            ->enable_and_queue_sync_after_module_creation($courseid);

        return $result;
    }

    /**
     * Validate course login, generate and manageactivities capabilities.
     *
     * @param int $courseid Course ID.
     */
    private function validate_course_access(int $courseid): void {
        require_course_login($courseid);
        $context = context_course::instance($courseid);
        require_capability('local/dixeo:generate', $context);
        require_capability('moodle/course:manageactivities', $context);
    }

    /**
     * Store an uploaded file in the current user's draft area.
     *
     * @param int $courseid Course ID for maxbytes check.
     * @param array $uploadedfile $_FILES entry.
     * @return int Draft item ID.
     * @throws moodle_exception
     */
    private function stage_upload_to_draft(int $courseid, array $uploadedfile): int {
        global $CFG, $USER;

        if (empty($uploadedfile) || !isset($uploadedfile['error'])) {
            throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
        }
        if ($uploadedfile['error'] !== UPLOAD_ERR_OK) {
            throw new moodle_exception('manual_upload_error_failed', 'block_dixeo_modulegen');
        }
        if (empty($uploadedfile['tmp_name']) || !is_uploaded_file($uploadedfile['tmp_name'])) {
            throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
        }

        $filename = clean_param($uploadedfile['name'] ?? '', PARAM_FILE);
        if ($filename === '') {
            throw new moodle_exception('manual_upload_error_missing', 'block_dixeo_modulegen');
        }

        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $course->maxbytes);
        if ($maxbytes != USER_CAN_IGNORE_FILE_SIZE_LIMITS
                && !empty($uploadedfile['size']) && $uploadedfile['size'] > $maxbytes) {
            throw new moodle_exception('uploadfilelimitexceeded', 'error');
        }

        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = context_user::instance($USER->id);
        $filerecord = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
        ];

        $fs = get_file_storage();
        $stored = $fs->create_file_from_pathname($filerecord, $uploadedfile['tmp_name']);
        if (!$stored) {
            throw new moodle_exception('manual_upload_error_failed', 'block_dixeo_modulegen');
        }

        return $draftitemid;
    }
}
