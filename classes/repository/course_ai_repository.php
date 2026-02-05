<?php
/**
 * Repository for managing course AI sync records.
 *
 * Handles all database operations for the local_dixeo_course_ai table,
 * including CRUD operations, status updates, and retry backoff logic.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\repository;

/**
 * Repository class for course AI sync records.
 */
class course_ai_repository {

    /** @var string Database table name. */
    private const TABLE = 'local_dixeo_course_ai';

    /** @var int Retry delay in seconds after first error (5 minutes). */
    private const RETRY_DELAY_FIRST = 300;

    /** @var int Retry delay in seconds after second error (15 minutes). */
    private const RETRY_DELAY_SECOND = 900;

    /** @var int Retry delay in seconds after third error (1 hour). */
    private const RETRY_DELAY_THIRD = 3600;

    /** @var int Maximum number of retries before stopping auto-retry. */
    private const MAX_RETRY_COUNT = 3;

    /**
     * Get the course AI record by course ID.
     *
     * @param int $courseid The course ID.
     * @return \stdClass|null The record or null if not found.
     */
    public function get_by_courseid(int $courseid): ?\stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['courseid' => $courseid]) ?: null;
    }

    /**
     * Create a new course AI record.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID who initiated the creation.
     * @return \stdClass The created record.
     */
    public function create(int $courseid, int $userid): \stdClass {
        global $DB;

        $now = time();
        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->enabled = 1;
        $record->sync_status = 'none';
        $record->error_count = 0;
        $record->enabled_by = $userid;
        $record->enabled_at = $now;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $record->id = $DB->insert_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Get or create a course AI record.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID (for creation if needed).
     * @return \stdClass The record.
     */
    public function get_or_create(int $courseid, int $userid): \stdClass {
        $record = $this->get_by_courseid($courseid);

        if ($record === null) {
            $record = $this->create($courseid, $userid);
        }

        return $record;
    }

    /**
     * Update the sync status and optionally progress information.
     *
     * @param int $courseid The course ID.
     * @param string $status The new status (none, syncing, synchronized, error, paused).
     * @param array|null $progress Optional progress data with keys: files_total, files_completed, progress_percent.
     * @return void
     */
    public function update_sync_status(int $courseid, string $status, ?array $progress = null): void {
        global $DB;

        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return;
        }

        $update = new \stdClass();
        $update->id = $record->id;
        $update->sync_status = $status;
        $update->timemodified = time();

        if ($progress !== null) {
            if (isset($progress['files_total'])) {
                $update->files_total = $progress['files_total'];
            }
            if (isset($progress['files_completed'])) {
                $update->files_completed = $progress['files_completed'];
            }
            if (isset($progress['progress_percent'])) {
                $update->progress_percent = $progress['progress_percent'];
            }
        }

        // Track sync timing based on status transitions.
        if ($status === 'syncing' && $record->sync_status !== 'syncing') {
            $update->last_sync_started = time();
        }

        if ($status === 'synchronized') {
            $update->last_sync_completed = time();
            // Clear error state on successful sync.
            $update->error_count = 0;
            $update->error_message = null;
            $update->last_error_at = null;
        }

        $DB->update_record(self::TABLE, $update);
    }

    /**
     * Set the enabled state for a course.
     *
     * @param int $courseid The course ID.
     * @param bool $enabled Whether to enable or disable.
     * @param int $userid The user ID making the change.
     * @return void
     */
    public function set_enabled(int $courseid, bool $enabled, int $userid): void {
        global $DB;

        $record = $this->get_or_create($courseid, $userid);

        $update = new \stdClass();
        $update->id = $record->id;
        $update->enabled = $enabled ? 1 : 0;
        $update->timemodified = time();

        if ($enabled) {
            $update->enabled_by = $userid;
            $update->enabled_at = time();
            // Reset error state when re-enabling.
            $update->error_count = 0;
            $update->error_message = null;
            $update->last_error_at = null;
        } else {
            $update->disabled_by = $userid;
            $update->disabled_at = time();
            // Set status to paused when disabled but keeping files.
            if ($record->sync_status !== 'none') {
                $update->sync_status = 'paused';
            }
        }

        $DB->update_record(self::TABLE, $update);
    }

    /**
     * Record an error for a course.
     *
     * Increments the error count and stores the error message.
     *
     * @param int $courseid The course ID.
     * @param string $message The error message.
     * @return void
     */
    public function record_error(int $courseid, string $message): void {
        global $DB;

        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return;
        }

        $update = new \stdClass();
        $update->id = $record->id;
        $update->sync_status = 'error';
        $update->error_message = $message;
        $update->error_count = $record->error_count + 1;
        $update->last_error_at = time();
        $update->timemodified = time();

        $DB->update_record(self::TABLE, $update);
    }

    /**
     * Clear the error state for a course.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public function clear_error(int $courseid): void {
        global $DB;

        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return;
        }

        $update = new \stdClass();
        $update->id = $record->id;
        $update->error_count = 0;
        $update->error_message = null;
        $update->last_error_at = null;
        $update->timemodified = time();

        // Revert to a sensible status.
        if ($record->sync_status === 'error') {
            $update->sync_status = $record->last_sync_completed ? 'synchronized' : 'none';
        }

        $DB->update_record(self::TABLE, $update);
    }

    /**
     * Get the retry delay in seconds based on error count.
     *
     * Uses exponential backoff: 5min -> 15min -> 1hour -> stop.
     *
     * @param int $courseid The course ID.
     * @return int The delay in seconds, or 0 if no retry should occur.
     */
    public function get_retry_delay(int $courseid): int {
        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return 0;
        }

        return match ($record->error_count) {
            1 => self::RETRY_DELAY_FIRST,
            2 => self::RETRY_DELAY_SECOND,
            3 => self::RETRY_DELAY_THIRD,
            default => 0,
        };
    }

    /**
     * Check if the course should auto-retry after an error.
     *
     * Returns true if the error count is within limits and enough time has passed.
     *
     * @param int $courseid The course ID.
     * @return bool True if auto-retry should proceed.
     */
    public function should_auto_retry(int $courseid): bool {
        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return false;
        }

        if ($record->error_count >= self::MAX_RETRY_COUNT) {
            return false;
        }

        if ($record->last_error_at === null) {
            return true;
        }

        $delay = $this->get_retry_delay($courseid);
        if ($delay === 0) {
            return false;
        }

        return (time() - $record->last_error_at) >= $delay;
    }

    /**
     * Reset the sync state for a course (used when deleting files).
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public function reset_sync_state(int $courseid): void {
        global $DB;

        $record = $this->get_by_courseid($courseid);
        if ($record === null) {
            return;
        }

        $update = new \stdClass();
        $update->id = $record->id;
        $update->sync_status = 'none';
        $update->files_total = null;
        $update->files_completed = null;
        $update->progress_percent = null;
        $update->error_count = 0;
        $update->error_message = null;
        $update->last_error_at = null;
        $update->last_sync_started = null;
        $update->last_sync_completed = null;
        $update->timemodified = time();

        $DB->update_record(self::TABLE, $update);
    }

    /**
     * Delete the course AI record.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public function delete(int $courseid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }

    /**
     * Get all courses with a specific sync status.
     *
     * @param string $status The status to filter by.
     * @return array Array of records.
     */
    public function get_by_status(string $status): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['sync_status' => $status]);
    }

    /**
     * Get all enabled courses.
     *
     * @return array Array of records.
     */
    public function get_enabled(): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['enabled' => 1]);
    }
}
