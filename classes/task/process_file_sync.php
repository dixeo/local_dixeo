<?php
/**
 * Adhoc task for processing file synchronization.
 *
 * Handles debounced sync execution, allowing multiple rapid changes
 * to be batched into a single sync operation.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\task;

use core\task\adhoc_task;
use local_dixeo\api\exception\api_exception;
use local_dixeo\service\file_sync_service;

/**
 * Adhoc task for processing file sync.
 */
class process_file_sync extends adhoc_task {

    /**
     * Get the task name for display.
     *
     * @return string The task name.
     */
    public function get_name(): string {
        return get_string('task_process_file_sync', 'local_dixeo');
    }

    /** @var int Debounce window in seconds - must match file_sync_service::DEBOUNCE_DELAY */
    private const DEBOUNCE_WINDOW = 30;

    /**
     * Execute the file sync task.
     *
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();

        if (!isset($data->courseid)) {
            mtrace('process_file_sync: No course ID provided');
            return;
        }

        $courseid = (int) $data->courseid;

        mtrace("process_file_sync: Starting sync for course {$courseid}");

        $service = new file_sync_service();

        // Check if sync is still enabled.
        if (!$service->is_enabled($courseid)) {
            mtrace("process_file_sync: Sync disabled for course {$courseid}, skipping");
            return;
        }

        // Check if a sync was completed recently (within debounce window).
        // This prevents duplicate syncs when user clicks "Sync Now" while a task is pending.
        $status = $service->get_status($courseid);
        if ($status->last_sync_completed && (time() - $status->last_sync_completed) < self::DEBOUNCE_WINDOW) {
            mtrace("process_file_sync: Recent sync found for course {$courseid}, skipping");
            return;
        }

        try {
            $service->trigger_sync($courseid);
            mtrace("process_file_sync: Successfully synced course {$courseid}");

        } catch (api_exception $e) {
            mtrace("process_file_sync: Error syncing course {$courseid}: " . $e->getMessage());

            // Schedule retry if appropriate.
            if ($service->should_retry_on_error($courseid)) {
                $this->schedule_retry($courseid, $service);
            }
        } catch (\Throwable $e) {
            mtrace("process_file_sync: Unexpected error for course {$courseid}: " . $e->getMessage());
        }
    }

    /**
     * Schedule a retry task with appropriate backoff.
     *
     * @param int $courseid The course ID.
     * @param file_sync_service $service The sync service.
     * @return void
     */
    private function schedule_retry(int $courseid, file_sync_service $service): void {
        $repository = new \local_dixeo\repository\course_ai_repository();
        $delay = $repository->get_retry_delay($courseid);

        if ($delay <= 0) {
            mtrace("process_file_sync: No more retries for course {$courseid}");
            return;
        }

        $task = new self();
        $task->set_custom_data((object) ['courseid' => $courseid]);
        $task->set_next_run_time(time() + $delay);

        \core\task\manager::queue_adhoc_task($task, true);

        $delayminutes = round($delay / 60);
        mtrace("process_file_sync: Scheduled retry for course {$courseid} in {$delayminutes} minutes");
    }
}
