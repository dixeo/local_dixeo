<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Service for managing AI job operations.
 *
 * Handles job submission, status polling, and cancellation for
 * async AI operations via the Dixeo API.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

use local_dixeo\api\client;
use local_dixeo\api\job_poller;
use local_dixeo\api\polling_config;
use local_dixeo\api\exception\api_exception;
use local_dixeo\dto\operation_result;
use local_dixeo\dto\job_status;

/**
 * Service for job management operations.
 */
class job_service {

    /** @var client The API client. */
    private client $client;

    /** @var job_poller The job poller. */
    private job_poller $poller;

    /**
     * Constructor.
     *
     * @param client|null $client Optional API client.
     * @param job_poller|null $poller Optional job poller.
     */
    public function __construct(?client $client = null, ?job_poller $poller = null) {
        $this->client = $client ?? new client();
        $this->poller = $poller ?? new job_poller($this->client);
    }

    /**
     * Submit a job to the API without waiting for completion.
     *
     * Returns immediately with jobid. Use get_job_status() to poll.
     *
     * @param string $endpoint The API endpoint to submit to.
     * @param array $payload The request payload.
     * @return operation_result Pending operation result with jobid.
     * @throws api_exception If the API request fails.
     */
    public function submit_job(string $endpoint, array $payload): operation_result {
        $response = $this->client->post($endpoint, $payload);

        return operation_result::pending($response['id'], 'pending', 0);
    }

    /**
     * Submit a job and poll for completion.
     *
     * Blocks until the job completes, fails, or times out.
     *
     * @param string $endpoint The API endpoint to submit to.
     * @param array $payload The request payload.
     * @param string $jobtype The job type for polling configuration.
     * @return operation_result The completed operation result.
     * @throws api_exception If an API error occurs.
     */
    public function submit_and_wait(string $endpoint, array $payload, string $jobtype): operation_result {
        $response = $this->client->post($endpoint, $payload);
        $jobid = $response['id'];
        $config = polling_config::for_job_type($jobtype);

        return $this->poller->poll($jobid, $config);
    }

    /**
     * Get the status of a job.
     *
     * @param string $jobid The job UUID.
     * @return job_status The current job status.
     * @throws api_exception If an API error occurs.
     */
    public function get_job_status(string $jobid): job_status {
        return $this->poller->get_job_status($jobid);
    }

    /**
     * Cancel a running job.
     *
     * @param string $jobid The job UUID to cancel.
     * @return array The cancellation response from the API.
     * @throws api_exception If an API error occurs.
     */
    public function cancel_job(string $jobid): array {
        return $this->client->post('/v1/jobs/' . $jobid . '/cancel', []);
    }

    /**
     * Wait for a pending job to complete.
     *
     * Use this to resume polling for a job that timed out earlier.
     *
     * @param string $jobid The job UUID.
     * @param string $jobtype The job type for polling configuration.
     * @return operation_result The operation result.
     * @throws api_exception If an API error occurs.
     */
    public function wait_for_job(string $jobid, string $jobtype): operation_result {
        return $this->poller->poll_for_type($jobid, $jobtype);
    }

    /**
     * Get the underlying API client.
     *
     * @return client The API client.
     */
    public function get_client(): client {
        return $this->client;
    }

    /**
     * Get the job poller.
     *
     * @return job_poller The job poller.
     */
    public function get_poller(): job_poller {
        return $this->poller;
    }
}
