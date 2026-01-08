<?php
/**
 * Web service to get available module types.
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
use core_external\external_multiple_structure;
use core_external\external_value;
use local_dixeo\external\traits\capability_check;
use local_dixeo\api\exception\api_exception;

/**
 * External function to get available module types.
 */
class get_module_types extends external_api {
    use capability_check;

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get available module types.
     *
     * @return array The list of available module types.
     */
    public static function execute(): array {
        self::validate_system_capability();

        try {
            $client = service_factory::get_client();
            $types = $client->get('/v1/modules/types');

            return response_factory::success(['types' => $types['types'] ?? []]);

        } catch (api_exception $e) {
            return response_factory::from_api_exception($e, ['types' => []]);
        }
    }

    /**
     * Define the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'types' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Module type identifier'),
                    'label' => new external_value(PARAM_RAW, 'Display label'),
                    'description' => new external_value(PARAM_RAW, 'Module type description'),
                    'category' => new external_value(PARAM_ALPHANUMEXT, 'Category for grouping'),
                    'component' => new external_value(PARAM_ALPHANUMEXT, 'Moodle component identifier'),
                ]),
                'List of available module types'
            ),
            'error_message' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_OPTIONAL),
            'error_code' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }
}
