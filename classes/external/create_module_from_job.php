<?php
/**
 * Web service to create a module from a completed job.
 *
 * Retrieves job result and executes DSL to create the Moodle module.
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
use local_dixeo\dsl\interpreter;
use local_dixeo\api\exception\api_exception;

/**
 * External function to create a module from a completed job.
 */
class create_module_from_job extends external_api {
    use capability_check;

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'job_id' => new external_value(PARAM_RAW, 'The completed job UUID'),
            'courseid' => new external_value(PARAM_INT, 'The course ID'),
            'sectionnumber' => new external_value(PARAM_INT, 'The section number', VALUE_DEFAULT, 0),
            'beforemod' => new external_value(PARAM_INT, 'Course module ID to insert before', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Create a module from a completed job.
     *
     * Fetches the job result and runs the DSL interpreter to create the module.
     *
     * @param string $jobid The completed job UUID.
     * @param int $courseid The course ID.
     * @param int $sectionnumber The section number.
     * @param int|null $beforemod Course module ID to insert before.
     * @return array Result with cmid on success.
     */
    public static function execute(string $jobid, int $courseid, int $sectionnumber = 0, ?int $beforemod = null): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'job_id' => $jobid,
            'courseid' => $courseid,
            'sectionnumber' => $sectionnumber,
            'beforemod' => $beforemod,
        ]);

        self::validate_course_capability($params['courseid'], true);

        try {
            $jobService = service_factory::get_job_service();
            $status = $jobService->get_job_status($params['job_id']);

            if (!$status->is_completed()) {
                return response_factory::module_creation_result(
                    false,
                    0,
                    'Job is not completed. Status: ' . $status->status,
                    'job_not_completed'
                );
            }

            $result = $status->result;

            // Result may be JSON encoded string from get_job_status, decode if needed.
            if (is_string($result)) {
                $result = json_decode($result, true);
            }

            if (empty($result['creation'])) {
                return response_factory::module_creation_result(
                    false,
                    0,
                    'No creation instructions in job result',
                    'no_creation_instructions'
                );
            }

            $context = interpreter::build_context(
                $params['courseid'],
                $params['sectionnumber'],
                $result['module_type'] ?? 'page',
                $params['beforemod']
            );

            $interpreter = new interpreter();
            $cmid = $interpreter->execute($result['creation'], $result['data'] ?? [], $context);

            return response_factory::module_creation_result(true, $cmid);

        } catch (api_exception $e) {
            return response_factory::module_creation_result(
                false,
                0,
                $e->getMessage(),
                $e->get_error_code()
            );
        } catch (\Exception $e) {
            return response_factory::module_creation_result(
                false,
                0,
                $e->getMessage(),
                'dsl_execution_error'
            );
        }
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the module was created successfully'),
            'cmid' => new external_value(PARAM_INT, 'The created course module ID (0 if failed)'),
            'error_message' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_OPTIONAL),
            'error_code' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }
}
