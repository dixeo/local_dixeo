<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Default course-module and instance completion fields for AI-created activities.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_activity_defaults_registry {

    /**
     * Properties for the course_modules row before add_course_module().
     *
     * @param string $modulename Plugin module name (e.g. page, quiz).
     * @return array<string, int|bool>
     */
    public static function get_course_module_defaults(string $modulename): array {
        switch ($modulename) {
            case 'label':
                return [
                    'visible' => 1,
                    'completion' => COMPLETION_TRACKING_NONE,
                    'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
                ];
            case 'url':
            case 'glossary':
                return [
                    'visible' => 1,
                    'completion' => COMPLETION_TRACKING_NONE,
                ];
            case 'quiz':
            case 'simplequiz':
            case 'simplequiz2':
            case 'h5pactivity':
                return [
                    'visible' => 1,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completiongradeitemnumber' => 0,
                    'completionpassgrade' => 1,
                ];
            case 'assign':
                return [
                    'visible' => 1,
                    'visibleoncoursepage' => 1,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
                    'completionsubmit' => 1,
                ];
            case 'youscribe':
                return [
                    'visible' => 1,
                    'visibleoncoursepage' => 1,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_REQUIRED,
                ];
            case 'page':
            case 'slideshow':
                return [
                    'visible' => 1,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_REQUIRED,
                ];
            default:
                return [
                    'visible' => 1,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_REQUIRED,
                ];
        }
    }

    /**
     * Extra fields merged into module instance data before modname_add_instance().
     *
     * @param string $modulename
     * @return array<string, int|bool>
     */
    public static function get_instance_completion_defaults(string $modulename): array {
        switch ($modulename) {
            case 'page':
                return ['visible' => 1];
            case 'quiz':
            case 'simplequiz':
            case 'simplequiz2':
                return [
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completiongradeitemnumber' => 0,
                    'completionpassgrade' => 1,
                ];
            case 'assign':
                return [
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
                    'completionsubmit' => 1,
                ];
            case 'youscribe':
                return [
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_REQUIRED,
                ];
            case 'h5pactivity':
            case 'slideshow':
                return [];
            default:
                return [];
        }
    }
}
