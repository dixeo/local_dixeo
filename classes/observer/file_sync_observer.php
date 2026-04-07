<?php
/**
 * Event observer for file sync triggers.
 *
 * Listens to course module events and block creation events to trigger
 * file synchronization when relevant changes occur.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\observer;

use core\event\course_module_created;
use core\event\course_module_updated;
use core\event\course_module_deleted;
use core\event\block_created;
use local_dixeo\external\service_factory;
use local_dixeo\service\file_sync_service;

/**
 * Observer for file sync related events.
 */
class file_sync_observer {

    /** @var array Module types that contain syncable files or SCORM extracts. */
    private const FILE_MODULE_TYPES = ['resource', 'folder', 'scorm'];

    /** @var array Block types that trigger auto-enable of sync. */
    private const SYNC_TRIGGER_BLOCKS = ['dixeo_tutor', 'dixeo_modulegen'];

    /**
     * Handle course module created event.
     *
     * Queues sync if the module is a resource/folder type in an enabled course.
     *
     * @param course_module_created $event The event.
     * @return void
     */
    public static function course_module_created(course_module_created $event): void {
        self::handle_module_change($event);
    }

    /**
     * Handle course module updated event.
     *
     * Queues sync if the module is a resource/folder type in an enabled course.
     *
     * @param course_module_updated $event The event.
     * @return void
     */
    public static function course_module_updated(course_module_updated $event): void {
        self::handle_module_change($event);
    }

    /**
     * Handle course module deleted event.
     *
     * Queues sync if the module was a resource/folder type in an enabled course.
     *
     * @param course_module_deleted $event The event.
     * @return void
     */
    public static function course_module_deleted(course_module_deleted $event): void {
        self::handle_module_change($event);
    }

    /**
     * Handle block created event.
     *
     * Auto-enables sync and triggers it when certain Dixeo blocks are added.
     *
     * @param block_created $event The event.
     * @return void
     */
    public static function block_created(block_created $event): void {
        global $USER;

        $data = $event->get_data();
        $other = $data['other'] ?? [];
        $blockname = $other['blockname'] ?? '';

        if (!in_array($blockname, self::SYNC_TRIGGER_BLOCKS, true)) {
            return;
        }

        // Get course ID from the block's parent context.
        $context = \context::instance_by_id($data['contextid'], IGNORE_MISSING);
        if (!$context) {
            return;
        }

        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return;
        }

        $courseid = $coursecontext->instanceid;
        if ($courseid <= 1) {
            // Skip site-level blocks.
            return;
        }

        $service = service_factory::get_file_sync_service();

        // Enable sync for this course.
        $service->enable_sync($courseid, $USER->id);

        // Trigger immediate sync.
        try {
            $service->trigger_sync($courseid);
        } catch (\Throwable $e) {
            debugging('Failed to trigger sync after block creation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Handle a module change event.
     *
     * Common logic for created, updated, and deleted events.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    private static function handle_module_change(\core\event\base $event): void {
        $data = $event->get_data();
        $other = $data['other'] ?? [];

        // Get module name from event data.
        $modulename = $other['modulename'] ?? '';

        if (!in_array($modulename, self::FILE_MODULE_TYPES, true)) {
            return;
        }

        $courseid = $data['courseid'] ?? 0;
        if ($courseid <= 1) {
            return;
        }

        $service = service_factory::get_file_sync_service();

        if (!$service->is_enabled($courseid)) {
            return;
        }

        // Mark as outdated immediately so the UI reflects the change.
        $service->mark_outdated($courseid);

        // Queue debounced sync.
        $service->queue_sync($courseid);
    }
}
