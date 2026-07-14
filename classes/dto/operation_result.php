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
 * Data transfer object for operation results.
 *
 * Represents the result of an async operation (generate/regenerate module).
 * Can be completed (with result data), pending (still processing), or failed (with error info).
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class operation_result {

    /**
     * Constructor.
     *
     * @param bool $completed Whether the operation has completed.
     * @param string $jobid The job UUID.
     * @param array|null $result The result data (only if completed).
     * @param int|null $creditsused Credits consumed (only if completed).
     * @param string|null $status Current status if not completed.
     * @param int $progress Progress percentage (0-100).
     * @param string|null $errormessage Error message if operation failed.
     * @param string|null $errorcode Error code for programmatic handling.
     */
    public function __construct(
        /** @var bool Whether the operation has completed. */
        public readonly bool $completed,
        /** @var string The job UUID. */
        public readonly string $jobid,
        /** @var array|null The result data from the completed job. */
        public readonly ?array $result = null,
        /** @var int|null Credits consumed by the operation. */
        public readonly ?int $creditsused = null,
        /** @var string|null The current status ('pending' or 'processing'). */
        public readonly ?string $status = null,
        /** @var int The progress percentage. */
        public readonly int $progress = 0,
        /** @var string|null Human-readable error description. */
        public readonly ?string $errormessage = null,
        /** @var string|null Machine-readable error code for programmatic handling. */
        public readonly ?string $errorcode = null
    ) {
    }

    /**
     * Create a completed operation result.
     *
     * @param string $jobid The job UUID.
     * @param array $result The result data from the completed job.
     * @param int|null $creditsused Credits consumed by the operation.
     * @return self A completed operation result.
     */
    public static function completed(string $jobid, array $result, ?int $creditsused = null): self {
        return new self(
            completed: true,
            jobid: $jobid,
            result: $result,
            creditsused: $creditsused,
            status: 'completed',
            progress: 100
        );
    }

    /**
     * Create a pending operation result.
     *
     * @param string $jobid The job UUID.
     * @param string $status The current status ('pending' or 'processing').
     * @param int $progress The progress percentage.
     * @return self A pending operation result.
     */
    public static function pending(string $jobid, string $status = 'pending', int $progress = 0): self {
        return new self(
            completed: false,
            jobid: $jobid,
            result: null,
            creditsused: null,
            status: $status,
            progress: $progress
        );
    }

    /**
     * Create a failed operation result.
     *
     * Used when the operation encounters an error and cannot continue.
     *
     * @param string $errormessage Human-readable error description.
     * @param string $errorcode Machine-readable error code for programmatic handling.
     * @return self A failed operation result.
     */
    public static function failed(string $errormessage, string $errorcode = 'error'): self {
        return new self(
            completed: false,
            jobid: '',
            result: null,
            creditsused: null,
            status: 'failed',
            progress: 0,
            errormessage: $errormessage,
            errorcode: $errorcode
        );
    }

    /**
     * Check if the operation is still in progress.
     *
     * @return bool True if the operation is pending or processing.
     */
    public function is_pending(): bool {
        return !$this->completed;
    }

    /**
     * Check if the operation completed successfully.
     *
     * @return bool True if the operation completed with results.
     */
    public function is_completed(): bool {
        return $this->completed;
    }

    /**
     * Check if the operation was successful.
     *
     * Alias for is_completed() for more intuitive usage.
     *
     * @return bool True if the operation completed successfully.
     */
    public function is_success(): bool {
        return $this->completed && $this->errormessage === null;
    }

    /**
     * Check if the operation failed.
     *
     * @return bool True if the operation failed with an error.
     */
    public function is_failed(): bool {
        return $this->status === 'failed' || $this->errormessage !== null;
    }

    /**
     * Get the error message if the operation failed.
     *
     * @return string|null The error message, or null if not failed.
     */
    public function get_error_message(): ?string {
        return $this->errormessage;
    }

    /**
     * Get the error code if the operation failed.
     *
     * @return string|null The error code, or null if not failed.
     */
    public function get_error_code(): ?string {
        return $this->errorcode;
    }

    /**
     * Get the generated content from the result.
     *
     * For module generation, this extracts the 'content' field from the result.
     * The API returns: { moduleType: string, data: { content: string, ... } }
     *
     * @return string|null The generated content, or null if not available.
     */
    public function get_content(): ?string {
        if (!$this->completed || $this->result === null) {
            return null;
        }
        // Content is nested under 'data' in the API response.
        return $this->result['data']['content'] ?? null;
    }

    /**
     * Get the module name from the result.
     *
     * The API returns: { moduleType: string, data: { name: string, ... } }
     *
     * @return string|null The generated module name, or null if not available.
     */
    public function get_name(): ?string {
        if (!$this->completed || $this->result === null) {
            return null;
        }
        // Name is nested under 'data' in the API response.
        return $this->result['data']['name'] ?? null;
    }

    /**
     * Convert to array for JSON serialization or storage.
     *
     * @return array The operation result as an array.
     */
    public function to_array(): array {
        $data = [
            'completed' => $this->completed,
            'jobid' => $this->jobid,
            'status' => $this->status,
            'progress' => $this->progress,
        ];

        // Only include optional fields when set (Moodle rejects null for VALUE_OPTIONAL structures).
        if ($this->result !== null) {
            $data['result'] = $this->result;
        }
        if ($this->creditsused !== null) {
            $data['creditsused'] = $this->creditsused;
        }
        if ($this->errormessage !== null) {
            $data['errormessage'] = $this->errormessage;
        }
        if ($this->errorcode !== null) {
            $data['errorcode'] = $this->errorcode;
        }

        return $data;
    }
}
