<?php
/**
 * Base exception for Dixeo API errors.
 *
 * All API-related exceptions extend this class for consistent error handling.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\api\exception;

/**
 * Base exception for Dixeo API errors.
 */
class api_exception extends \moodle_exception {

    /** @var string RFC 7807 error type. */
    protected string $errortype;

    /** @var int HTTP status code. */
    protected int $httpstatus;

    /** @var array Additional error details. */
    protected array $details;

    /**
     * Constructor.
     *
     * @param string $errortype RFC 7807 error type identifier.
     * @param string $message Human-readable error message.
     * @param int $httpstatus HTTP status code.
     * @param array $details Additional error details from the API response.
     */
    public function __construct(
        string $errortype,
        string $message,
        int $httpstatus = 500,
        array $details = []
    ) {
        $this->errortype = $errortype;
        $this->httpstatus = $httpstatus;
        $this->details = $details;

        parent::__construct('api_error', 'local_dixeo', '', $message, null);
    }

    /**
     * Get the RFC 7807 error type.
     *
     * @return string The error type identifier.
     */
    public function get_error_type(): string {
        return $this->errortype;
    }

    /**
     * Get the error code (alias for error type).
     *
     * Provides a consistent interface for error handling across the plugin.
     *
     * @return string The error code identifier.
     */
    public function get_error_code(): string {
        return $this->errortype;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int The HTTP status code.
     */
    public function get_http_status(): int {
        return $this->httpstatus;
    }

    /**
     * Get additional error details.
     *
     * @return array The error details.
     */
    public function get_details(): array {
        return $this->details;
    }

    /**
     * Create exception from API error response.
     *
     * Factory method to create the appropriate exception subclass based on error type.
     * The API returns RFC 7807 format with extensions merged at root level.
     *
     * @param array $errordata The error data from the API response (RFC 7807 format).
     * @param int $httpstatus The HTTP status code.
     * @return api_exception The appropriate exception instance.
     */
    public static function from_response(array $errordata, int $httpstatus): api_exception {
        $type = $errordata['type'] ?? 'unknown_error';
        $message = $errordata['detail'] ?? $errordata['title'] ?? 'Unknown API error';
        $details = $errordata;

        // API merges extensions at root level (not in 'extensions' sub-object).
        // Map error types from API exception class names to our exception classes.
        return match ($type) {
            // Authentication errors - API uses 'authentication_error' from ApiKeyAuthenticator.
            'authentication', 'authentication_error' => new authentication_exception($message, $details),

            // Payment/credit errors - API uses 'insufficient_credits' or 'payment_required'.
            'payment_required', 'insufficient_credits' => new payment_required_exception(
                $message,
                $errordata['currentBalance'] ?? $errordata['current_balance'] ?? null,
                $details
            ),

            // Rate limiting - API uses 'too_many_requests_http' from TooManyRequestsHttpException.
            'too_many_requests', 'too_many_requests_http' => new rate_limit_exception(
                $message,
                $errordata['retryAfter'] ?? $errordata['retry_after'] ?? null,
                $details
            ),

            // Validation errors - violations at root level.
            'validation_error' => new validation_exception(
                $message,
                $errordata['violations'] ?? [],
                $details
            ),

            // Job not found.
            'job_not_found' => new job_not_found_exception($message, $details),

            // OpenAI errors - API converts 'OpenAIException' to 'open_a_i'.
            'open_ai', 'open_a_i' => new openai_exception($message, $details),

            // Default fallback for unknown error types.
            default => new self($type, $message, $httpstatus, $details),
        };
    }
}