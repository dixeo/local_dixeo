<?php
/**
 * Web service to get file sync status for a course.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_dixeo\external\traits\capability_check;
use local_dixeo\service\file_sync_service;

/**
 * External function to get file sync status.
 */
class get_file_sync_status extends external_api {
    use capability_check;

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course ID'),
        ]);
    }

    /**
     * Get the file sync status for a course.
     *
     * @param int $courseid The course ID.
     * @return array The sync status.
     */
    public static function execute(int $courseid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        self::validate_course_capability($params['courseid']);

        $service = new file_sync_service();

        // If currently syncing, poll API for real-time status update.
        $localstatus = $service->get_status($params['courseid']);
        if ($localstatus->status === 'syncing') {
            $status = $service->poll_status($params['courseid']);
        } else {
            $status = $localstatus;
        }

        return [
            'enabled' => $status->enabled,
            'status' => $status->status,
            'files_total' => $status->files_total,
            'files_completed' => $status->files_completed,
            'progress_percent' => $status->progress_percent,
            'error_message' => $status->error_message,
            'last_sync_started' => $status->last_sync_started,
            'last_sync_completed' => $status->last_sync_completed,
        ];
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'Whether sync is enabled'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Sync status (none, syncing, synchronized, outdated, error, paused)'),
            'files_total' => new external_value(PARAM_INT, 'Total number of files', VALUE_OPTIONAL),
            'files_completed' => new external_value(PARAM_INT, 'Number of files synced', VALUE_OPTIONAL),
            'progress_percent' => new external_value(PARAM_INT, 'Progress percentage 0-100', VALUE_OPTIONAL),
            'error_message' => new external_value(PARAM_RAW, 'Error message if status is error', VALUE_OPTIONAL),
            'last_sync_started' => new external_value(PARAM_INT, 'Timestamp of last sync start', VALUE_OPTIONAL),
            'last_sync_completed' => new external_value(PARAM_INT, 'Timestamp of last successful sync', VALUE_OPTIONAL),
        ]);
    }
}
