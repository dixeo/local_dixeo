<?php
/**
 * Hook callback for injecting the sync indicator into pages.
 *
 * Injects the file sync status indicator into course pages for users
 * with the appropriate capability.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\hook\output;

use core\hook\output\before_standard_top_of_body_html_generation;
use local_dixeo\service\file_sync_service;

/**
 * Callback class for injecting sync indicator.
 */
class sync_indicator_injector {

    /**
     * Inject the sync indicator into the page output.
     *
     * @param before_standard_top_of_body_html_generation $hook The hook instance.
     * @return void
     */
    public static function callback(before_standard_top_of_body_html_generation $hook): void {
        global $COURSE, $PAGE, $OUTPUT;

        // Only show on course pages (not site level).
        if (!isset($COURSE->id) || $COURSE->id <= 1) {
            return;
        }

        // Check capability.
        $context = \context_course::instance($COURSE->id);
        if (!has_capability('local/dixeo:generate', $context)) {
            return;
        }

        // Get sync status.
        $service = new file_sync_service();
        $status = $service->get_status($COURSE->id);

        // Prepare template context.
        $templatecontext = self::prepare_template_context($COURSE->id, $status);

        // Render the indicator.
        $html = $OUTPUT->render_from_template('local_dixeo/sync_indicator', $templatecontext);

        // Add the HTML to the page.
        $hook->add_html($html);

        // Include the JavaScript module.
        $PAGE->requires->js_call_amd('local_dixeo/sync_indicator', 'init', [
            $COURSE->id,
            $status->status,
            $status->enabled,
            $status->filestotal,
        ]);
    }

    /**
     * Prepare the template context from status data.
     *
     * @param int $courseid The course ID.
     * @param \stdClass $status The status object.
     * @return array The template context.
     */
    private static function prepare_template_context(int $courseid, \stdClass $status): array {
        $statusclass = $status->enabled ? $status->status : 'disabled';

        $context = [
            'courseid' => $courseid,
            'status' => $status->status,
            'enabled' => $status->enabled,
            'statusclass' => $statusclass,
            'tooltip' => self::get_tooltip($status),
            'statusmessage' => self::get_status_message($status),
            'showprogress' => $status->status === 'syncing' && $status->progresspercent !== null,
            'progresspercent' => $status->progresspercent,
            'filestotal' => $status->filestotal,
            'filescompleted' => $status->filescompleted,
        ];

        if ($status->lastsynccompleted) {
            $context['lastsync'] = userdate($status->lastsynccompleted, get_string('strftimedatetimeshort', 'core_langconfig'));
        }

        if ($status->errormessage && $status->status === 'error') {
            $context['errormessage'] = $status->errormessage;
        }

        return $context;
    }

    /**
     * Get the tooltip text for the indicator.
     *
     * @param \stdClass $status The status object.
     * @return string The tooltip text.
     */
    private static function get_tooltip(\stdClass $status): string {
        if (!$status->enabled) {
            return get_string('filesync_status_disabled', 'local_dixeo');
        }

        return match ($status->status) {
            'none' => get_string('filesync_status_none', 'local_dixeo'),
            'syncing' => get_string('filesync_status_syncing', 'local_dixeo'),
            'synchronized' => get_string('filesync_status_synchronized', 'local_dixeo'),
            'outdated' => get_string('filesync_status_outdated', 'local_dixeo'),
            'error' => get_string('filesync_status_error', 'local_dixeo'),
            'paused' => get_string('filesync_status_paused', 'local_dixeo'),
            default => get_string('filesync_status_none', 'local_dixeo'),
        };
    }

    /**
     * Get the status message for the dropdown.
     *
     * @param \stdClass $status The status object.
     * @return string The status message.
     */
    private static function get_status_message(\stdClass $status): string {
        if (!$status->enabled) {
            return get_string('filesync_status_disabled', 'local_dixeo');
        }

        if ($status->status === 'synchronized' && $status->filestotal !== null) {
            return get_string('filesync_files_count', 'local_dixeo', $status->filestotal);
        }

        if ($status->status === 'syncing' && $status->progresspercent !== null) {
            return get_string('filesync_progress', 'local_dixeo', $status->progresspercent);
        }

        if ($status->status === 'outdated') {
            return get_string('filesync_status_outdated', 'local_dixeo');
        }

        if ($status->status === 'error') {
            return get_string('filesync_status_error', 'local_dixeo');
        }

        return self::get_tooltip($status);
    }
}
