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
use local_dixeo\api\exception\authentication_exception;
use local_dixeo\api\exception\rate_limit_exception;

/**
 * HTTP client for communicating with the Dixeo API.
 *
 * Handles all low-level HTTP communication, including authentication,
 * request formatting, and error response parsing.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {

    /** @var string The API base URL. */
    protected string $baseurl;

    /** @var string The API key for authentication. */
    protected string $apikey;

    /** @var int Request timeout in seconds. */
    protected int $timeout;

    /** @var int Connection timeout in seconds. */
    protected int $connecttimeout;

    /**
     * Constructor.
     *
     * @param string|null $baseurl The API base URL. If null, uses configured value.
     * @param string|null $apikey The API key. If null, uses configured value.
     * @param int $timeout Request timeout in seconds.
     * @param int $connecttimeout Connection timeout in seconds.
     */
    public function __construct(
        ?string $baseurl = null,
        ?string $apikey = null,
        int $timeout = 30,
        int $connecttimeout = 10
    ) {
        $this->baseurl = $baseurl ?? get_config('local_dixeo', 'api_url') ?: 'https://api.dixeo.io';
        $this->apikey = $apikey ?? get_config('local_dixeo', 'api_key') ?: '';
        $this->timeout = $timeout;
        $this->connecttimeout = $connecttimeout;
    }

    /**
     * Send a POST request to the API.
     *
     * @param string $endpoint The API endpoint (e.g., '/v1/modules/generate').
     * @param array $data The request payload.
     * @param string|null $idempotencykey Optional idempotency key to prevent duplicate requests.
     * @return array The decoded response data.
     * @throws api_exception If the request fails.
     */
    public function post(string $endpoint, array $data, ?string $idempotencykey = null): array {
        return $this->request('POST', $endpoint, $data, $idempotencykey);
    }

    /**
     * Send a GET request to the API.
     *
     * @param string $endpoint The API endpoint (e.g., '/v1/jobs/{id}').
     * @param array $queryparams Optional query parameters.
     * @return array The decoded response data.
     * @throws api_exception If the request fails.
     */
    public function get(string $endpoint, array $queryparams = []): array {
        return $this->request('GET', $endpoint, $queryparams);
    }

    /**
     * Send an HTTP request to the API.
     *
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param string $endpoint The API endpoint.
     * @param array $data The request data (body for POST, query params for GET).
     * @param string|null $idempotencykey Optional idempotency key.
     * @return array The decoded response data.
     * @throws api_exception If the request fails.
     */
    protected function request(string $method, string $endpoint, array $data = [], ?string $idempotencykey = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $this->validate_configuration();

        $curl = new \curl();
        $url = rtrim($this->baseurl, '/') . '/' . ltrim($endpoint, '/');

        // Configure curl options.
        $curl->setopt([
            'CURLOPT_TIMEOUT' => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT' => $this->connecttimeout,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 3,
        ]);

        // Set headers.
        $headers = [
            'X-API-Key: ' . $this->apikey,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: local_dixeo/1.0 (Moodle Plugin)',
        ];

        if ($idempotencykey !== null) {
            $headers[] = 'X-Idempotency-Key: ' . $idempotencykey;
        }

        $curl->setHeader($headers);

        // Execute request based on method.
        if ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
            $response = $curl->get($url);
        } else {
            $response = $curl->post($url, json_encode($data));
        }

        // Check for curl errors.
        $errno = $curl->get_errno();
        if ($errno) {
            throw new api_exception(
                'connection_error',
                'Failed to connect to Dixeo API: ' . $curl->error,
                0,
                ['curl_errno' => $errno]
            );
        }

        // Parse the response.
        return $this->parse_response($response, $curl->get_info());
    }

    /**
     * Parse and validate the API response.
     *
     * @param string $response The raw response body.
     * @param array $info The curl info array.
     * @return array The decoded response data.
     * @throws api_exception If the response indicates an error.
     */
    protected function parse_response(string $response, array $info): array {
        $httpcode = $info['http_code'] ?? 0;
        $data = json_decode($response, true);

        // Handle JSON parse errors.
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new api_exception(
                'invalid_response',
                'Invalid JSON response from Dixeo API',
                $httpcode,
                ['raw_response' => substr($response, 0, 500)]
            );
        }

        // Handle successful responses.
        if ($httpcode >= 200 && $httpcode < 300) {
            // API wraps successful responses in a 'data' key.
            if (isset($data['success']) && $data['success'] === true) {
                return $data['data'] ?? $data;
            }
            return $data;
        }

        // Handle rate limiting with Retry-After header.
        if ($httpcode === 429) {
            $retryafter = null;
            // Moodle's curl doesn't expose headers directly, check response body.
            if (isset($data['retry_after'])) {
                $retryafter = (int) $data['retry_after'];
            }
            throw new rate_limit_exception(
                $data['detail'] ?? 'Rate limit exceeded',
                $retryafter,
                $data
            );
        }

        // Handle error responses.
        $errordata = $data['error'] ?? $data;
        throw api_exception::from_response($errordata, $httpcode);
    }

    /**
     * Validate that the API is properly configured.
     *
     * @throws authentication_exception If the API key is not configured.
     */
    protected function validate_configuration(): void {
        if (empty($this->apikey)) {
            throw new authentication_exception(
                'Dixeo API key is not configured. Please configure it in the plugin settings.'
            );
        }
    }

    /**
     * Check if the API is configured and reachable.
     *
     * @return bool True if the API is configured with a key.
     */
    public function is_configured(): bool {
        return !empty($this->apikey) && !empty($this->baseurl);
    }

    /**
     * Get the configured API base URL.
     *
     * @return string The API base URL.
     */
    public function get_base_url(): string {
        return $this->baseurl;
    }
}