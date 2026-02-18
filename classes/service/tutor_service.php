<?php
/**
 * Service for AI tutor operations.
 *
 * Handles message submission and conversation retrieval for the tutor block.
 * All API communication goes through job_service and client from local_dixeo.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

use local_dixeo\api\client;
use local_dixeo\api\exception\api_exception;
use local_dixeo\context\context_builder_factory;
use local_dixeo\dto\operation_result;

/**
 * Service for tutor message operations.
 */
class tutor_service {

    /** @var job_service The job service for submitting messages. */
    private job_service $jobservice;

    /** @var client The API client for direct GET requests. */
    private client $client;

    /** @var string|null The namespace for API requests. */
    private ?string $namespace;

    /**
     * Constructor.
     *
     * @param job_service|null $jobservice Optional job service instance.
     * @param client|null $client Optional API client instance.
     */
    public function __construct(?job_service $jobservice = null, ?client $client = null) {
        $this->jobservice = $jobservice ?? new job_service();
        $this->client = $client ?? $this->jobservice->get_client();
        $this->namespace = get_config('local_dixeo', 'namespace') ?: 'default';
    }

    /**
     * Submit a tutor message.
     *
     * Builds instructions, constructs payload, and submits a job via job_service.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @param string $message The user message.
     * @param string $pagecontext Optional page context information.
     * @return operation_result Pending operation result with job_id.
     * @throws api_exception If the API request fails.
     */
    public function submit_message(int $courseid, int $userid, string $message, string $pagecontext = ''): operation_result {
        $instructions = $this->build_instructions($courseid);

        $payload = [
            'courseId' => (string) $courseid,
            'userId' => (string) $userid,
            'message' => $message,
            'instructions' => $instructions,
            'namespace' => $this->namespace,
        ];

        if (!empty($pagecontext)) {
            $payload['pageContext'] = $pagecontext;
        }

        return $this->jobservice->submit_job('/v1/tutor/messages', $payload);
    }

    /**
     * Get conversation history from the API.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @param string $sinceid Optional message ID to fetch messages after.
     * @param int $limit Maximum number of messages to return.
     * @return array Array of message objects with id, role, content, time keys.
     * @throws api_exception If the API request fails.
     */
    public function get_conversation(int $courseid, int $userid, string $sinceid = '', int $limit = 50): array {
        $params = [
            'courseId' => (string) $courseid,
            'userId' => (string) $userid,
            'namespace' => $this->namespace,
            'limit' => $limit,
        ];

        if (!empty($sinceid)) {
            $params['sinceId'] = $sinceid;
        }

        $response = $this->client->get('/v1/tutor/messages', $params);

        // Map API response format to Moodle format.
        $messages = [];
        $rawmessages = $response['messages'] ?? $response;

        if (is_array($rawmessages)) {
            foreach ($rawmessages as $msg) {
                $messages[] = [
                    'id' => $msg['id'] ?? '',
                    'role' => $msg['role'] ?? 'user',
                    'content' => $msg['content'] ?? '',
                    'time' => isset($msg['createdAt']) ? self::parse_iso_timestamp($msg['createdAt']) : 0,
                ];
            }
        }

        return $messages;
    }

    /**
     * Build system instructions for the tutor.
     *
     * Combines the instruction template lang string with course context.
     *
     * @param int $courseid The course ID.
     * @return string The complete instruction string.
     */
    private function build_instructions(int $courseid): string {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', MUST_EXIST);
        $context = context_builder_factory::buildCourseContext($courseid, null, 'assessment');

        return get_string('tutorinstructions', 'local_dixeo', (object) [
            'fullname' => $course->fullname,
            'context' => $context,
        ]);
    }

    /**
     * Parse an ISO-8601 timestamp to a Unix timestamp.
     *
     * @param string|int $timestamp The timestamp value.
     * @return int Unix timestamp.
     */
    private static function parse_iso_timestamp(string|int $timestamp): int {
        if (is_int($timestamp)) {
            return $timestamp;
        }

        $parsed = strtotime($timestamp);
        return $parsed !== false ? $parsed : 0;
    }
}
