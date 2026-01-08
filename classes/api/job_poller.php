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

namespace local_dixeo\api;

use local_dixeo\api\exception\api_exception;
use local_dixeo\api\exception\job_failed_exception;
use local_dixeo\dto\job_status;
use local_dixeo\dto\operation_result;

/**
 * Job poller for tracking async job completion.
 *
 * Polls the Dixeo API for job status with configurable timing per job type.
 * On timeout, returns a pending result rather than throwing an exception,
 * allowing the caller to decide how to handle incomplete jobs.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class job_poller {

    /** @var client The API client. */
    protected client $client;

    /**
     * Constructor.
     *
     * @param client $client The API client instance.
     */
    public function __construct(client $client) {
        $this->client = $client;
    }

    /**
     * Poll for job completion with the specified configuration.
     *
     * Waits for the initial delay, then polls at regular intervals until
     * the job completes, fails, or times out.
     *
     * @param string $jobid The job UUID to poll.
     * @param polling_config $config The polling configuration.
     * @return operation_result The operation result (completed or pending).
     * @throws api_exception If an API error occurs (not including timeouts).
     * @throws job_failed_exception If the job completes with failed status.
     */
    public function poll(string $jobid, polling_config $config): operation_result {
        $starttime = microtime(true);
        $timeoutseconds = $config->get_timeout_seconds();

        // Wait for initial delay before first poll.
        $this->sleep($config->get_initial_delay_seconds());

        while (true) {
            $elapsed = microtime(true) - $starttime;

            // Check timeout before polling.
            if ($elapsed >= $timeoutseconds) {
                return $this->create_timeout_result($jobid);
            }

            // Poll the job status.
            $status = $this->get_job_status($jobid);

            // Check if job has reached a terminal state.
            if ($status->is_completed()) {
                return operation_result::completed(
                    jobid: $jobid,
                    result: $status->result,
                    creditsused: $status->creditsused
                );
            }

            if ($status->is_failed()) {
                throw new job_failed_exception(
                    $jobid,
                    $status->errormessage ?? 'Job processing failed',
                    $status->errorcode,
                    ['job_status' => $status->status]
                );
            }

            // Check timeout after processing to avoid unnecessary sleep.
            $elapsed = microtime(true) - $starttime;
            $remaining = $timeoutseconds - $elapsed;
            if ($remaining <= 0) {
                return $this->create_timeout_result($jobid, $status->status, $status->progress);
            }

            // Sleep for poll interval (or remaining time if less).
            $sleeptime = min($config->get_poll_interval_seconds(), $remaining);
            $this->sleep($sleeptime);
        }
    }

    /**
     * Poll for job completion using default config for the job type.
     *
     * @param string $jobid The job UUID to poll.
     * @param string $jobtype The job type for configuration lookup.
     * @return operation_result The operation result.
     * @throws api_exception If an API error occurs.
     * @throws job_failed_exception If the job fails.
     */
    public function poll_for_type(string $jobid, string $jobtype): operation_result {
        $config = polling_config::for_job_type($jobtype);
        return $this->poll($jobid, $config);
    }

    /**
     * Get the current status of a job.
     *
     * @param string $jobid The job UUID.
     * @return job_status The current job status.
     * @throws api_exception If an API error occurs.
     */
    public function get_job_status(string $jobid): job_status {
        $response = $this->client->get("/v1/jobs/{$jobid}");
        return job_status::from_array($response);
    }

    /**
     * Create a timeout result for an incomplete job.
     *
     * @param string $jobid The job UUID.
     * @param string $status The last known status (default: 'pending').
     * @param int $progress The last known progress percentage.
     * @return operation_result A pending operation result.
     */
    protected function create_timeout_result(string $jobid, string $status = 'pending', int $progress = 0): operation_result {
        return operation_result::pending(
            jobid: $jobid,
            status: $status,
            progress: $progress
        );
    }

    /**
     * Sleep for the specified duration.
     *
     * Extracted to a method for testability (can be mocked in unit tests).
     *
     * @param float $seconds The duration to sleep in seconds.
     */
    protected function sleep(float $seconds): void {
        if ($seconds > 0) {
            usleep((int) ($seconds * 1000000));
        }
    }
}