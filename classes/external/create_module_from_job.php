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
use local_dixeo\service\file_sync_service;

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
            'jobid' => new external_value(PARAM_RAW, 'The completed job UUID'),
            'courseid' => new external_value(PARAM_INT, 'The course ID'),
            'sectionnumber' => new external_value(PARAM_INT, 'The section number', VALUE_DEFAULT, 0),
            'beforemod' => new external_value(PARAM_INT, 'Course module ID to insert before', VALUE_DEFAULT, null),
            // Allow callers (e.g. the course structure flow) to override AI-generated name/intro
            // with values from the structure definition rather than re-querying the job.
            'name' => new external_value(PARAM_TEXT, 'Override module name from course structure', VALUE_OPTIONAL),
            'intro' => new external_value(PARAM_RAW, 'Override module intro from course structure', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Create a module from a completed job.
     *
     * Fetches the job result and runs the DSL interpreter to create the module.
     * Optional name/intro allow the course structure flow to override AI-generated
     * values with those defined in the structure template.
     *
     * @param string $jobid The completed job UUID.
     * @param int $courseid The course ID.
     * @param int $sectionnumber The section number.
     * @param int|null $beforemod Course module ID to insert before.
     * @param string|null $name Override module name.
     * @param string|null $intro Override module intro HTML.
     * @return array Result with cmid on success.
     */
    public static function execute(
        string $jobid,
        int $courseid,
        int $sectionnumber = 0,
        ?int $beforemod = null,
        ?string $name = null,
        ?string $intro = null
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid,
            'courseid' => $courseid,
            'sectionnumber' => $sectionnumber,
            'beforemod' => $beforemod,
            'name' => $name,
            'intro' => $intro,
        ]);

        self::validate_course_capability($params['courseid'], true);

        try {
            $jobService = service_factory::get_job_service();
            $status = $jobService->get_job_status($params['jobid']);

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
                $result['moduleType'] ?? 'page',
                $params['beforemod']
            );

            // Allow callers to override AI-generated name/intro with structure-defined values.
            // This lets the course structure flow inject pre-determined titles without
            // needing a second AI call or a separate job.
            // Skip intro override for labels — intro IS the content, not a description.
            $data = $result['data'] ?? [];
            $moduletype = $result['moduleType'] ?? 'page';
            if (!empty($params['name'])) {
                $data['name'] = $params['name'];
            }
            if (!empty($params['intro']) && $moduletype !== 'label') {
                $data['intro'] = $params['intro'];
            }

            $interpreter = new interpreter();
            $cmid = $interpreter->execute($result['creation'], $data, $context);

            // Enable file sync on successful AI module creation.
            self::enable_file_sync_if_needed($params['courseid']);

            return response_factory::module_creation_result(true, $cmid);

        } catch (api_exception $e) {
            return response_factory::module_creation_result(
                false,
                0,
                $e->getMessage(),
                $e->get_error_code()
            );
        } catch (\Exception $e) {
            // For moodle_exception (including dsl_exception), getMessage() returns the
            // language string key, not the detailed error. Extract debuginfo for details.
            $message = $e->getMessage();
            if ($e instanceof \moodle_exception && !empty($e->debuginfo)) {
                $message = $e->debuginfo;
            }

            return response_factory::module_creation_result(
                false,
                0,
                $message,
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
            'errormessage' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_OPTIONAL),
            'errorcode' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Enable file sync for a course if not already enabled.
     *
     * Called after successful AI module creation to ensure the course
     * files are synced to the VectorStore.
     *
     * @param int $courseid The course ID.
     * @return void
     */
    private static function enable_file_sync_if_needed(int $courseid): void {
        global $USER;

        try {
            $service = service_factory::get_file_sync_service();

            // Enable sync if not already enabled - this is idempotent.
            $service->enable_sync($courseid, $USER->id);

            // Queue a sync to pick up any new files.
            $service->queue_sync($courseid);

        } catch (\Throwable $e) {
            // Don't fail the module creation if sync setup fails.
            debugging('Failed to enable file sync after module creation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
