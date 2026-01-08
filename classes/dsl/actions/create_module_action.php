<?php
/**
 * DSL action for creating Moodle activity modules.
 *
 * Creates a Moodle activity module using the standard Moodle APIs:
 * add_course_module() and {modulename}_add_instance().
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\dsl\actions;

use local_dixeo\dsl\dsl_exception;
use local_dixeo\dsl\value_resolver;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Action handler for creating Moodle course modules.
 *
 * Expected action format:
 * {
 *   "action": "create_module",
 *   "save_as": "module",
 *   "fields": {
 *     "name": {"source": "$.name"},
 *     "intro": {"source": "$.intro"},
 *     "content": {"source": "$.content"}
 *   }
 * }
 *
 * Required context:
 * - courseid: The course ID
 * - sectionid: The section ID
 * - sectionnum: The section number
 * - modulename: The module type (page, quiz, glossary, etc.)
 * - beforemod: Optional course module ID to insert before
 */
class create_module_action {

    /** @var array Default module settings applied to all modules. */
    protected const DEFAULT_MODULE_SETTINGS = [
        'visible' => 1,
        'completion' => 2,
        'completionview' => 1,
    ];

    /**
     * Module-specific field quirks that cannot be handled by platform defaults.
     *
     * Some Moodle modules require specific field name remappings or special
     * handling that differs from the standard form field names.
     *
     * @var array<string, array<string, mixed>>
     */
    protected const MODULE_FIELD_QUIRKS = [
        'quiz' => [
            // Moodle expects 'quizpassword' which gets renamed to 'password' in quiz_process_options().
            'quizpassword' => '',
            // Review options: enable showing feedback immediately after attempt and when quiz is closed.
            // quiz_review_option_form_to_db() expects individual form fields: {option}{when}
            // Options: attempt, correctness, marks, specificfeedback, generalfeedback, rightanswer, overallfeedback
            // When: during, immediately, open (while open), closed (after close)
            'attemptimmediately' => 1,
            'attemptopen' => 1,
            'attemptclosed' => 1,
            'correctnessimmediately' => 1,
            'correctnessopen' => 1,
            'correctnessclosed' => 1,
            'marksimmediately' => 1,
            'marksopen' => 1,
            'marksclosed' => 1,
            'specificfeedbackimmediately' => 1,
            'specificfeedbackopen' => 1,
            'specificfeedbackclosed' => 1,
            'generalfeedbackimmediately' => 1,
            'generalfeedbackopen' => 1,
            'generalfeedbackclosed' => 1,
            'rightanswerimmediately' => 1,
            'rightansweropen' => 1,
            'rightanswerclosed' => 1,
            'overallfeedbackclosed' => 1,
        ],
    ];

    /**
     * Execute the create_module action.
     *
     * @param array $action The action specification.
     * @param value_resolver $resolver The value resolver for field resolution.
     * @return array The created module data including 'id' (instance), 'cmid', and 'cm'.
     * @throws dsl_exception If module creation fails.
     */
    public function execute(array $action, value_resolver $resolver): array {
        global $CFG, $DB;

        $context = $resolver->get_context();
        $this->validate_context($context);

        // Resolve field values from the action specification.
        $fields = $resolver->resolve_fields($action['fields'] ?? []);

        $courseid = (int) $context['courseid'];
        $sectionid = (int) $context['sectionid'];
        $sectionnum = (int) $context['sectionnum'];
        $modulename = $context['modulename'];
        $beforemod = $context['beforemod'] ?? null;

        // Get the module ID from the modules table.
        $moduleid = $DB->get_field('modules', 'id', ['name' => $modulename]);
        if (!$moduleid) {
            throw dsl_exception::module_creation_failed($modulename, 'Module type not found in database');
        }

        // Start a transaction for atomic creation.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Create the course module record.
            $cmid = $this->create_course_module($courseid, $moduleid, $sectionid);

            // Add the course module to the section.
            \course_add_cm_to_section($courseid, $cmid, $sectionnum, $beforemod);

            // Prepare module data for the add_instance function.
            $moduledata = $this->prepare_module_data($fields, $courseid, $cmid, $modulename);

            // Run module-specific pre-creation hooks if available.
            $this->run_before_hook($modulename, $cmid, $moduledata);

            // Create the module instance.
            $instanceid = $this->create_module_instance($modulename, $moduledata);
            if (!$instanceid) {
                throw dsl_exception::module_creation_failed($modulename, 'add_instance returned false');
            }

            // Link the course module to its instance.
            $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cmid]);

            // Store instance ID for after hook.
            $moduledata->id = $instanceid;

            // Run module-specific post-creation hooks if available.
            $this->run_after_hook($modulename, $cmid, $moduledata);

            // Rebuild course cache to reflect changes.
            \rebuild_course_cache($courseid);

            $transaction->allow_commit();

            // Return module data for variable storage.
            return [
                'id' => $instanceid,
                'cmid' => $cmid,
                'cm' => $cmid,
                'name' => $moduledata->name ?? '',
                'modulename' => $modulename,
            ];

        } catch (\Exception $e) {
            $transaction->rollback($e);

            if ($e instanceof dsl_exception) {
                throw $e;
            }

            throw new dsl_exception(
                "Module creation failed: " . $e->getMessage(),
                'create_module',
                ['modulename' => $modulename],
                $e
            );
        }
    }

    /**
     * Validate that required context values are present.
     *
     * @param array $context The context array.
     * @throws dsl_exception If required values are missing.
     */
    protected function validate_context(array $context): void {
        $required = ['courseid', 'sectionid', 'sectionnum', 'modulename'];

        foreach ($required as $key) {
            if (!isset($context[$key])) {
                throw dsl_exception::missing_context($key);
            }
        }
    }

    /**
     * Create the course module record.
     *
     * @param int $courseid The course ID.
     * @param int $moduleid The module type ID.
     * @param int $sectionid The section ID.
     * @return int The new course module ID (cmid).
     * @throws dsl_exception If creation fails.
     */
    protected function create_course_module(int $courseid, int $moduleid, int $sectionid): int {
        $cm = new \stdClass();
        $cm->course = $courseid;
        $cm->module = $moduleid;
        $cm->section = $sectionid;
        $cm->visible = self::DEFAULT_MODULE_SETTINGS['visible'];
        $cm->completion = self::DEFAULT_MODULE_SETTINGS['completion'];
        $cm->completionview = self::DEFAULT_MODULE_SETTINGS['completionview'];

        $cmid = \add_course_module($cm);

        if (!$cmid) {
            throw new dsl_exception(
                'add_course_module returned false',
                'create_module',
                ['courseid' => $courseid, 'moduleid' => $moduleid]
            );
        }

        return $cmid;
    }

    /**
     * Prepare module data for the add_instance function.
     *
     * Merges data in priority order (later overrides earlier):
     * 1. Platform defaults from get_config()
     * 2. Module-specific quirks (field name remappings)
     * 3. DSL-provided fields
     *
     * @param array $fields The resolved field values.
     * @param int $courseid The course ID.
     * @param int $cmid The course module ID.
     * @param string $modulename The module type name.
     * @return \stdClass The module data object.
     */
    protected function prepare_module_data(array $fields, int $courseid, int $cmid, string $modulename): \stdClass {
        // Get platform defaults for this module type.
        $platformdefaults = $this->get_platform_defaults($modulename);

        // Get module-specific quirks (field name remappings, etc.).
        $quirks = self::MODULE_FIELD_QUIRKS[$modulename] ?? [];

        // Merge in priority order: platform defaults -> quirks -> DSL fields.
        $mergedfields = array_merge($platformdefaults, $quirks, $fields);

        $moduledata = (object) $mergedfields;
        $moduledata->course = $courseid;
        $moduledata->coursemodule = $cmid;
        $moduledata->cmidnumber = $cmid;

        return $moduledata;
    }

    /**
     * Get platform default values for a module type.
     *
     * Retrieves admin-configured defaults from Moodle's config table.
     *
     * @param string $modulename The module type name.
     * @return array The platform default values as an associative array.
     */
    protected function get_platform_defaults(string $modulename): array {
        $config = get_config($modulename);

        if (empty($config) || !is_object($config)) {
            return [];
        }

        return (array) $config;
    }

    /**
     * Create the module instance using the module's add_instance function.
     *
     * @param string $modulename The module type name.
     * @param \stdClass $moduledata The module data.
     * @return int|false The instance ID or false on failure.
     */
    protected function create_module_instance(string $modulename, \stdClass $moduledata): int|false {
        global $CFG;

        $libfile = $CFG->dirroot . '/mod/' . $modulename . '/lib.php';
        if (!file_exists($libfile)) {
            throw dsl_exception::module_creation_failed($modulename, "Module lib.php not found: $libfile");
        }

        require_once($libfile);

        $addfunction = $modulename . '_add_instance';
        if (!function_exists($addfunction)) {
            throw dsl_exception::module_creation_failed($modulename, "Function $addfunction does not exist");
        }

        return $addfunction($moduledata, null);
    }

    /**
     * Run module-specific before_module_created hook.
     *
     * Looks for a hook class in block_dixeo_modulegen for backward compatibility.
     *
     * @param string $modulename The module type name.
     * @param int $cmid The course module ID.
     * @param \stdClass $moduledata The module data (passed by reference).
     */
    protected function run_before_hook(string $modulename, int $cmid, \stdClass &$moduledata): void {
        $classname = '\\block_dixeo_modulegen\\modules\\' . $modulename;

        if (class_exists($classname) && method_exists($classname, 'before_module_created')) {
            $classname::before_module_created($cmid, $moduledata);
        }
    }

    /**
     * Run module-specific after_module_created hook.
     *
     * Looks for a hook class in block_dixeo_modulegen for backward compatibility.
     *
     * @param string $modulename The module type name.
     * @param int $cmid The course module ID.
     * @param \stdClass $moduledata The module data (passed by reference).
     */
    protected function run_after_hook(string $modulename, int $cmid, \stdClass &$moduledata): void {
        $classname = '\\block_dixeo_modulegen\\modules\\' . $modulename;

        if (class_exists($classname) && method_exists($classname, 'after_module_created')) {
            $classname::after_module_created($cmid, $moduledata);
        }
    }
}
