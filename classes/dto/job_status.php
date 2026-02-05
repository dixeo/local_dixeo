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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\dto;

/**
 * Data transfer object for job status from the API.
 *
 * Represents the current state of an async job, including progress,
 * result data (if completed), or error information (if failed).
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class job_status {

    /** @var string Job is waiting to be processed. */
    public const STATUS_PENDING = 'pending';

    /** @var string Job is currently being processed. */
    public const STATUS_PROCESSING = 'processing';

    /** @var string Job completed successfully. */
    public const STATUS_COMPLETED = 'completed';

    /** @var string Job failed during processing. */
    public const STATUS_FAILED = 'failed';

    /**
     * Constructor.
     *
     * @param string $jobid The job UUID.
     * @param string $type The job type (e.g., 'generate_module', 'edit_module').
     * @param string $status Current status.
     * @param int $progress Progress percentage (0-100).
     * @param int $createdat Unix timestamp when the job was created.
     * @param int|null $updatedat Unix timestamp when the job was last updated.
     * @param int|null $completedat Unix timestamp when the job completed.
     * @param array|null $result The result data (only for completed jobs).
     * @param int|null $creditsused Credits consumed (only for completed/failed jobs).
     * @param string|null $errorcode Error code (only for failed jobs).
     * @param string|null $errormessage Error message (only for failed jobs).
     * @param float|null $processingtimeseconds Actual processing time.
     * @param string|null $namespace The namespace for the job.
     */
    public function __construct(
        public readonly string $jobid,
        public readonly string $type,
        public readonly string $status,
        public readonly int $progress,
        public readonly int $createdat,
        public readonly ?int $updatedat = null,
        public readonly ?int $completedat = null,
        public readonly ?array $result = null,
        public readonly ?int $creditsused = null,
        public readonly ?string $errorcode = null,
        public readonly ?string $errormessage = null,
        public readonly ?float $processingtimeseconds = null,
        public readonly ?string $namespace = null
    ) {
    }

    /**
     * Create a job_status from API response array.
     *
     * The API returns timestamps in ISO-8601 format (e.g., "2025-01-29T10:00:00+00:00").
     * This method converts them to Unix timestamps for internal use.
     *
     * @param array $data The API response data.
     * @return self The job status DTO.
     */
    public static function from_array(array $data): self {
        $error = $data['error'] ?? null;

        return new self(
            jobid: $data['id'],
            type: $data['type'],
            status: $data['status'],
            progress: $data['progress'] ?? 0,
            createdat: self::parse_timestamp($data['createdAt']),
            updatedat: self::parse_timestamp($data['updatedAt'] ?? null),
            completedat: self::parse_timestamp($data['completedAt'] ?? null),
            result: $data['result'] ?? null,
            creditsused: $data['creditsUsed'] ?? null,
            errorcode: $error['type'] ?? null,
            errormessage: $error['detail'] ?? null,
            processingtimeseconds: $data['processingTimeSeconds'] ?? null,
            namespace: $data['namespace'] ?? null
        );
    }

    /**
     * Parse an ISO-8601 timestamp string to Unix timestamp.
     *
     * Handles both ISO-8601 strings and already-numeric timestamps for backwards compatibility.
     *
     * @param string|int|null $timestamp The timestamp value from API.
     * @return int|null Unix timestamp or null if input is null/invalid.
     */
    private static function parse_timestamp(string|int|null $timestamp): ?int {
        if ($timestamp === null) {
            return null;
        }

        // If already an integer, return as-is (backwards compatibility).
        if (is_int($timestamp)) {
            return $timestamp;
        }

        // Parse ISO-8601 string to Unix timestamp.
        $parsed = strtotime($timestamp);
        return $parsed !== false ? $parsed : null;
    }

    /**
     * Check if the job is pending (not yet started).
     *
     * @return bool True if the job is pending.
     */
    public function is_pending(): bool {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the job is currently processing.
     *
     * @return bool True if the job is processing.
     */
    public function is_processing(): bool {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the job has completed successfully.
     *
     * @return bool True if the job completed.
     */
    public function is_completed(): bool {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the job has failed.
     *
     * @return bool True if the job failed.
     */
    public function is_failed(): bool {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the job has reached a terminal state (completed or failed).
     *
     * @return bool True if the job is in a terminal state.
     */
    public function is_terminal(): bool {
        return $this->is_completed() || $this->is_failed();
    }

    /**
     * Check if the job is still in progress (pending or processing).
     *
     * @return bool True if the job is still in progress.
     */
    public function is_in_progress(): bool {
        return !$this->is_terminal();
    }

    /**
     * Derive a human-readable title from an error type code.
     *
     * Converts snake_case error codes to Title Case titles.
     * E.g., 'job_processing_failed' becomes 'Job Processing Failed'.
     *
     * @param string $errortype The error type code.
     * @return string The human-readable title.
     */
    private static function derive_error_title(string $errortype): string {
        return ucwords(str_replace('_', ' ', $errortype));
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array The job status as an array.
     */
    public function to_array(): array {
        $data = [
            'job_id' => $this->jobid,
            'type' => $this->type,
            'status' => $this->status,
            'progress' => $this->progress,
            'created_at' => $this->createdat,
        ];

        if ($this->updatedat !== null) {
            $data['updated_at'] = $this->updatedat;
        }

        if ($this->completedat !== null) {
            $data['completed_at'] = $this->completedat;
        }

        if ($this->result !== null) {
            $data['result'] = $this->result;
        }

        if ($this->creditsused !== null) {
            $data['credits_used'] = $this->creditsused;
        }

        // RFC 7807 Problem Details format for errors.
        if ($this->errorcode !== null || $this->errormessage !== null) {
            $data['error'] = [
                'type' => $this->errorcode,
                'title' => $this->errorcode !== null ? self::derive_error_title($this->errorcode) : null,
                'status' => 500,
                'detail' => $this->errormessage,
            ];
        }

        if ($this->processingtimeseconds !== null) {
            $data['processing_time_seconds'] = $this->processingtimeseconds;
        }

        if ($this->namespace !== null) {
            $data['namespace'] = $this->namespace;
        }

        return $data;
    }
}