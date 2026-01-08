<?php
/**
 * Trait for shared capability checking in external API classes.
 *
 * Provides common methods for validating system context and checking
 * the dixeo:generate capability that all external API endpoints require.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\external\traits;

/**
 * Trait providing capability checking for external API endpoints.
 */
trait capability_check {

    /**
     * Validate system context and check dixeo:generate capability.
     *
     * Use this for endpoints that operate at system level (not course-specific).
     *
     * @return \context_system The validated system context.
     * @throws \required_capability_exception If capability check fails.
     */
    protected static function validate_system_capability(): \context_system {
        $systemcontext = \context_system::instance();
        self::validate_context($systemcontext);
        require_capability('local/dixeo:generate', $systemcontext);

        return $systemcontext;
    }

    /**
     * Validate course context and check required capabilities.
     *
     * Use this for endpoints that operate on specific courses.
     *
     * @param int $courseid The course ID.
     * @param bool $requiremanageactivities Whether to also check moodle/course:manageactivities.
     * @return \context_course The validated course context.
     * @throws \required_capability_exception If capability check fails.
     */
    protected static function validate_course_capability(
        int $courseid,
        bool $requiremanageactivities = false
    ): \context_course {
        $coursecontext = \context_course::instance($courseid);
        self::validate_context($coursecontext);
        require_capability('local/dixeo:generate', $coursecontext);

        if ($requiremanageactivities) {
            require_capability('moodle/course:manageactivities', $coursecontext);
        }

        return $coursecontext;
    }
}
