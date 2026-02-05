<?php
/**
 * Web service to generate a module using AI.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_dixeo\external\traits\capability_check;
use local_dixeo\api\exception\api_exception;

/**
 * External function to generate a module using AI.
 */
class generate_module extends external_api {
    use capability_check;

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'module_type' => new external_value(PARAM_ALPHANUMEXT, 'Module type (page, label, quiz, glossary)'),
            'instructions' => new external_value(PARAM_RAW, 'Instructions for the AI'),
            'context' => new external_value(PARAM_RAW, 'Course context in markdown format'),
            'course_id' => new external_value(PARAM_INT, 'Course ID for RAG file search (optional)', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Submit a module generation job (non-blocking).
     *
     * Returns immediately with job_id. Use local_dixeo_get_job_status to poll.
     *
     * @param string $moduletype The module type.
     * @param string $instructions Instructions for the AI.
     * @param string $context Course context markdown.
     * @param int|null $courseid Optional course ID for RAG file search.
     * @return array The pending operation result with job_id.
     */
    public static function execute(string $moduletype, string $instructions, string $context, ?int $courseid = null): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'module_type' => $moduletype,
            'instructions' => $instructions,
            'context' => $context,
            'course_id' => $courseid,
        ]);

        self::validate_system_capability();

        try {
            $service = service_factory::get_module_generation_service();
            $result = $service->submit_generate_job(
                $params['module_type'],
                $params['instructions'],
                $params['context'],
                $params['course_id'] ?? null
            );

            return $result->to_array();

        } catch (api_exception $e) {
            return response_factory::job_error($e);
        }
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'completed' => new external_value(PARAM_BOOL, 'Whether the operation has completed'),
            'job_id' => new external_value(PARAM_RAW, 'The job UUID'),
            'result' => new external_single_structure([
                'data' => new external_single_structure([
                    'name' => new external_value(PARAM_RAW, 'Generated module name', VALUE_OPTIONAL),
                    'content' => new external_value(PARAM_RAW, 'Generated content', VALUE_OPTIONAL),
                ], 'Result data', VALUE_OPTIONAL),
            ], 'The result data', VALUE_OPTIONAL),
            'credits_used' => new external_value(PARAM_INT, 'Credits consumed', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_ALPHA, 'Current status', VALUE_OPTIONAL),
            'progress' => new external_value(PARAM_INT, 'Progress percentage (0-100)'),
            'error_message' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_OPTIONAL),
            'error_code' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }
}
