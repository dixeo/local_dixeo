<?php
/**
 * Service for managing AI file synchronization with the Dixeo VectorStore.
 *
 * Orchestrates file collection from course modules, uploading to the API,
 * and tracking sync status. Supports debounced sync via adhoc tasks.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

use local_dixeo\api\client;
use local_dixeo\api\exception\api_exception;
use local_dixeo\repository\course_ai_repository;

/**
 * Service for file synchronization with Dixeo VectorStore.
 */
class file_sync_service {

    /** @var int Debounce delay in seconds for queued syncs. */
    private const DEBOUNCE_DELAY = 30;

    /** @var array Supported file extensions for sync. */
    private const SUPPORTED_EXTENSIONS = ['pdf', 'docx', 'txt', 'pptx'];

    /** @var array Module types that contain syncable files. */
    private const FILE_MODULE_TYPES = ['resource', 'folder'];

    /** @var course_ai_repository Repository for course AI records. */
    private course_ai_repository $repository;

    /** @var client API client for Dixeo communication. */
    private client $client;

    /**
     * Constructor.
     *
     * @param course_ai_repository|null $repository Optional repository instance.
     * @param client|null $client Optional API client instance.
     */
    public function __construct(
        ?course_ai_repository $repository = null,
        ?client $client = null
    ) {
        $this->repository = $repository ?? new course_ai_repository();
        $this->client = $client ?? new client();
    }

    /**
     * Enable file sync for a course.
     *
     * Creates the course AI record if it doesn't exist and marks it as enabled.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user enabling sync.
     * @return void
     */
    public function enable_sync(int $courseid, int $userid): void {
        $record = $this->repository->get_by_courseid($courseid);

        if ($record === null) {
            $this->repository->create($courseid, $userid);
            return;
        }

        if (!$record->enabled) {
            $this->repository->set_enabled($courseid, true, $userid);
        }
    }

    /**
     * Disable file sync for a course.
     *
     * Optionally removes all files from the VectorStore.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user disabling sync.
     * @param bool $removefiles Whether to delete files from VectorStore.
     * @return void
     */
    public function disable_sync(int $courseid, int $userid, bool $removefiles = false): void {
        if ($removefiles) {
            // Always reset local state when user wants to clear data.
            $this->repository->reset_sync_state($courseid);

            // Try to delete files from API, but don't fail if it doesn't work.
            try {
                $this->client->delete_files((string) $courseid);
            } catch (api_exception $e) {
                // Log but don't fail - user wants to disable regardless.
                // Local state is already reset, API files will be orphaned but harmless.
                debugging('Failed to delete files from VectorStore: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        $this->repository->set_enabled($courseid, false, $userid);
    }

    /**
     * Check if file sync is enabled for a course.
     *
     * Returns true by default if no record exists (enabled by default).
     *
     * @param int $courseid The course ID.
     * @return bool True if sync is enabled.
     */
    public function is_enabled(int $courseid): bool {
        $record = $this->repository->get_by_courseid($courseid);

        // Default to enabled if no record exists.
        if ($record === null) {
            return true;
        }

        return (bool) $record->enabled;
    }

    /**
     * Get the current sync status for a course.
     *
     * Returns a status object with all relevant fields for UI display.
     *
     * @param int $courseid The course ID.
     * @return \stdClass Status object with: enabled, status, filestotal, filescompleted,
     *                   progresspercent, errormessage, lastsyncstarted, lastsynccompleted.
     */
    public function get_status(int $courseid): \stdClass {
        $record = $this->repository->get_by_courseid($courseid);

        $status = new \stdClass();

        if ($record === null) {
            $status->enabled = true;
            $status->status = 'none';
            $status->filestotal = null;
            $status->filescompleted = null;
            $status->progresspercent = null;
            $status->errormessage = null;
            $status->lastsyncstarted = null;
            $status->lastsynccompleted = null;
            return $status;
        }

        $status->enabled = (bool) $record->enabled;
        $status->status = $record->syncstatus;
        $status->filestotal = $record->filestotal;
        $status->filescompleted = $record->filescompleted;
        $status->progresspercent = $record->progresspercent;
        $status->uploadbytes = isset($record->uploadbytes) ? (int) $record->uploadbytes : null;
        $status->uploadbytestotal = isset($record->uploadbytestotal) ? (int) $record->uploadbytestotal : null;
        $status->errormessage = $record->errormessage;
        $status->lastsyncstarted = $record->lastsyncstarted;
        $status->lastsynccompleted = $record->lastsynccompleted;

        return $status;
    }

    /**
     * Trigger an immediate file sync for a course.
     *
     * Collects all syncable files from the course and uploads them to the API.
     *
     * @param int $courseid The course ID.
     * @return void
     * @throws api_exception If the API request fails.
     */
    public function trigger_sync(int $courseid): void {
        global $USER;

        $userid = $USER->id ?? 0;

        // Ensure record exists.
        $record = $this->repository->get_or_create($courseid, $userid);

        if (!$record->enabled) {
            return;
        }

        // Collect files and check if anything changed before hitting the API.
        $files = $this->collect_course_files($courseid);
        $filehash = $this->compute_file_manifest_hash($files);

        if ($filehash === ($record->filehash ?? null)) {
            return;
        }

        // Release the session lock for this request so other requests (e.g. file-sync status polls)
        // can run while the outbound upload updates progress in the database.
        \core\session\manager::write_close();

        // Update status to syncing.
        $this->repository->update_sync_status($courseid, 'syncing');

        try {
            $filecount = count($files);

            $payloadbytes = 0;
            foreach ($files as $f) {
                if ($f instanceof \stored_file) {
                    $payloadbytes += (int) $f->get_filesize();
                }
            }
            $estimateduploadtotal = $payloadbytes + max(4096, (int) round($payloadbytes * 0.08));

            // Update progress with file count and byte budget for designer step-1 UI (5% + 15% by bytes).
            $this->repository->update_sync_status($courseid, 'syncing', [
                'filestotal' => $filecount,
                'filescompleted' => 0,
                'progresspercent' => 0,
                'uploadbytes' => 0,
                'uploadbytestotal' => $estimateduploadtotal,
            ]);

            if ($filecount === 0) {
                // No files to sync - mark as synchronized.
                $this->repository->update_sync_status($courseid, 'synchronized', [
                    'filestotal' => 0,
                    'filescompleted' => 0,
                    'progresspercent' => 100,
                    'uploadbytes' => null,
                    'uploadbytestotal' => null,
                ]);
                $this->repository->update_filehash($courseid, $filehash);
                return;
            }

            $uploadprogress = function (float $pct, int $ulnow = 0, int $ultotal = 0) use (
                $courseid,
                $filecount,
                $estimateduploadtotal
            ): void {
                $totalbytes = $ultotal > 0 ? $ultotal : $estimateduploadtotal;
                $uploaded = $ulnow;
                if ($uploaded <= 0 && $pct >= 100.0) {
                    $uploaded = $totalbytes;
                } else if ($uploaded <= 0 && $pct > 0.0 && $totalbytes > 0) {
                    $uploaded = (int) round($totalbytes * min(99.0, $pct) / 100.0);
                }
                $this->repository->update_sync_status($courseid, 'syncing', [
                    'filestotal' => $filecount,
                    'filescompleted' => 0,
                    'progresspercent' => (int) round(max(0, min(100, $pct))),
                    'uploadbytes' => $uploaded,
                    'uploadbytestotal' => $totalbytes,
                ]);
            };

            // Upload files to API (progress callback updates DB for UI polling).
            $result = $this->client->upload_files((string) $courseid, $files, null, $uploadprogress);

            // API returns 'syncing' status - indexing happens asynchronously.
            // Don't mark as 'synchronized' until API confirms indexing is complete.
            $apistatus = $result['status'] ?? 'syncing';
            $record = $this->repository->get_by_courseid($courseid);
            $uploadwatermark = (float) ($record->progresspercent ?? 0);
            $apipct = $result['progress']['percent'] ?? null;
            $mergedpct = $apipct !== null
                ? max($uploadwatermark, (float) $apipct)
                : max($uploadwatermark, 99.0);

            $syncedcount = (int) ($result['syncedCount'] ?? 0);
            $apitotal = (int) ($result['fileCount'] ?? $filecount);
            if ($apitotal > 0 && $syncedcount < $apitotal && $mergedpct >= 100.0) {
                $mergedpct = 99.0;
            }

            $bytetotal = (int) ($record->uploadbytestotal ?? $estimateduploadtotal);
            $this->repository->update_sync_status($courseid, $apistatus, [
                'filestotal' => $result['fileCount'] ?? $filecount,
                'filescompleted' => $result['syncedCount'] ?? 0,
                'progresspercent' => (int) round(max(0, min(100, $mergedpct))),
                'uploadbytes' => $bytetotal,
                'uploadbytestotal' => $bytetotal,
            ]);

            $this->repository->clear_error($courseid);
            $this->repository->update_filehash($courseid, $filehash);

        } catch (api_exception $e) {
            // Include raw response in error message if available (helps debug invalid JSON errors).
            $errormessage = $e->getMessage();
            $details = $e->get_details();
            if (!empty($details['raw_response'])) {
                $errormessage .= ' | Raw: ' . $details['raw_response'];
            }
            $this->repository->record_error($courseid, $errormessage);
            throw $e;
        }
    }

    /**
     * Mark a course as outdated (content changed, sync needed).
     *
     * Sets the sync status to 'outdated' to signal that course content
     * has changed since the last synchronization. Does not override
     * an active sync in progress.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public function mark_outdated(int $courseid): void {
        $record = $this->repository->get_by_courseid($courseid);

        if ($record === null || !$record->enabled) {
            return;
        }

        // Don't override an active sync.
        if ($record->syncstatus === 'syncing') {
            return;
        }

        $this->repository->update_sync_status($courseid, 'outdated');
    }

    /**
     * Queue a debounced sync for a course.
     *
     * Creates an adhoc task that will execute after a delay, allowing
     * multiple rapid changes to be batched into a single sync.
     *
     * Uses database status check to prevent race conditions between
     * concurrent requests checking for existing tasks.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    public function queue_sync(int $courseid): void {
        if (!$this->is_enabled($courseid)) {
            return;
        }

        // Check current status - if already syncing, skip to avoid duplicate work.
        $record = $this->repository->get_by_courseid($courseid);
        if ($record !== null && $record->syncstatus === 'syncing') {
            return;
        }

        // Check for existing pending task.
        // Note: This check has a small race window, but the status check above
        // prevents the more common duplicate sync scenario.
        $existingtasks = \core\task\manager::get_adhoc_tasks('\\local_dixeo\\task\\process_file_sync');
        foreach ($existingtasks as $task) {
            $data = $task->get_custom_data();
            if (isset($data->courseid) && (int) $data->courseid === $courseid) {
                // Task already queued for this course - skip.
                return;
            }
        }

        // Create new adhoc task with delay.
        // The 'true' parameter ensures only one task per course is queued.
        $task = new \local_dixeo\task\process_file_sync();
        $task->set_custom_data((object) ['courseid' => $courseid]);
        $task->set_next_run_time(time() + self::DEBOUNCE_DELAY);

        \core\task\manager::queue_adhoc_task($task, true);
    }

    /**
     * Collect all syncable files from a course.
     *
     * Iterates through resource and folder modules, extracting files
     * with supported extensions.
     *
     * @param int $courseid The course ID.
     * @return array Array of stored_file objects.
     */
    public function collect_course_files(int $courseid): array {
        $modinfo = get_fast_modinfo($courseid);
        $files = [];
        $fs = get_file_storage();

        foreach ($modinfo->get_cms() as $cm) {
            if (!in_array($cm->modname, self::FILE_MODULE_TYPES, true)) {
                continue;
            }

            if (!$cm->visible) {
                continue;
            }

            $context = \context_module::instance($cm->id);
            $modulefiles = $this->get_module_files($cm->modname, $context, $fs);
            $files = array_merge($files, $modulefiles);
        }

        return $files;
    }

    /**
     * Check if auto-retry should proceed for a course.
     *
     * @param int $courseid The course ID.
     * @return bool True if auto-retry should proceed.
     */
    public function should_retry_on_error(int $courseid): bool {
        return $this->repository->should_auto_retry($courseid);
    }

    /**
     * Poll the API for current sync status and update local record.
     *
     * @param int $courseid The course ID.
     * @return \stdClass Updated status object.
     */
    public function poll_status(int $courseid): \stdClass {
        // While Moodle is still sending the multipart upload, the remote API often
        // returns none/empty counts. Merging that would clobber local syncing + byte progress.
        $record = $this->repository->get_by_courseid($courseid);
        if ($record && $record->syncstatus === 'syncing') {
            $ubtotal = (int) ($record->uploadbytestotal ?? 0);
            $ubnow = (int) ($record->uploadbytes ?? 0);
            if ($ubtotal > 0 && $ubnow < $ubtotal) {
                return $this->get_status($courseid);
            }
        }

        try {
            $apiStatus = $this->client->get_files_status((string) $courseid);

            $record = $this->repository->get_by_courseid($courseid);
            $localpct = ($record && isset($record->progresspercent) && $record->progresspercent !== null)
                ? (float) $record->progresspercent
                : null;
            $apipct = null;
            if (isset($apiStatus['progress']['percent'])) {
                $apipct = (float) $apiStatus['progress']['percent'];
            }

            $apifiles = isset($apiStatus['fileCount']) ? (int) $apiStatus['fileCount'] : 0;
            $apisynced = isset($apiStatus['syncedCount']) ? (int) $apiStatus['syncedCount'] : 0;
            if ($apifiles > 0 && $apisynced < $apifiles && $apipct !== null && $apipct >= 100.0) {
                $apipct = 99.0;
            }

            $progress = [
                'filestotal' => $apiStatus['fileCount'] ?? null,
                'filescompleted' => $apiStatus['syncedCount'] ?? null,
            ];

            // Never regress below local upload/indexing watermark (API may lag behind HTTP upload progress).
            if ($localpct !== null || $apipct !== null) {
                $merged = max($localpct ?? 0, $apipct ?? 0);
                if ($apifiles > 0 && $apisynced < $apifiles && $merged >= 100.0) {
                    $merged = 99.0;
                }
                $progress['progresspercent'] = (int) round($merged);
            }

            $status = $apiStatus['status'] ?? 'none';
            $this->repository->update_sync_status($courseid, $status, $progress);

        } catch (api_exception $e) {
            // Don't update status on poll failure - just return current state.
            debugging('Failed to poll sync status: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $this->get_status($courseid);
    }

    /**
     * Compute a SHA-256 hash of the file manifest (sorted contenthash + filename pairs).
     *
     * Returns a deterministic hash that changes only when files are added, removed,
     * or modified. Used to skip API calls when nothing has changed.
     *
     * @param \stored_file[] $files The collected course files.
     * @return string SHA-256 hex digest.
     */
    private function compute_file_manifest_hash(array $files): string {
        $entries = [];
        foreach ($files as $file) {
            $entries[] = $file->get_contenthash() . '|' . $file->get_filename();
        }
        sort($entries);

        return hash('sha256', implode("\n", $entries));
    }

    /**
     * Get files from a specific module.
     *
     * @param string $modname The module type (resource, folder).
     * @param \context_module $context The module context.
     * @param \file_storage $fs The file storage instance.
     * @return array Array of stored_file objects.
     */
    private function get_module_files(string $modname, \context_module $context, \file_storage $fs): array {
        $files = [];

        // Component and filearea depend on module type.
        $component = 'mod_' . $modname;

        // Get files based on module type.
        if ($modname === 'resource') {
            $areafiles = $fs->get_area_files($context->id, $component, 'content', 0, 'sortorder', false);
        } else if ($modname === 'folder') {
            $areafiles = $fs->get_area_files($context->id, $component, 'content', 0, 'sortorder', false);
        } else {
            return $files;
        }

        foreach ($areafiles as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
