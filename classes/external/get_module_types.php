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
use local_dixeo\service\plugin_installation_service;

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
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id for language/context (0 = system context only)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Require course access and set page context so get_string matches course language (e.g. forced lang).
     *
     * @param int $courseid Course id.
     */
    private static function validate_course_for_module_types(int $courseid): void {
        global $PAGE;
        require_course_login($courseid);
        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);
        require_capability('local/dixeo:generate', $context);
        require_capability('moodle/course:manageactivities', $context);
    }

    /**
     * Get available module types.
     *
     * @param int $courseid Course id for UI language alignment with queue (0 = legacy system context).
     * @return array The list of available module types.
     */
    public static function execute(int $courseid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);
        $courseid = (int) $params['courseid'];

        if ($courseid > 0) {
            self::validate_course_for_module_types($courseid);
        } else {
            self::validate_system_capability();
        }

        try {
            $types = service_factory::get_module_types_service()->get_module_types_cached();

            $installedmods = plugin_installation_service::get_installed_plugin_map('mod');

            $stringmanager = get_string_manager();
            foreach ($types as &$type) {
                $modname = $type['type'];
                $type['installed'] = isset($installedmods[$modname]);

                // Use Moodle's translated activity name when the module is installed (same as queue UI).
                // Fall back to the API label when not installed or when no mod string exists.
                if (!empty($type['installed'])) {
                    $component = $type['component'] ?? '';
                    if ($component === '' || strpos($component, 'mod_') !== 0) {
                        $component = 'mod_' . $modname;
                    }
                    if ($stringmanager->string_exists('modulename', $component)) {
                        $type['label'] = get_string('modulename', $component);
                    } else if ($stringmanager->string_exists('pluginname', $component)) {
                        $type['label'] = get_string('pluginname', $component);
                    }
                }
            }
            unset($type);

            // Non-admins only see installed module types (no lock icon / "plugin required" options).
            if (!is_siteadmin()) {
                $types = array_values(array_filter($types, function($t) {
                    return !empty($t['installed']);
                }));
            }

            return response_factory::success(['types' => $types]);

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
                    'installed' => new external_value(PARAM_BOOL, 'Whether the module plugin is installed'),
                ]),
                'List of available module types'
            ),
            'errormessage' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_OPTIONAL),
            'errorcode' => new external_value(PARAM_ALPHANUMEXT, 'Error code if failed', VALUE_OPTIONAL),
        ]);
    }
}
