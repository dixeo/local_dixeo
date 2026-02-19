<?php
/**
 * Web service to cancel a running job.
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
 * External function to cancel a running job.
 */
class cancel_job extends external_api {
    use capability_check;

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_RAW, 'The job UUID to cancel'),
        ]);
    }

    /**
     * Cancel a running job.
     *
     * @param string $jobid The job UUID.
     * @return array The cancellation result.
     */
    public static function execute(string $jobid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid,
        ]);

        self::validate_system_capability();

        try {
            $service = service_factory::get_job_service();
            $result = $service->cancel_job($params['jobid']);

            $status = $result['status'] ?? 'cancelled';
            return response_factory::cancellation_result(
                $params['jobid'],
                true,
                'Job ' . $status . ' successfully'
            );

        } catch (api_exception $e) {
            return response_factory::cancellation_result(
                $params['jobid'],
                false,
                $e->getMessage(),
                $e->get_error_code()
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
            'success' => new external_value(PARAM_BOOL, 'Whether the cancellation was successful'),
            'jobid' => new external_value(PARAM_RAW, 'The job UUID'),
            'message' => new external_value(PARAM_RAW, 'Status message'),
            'errorcode' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }
}
