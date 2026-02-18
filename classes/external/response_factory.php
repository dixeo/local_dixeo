<?php
/**
 * Factory for building standardized API responses.
 *
 * Provides consistent response envelope format across all external API endpoints,
 * handling both success and error cases with proper structure.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\external;

use local_dixeo\api\exception\api_exception;

/**
 * Factory class for building standardized API responses.
 */
class response_factory {

    /**
     * Build a success response with optional data payload.
     *
     * @param array $data The response data to include.
     * @return array The formatted success response.
     */
    public static function success(array $data = []): array {
        return array_merge(['success' => true], $data);
    }

    /**
     * Build an error response from an api_exception.
     *
     * Converts API exceptions to a standardized error response format.
     *
     * @param api_exception $exception The exception to convert.
     * @param array $defaults Default values for response fields.
     * @return array The formatted error response.
     */
    public static function from_api_exception(api_exception $exception, array $defaults = []): array {
        return array_merge($defaults, [
            'success' => false,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->get_error_code(),
        ]);
    }

    /**
     * Build an error response from a generic exception.
     *
     * For non-API exceptions, wraps the message with a generic error code.
     *
     * @param \Throwable $exception The exception to convert.
     * @param string $errorcode The error code to use.
     * @param array $defaults Default values for response fields.
     * @return array The formatted error response.
     */
    public static function from_exception(
        \Throwable $exception,
        string $errorcode = 'internal_error',
        array $defaults = []
    ): array {
        return array_merge($defaults, [
            'success' => false,
            'error_message' => $exception->getMessage(),
            'error_code' => $errorcode,
        ]);
    }

    /**
     * Build an error response with custom message and code.
     *
     * Use this for application-level errors that are not exceptions.
     *
     * @param string $message The error message.
     * @param string $errorcode The error code.
     * @param array $defaults Default values for response fields.
     * @return array The formatted error response.
     */
    public static function error(string $message, string $errorcode, array $defaults = []): array {
        return array_merge($defaults, [
            'success' => false,
            'error_message' => $message,
            'error_code' => $errorcode,
        ]);
    }

    /**
     * Build a job-related error response for generate_module endpoint.
     *
     * Provides the specific structure expected by the generate_module endpoint.
     *
     * @param api_exception $exception The exception to convert.
     * @return array The formatted error response for job operations.
     */
    public static function job_error(api_exception $exception): array {
        return [
            'completed' => false,
            'job_id' => '',
            'status' => 'failed',
            'progress' => 0,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->get_error_code(),
        ];
    }

    /**
     * Build a job status error response for get_job_status endpoint.
     *
     * Provides the specific structure expected by the get_job_status endpoint
     * using RFC 7807 Problem Details format.
     *
     * @param string $jobid The job ID that was queried.
     * @param api_exception $exception The exception to convert.
     * @return array The formatted error response for job status queries.
     */
    public static function job_status_error(string $jobid, api_exception $exception): array {
        $errorcode = $exception->get_error_code();

        return [
            'job_id' => $jobid,
            'type' => '',
            'status' => 'failed',
            'progress' => 0,
            'created_at' => 0,
            // RFC 7807 Problem Details format.
            'error' => [
                'type' => $errorcode,
                'title' => ucwords(str_replace('_', ' ', $errorcode)),
                'status' => 500,
                'detail' => $exception->getMessage(),
            ],
        ];
    }

    /**
     * Build a cancellation result response for cancel_job endpoint.
     *
     * @param string $jobid The job ID.
     * @param bool $success Whether cancellation succeeded.
     * @param string $message The status message.
     * @param string|null $errorcode The error code if failed.
     * @return array The formatted cancellation response.
     */
    public static function cancellation_result(
        string $jobid,
        bool $success,
        string $message,
        ?string $errorcode = null
    ): array {
        $response = [
            'success' => $success,
            'job_id' => $jobid,
            'message' => $message,
        ];

        if ($errorcode !== null) {
            $response['error_code'] = $errorcode;
        }

        return $response;
    }

    /**
     * Build a module creation result response for create_module_from_job endpoint.
     *
     * @param bool $success Whether creation succeeded.
     * @param int $cmid The created course module ID (0 if failed).
     * @param string|null $errormessage The error message if failed.
     * @param string|null $errorcode The error code if failed.
     * @return array The formatted module creation response.
     */
    public static function module_creation_result(
        bool $success,
        int $cmid,
        ?string $errormessage = null,
        ?string $errorcode = null
    ): array {
        return [
            'success' => $success,
            'cmid' => $cmid,
            'error_message' => $errormessage,
            'error_code' => $errorcode,
        ];
    }
}
