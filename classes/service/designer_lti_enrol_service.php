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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Adds an enrol_lti tool instance for designer-created courses (orchestration only).
 *
 * Callers (e.g. block_dixeo_designer) supply field values from their own settings.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer_lti_enrol_service {

    /** Config value meaning: use the course's language for LTI user defaults. */
    public const LANG_SAME_AS_COURSE = 'sameascourse';

    /**
     * Create one LTI 1.3 enrol instance for the course.
     *
     * @param int $courseid
     * @param array $fields Must include: maxenrolled (int), maildisplay (int 0–2), lang (lang code),
     *                      city (string), country (string ISO or '').
     * @return string|false Tool UUID for LTI Advantage, or false if enrol_lti disabled or failure.
     */
    public function add_lti_enrol_instance(int $courseid, array $fields): string|false {
        global $DB;

        if (!enrol_is_enabled('lti')) {
            return false;
        }

        $course = get_course($courseid);
        $maxenrolled = (int) ($fields['maxenrolled'] ?? 0);
        if ($maxenrolled < 0) {
            $maxenrolled = 0;
        }

        $maildisplay = (int) ($fields['maildisplay'] ?? 0);
        if (!in_array($maildisplay, [0, 1, 2], true)) {
            $maildisplay = 0;
        }

        $lang = clean_param($fields['lang'] ?? '', PARAM_LANG);
        if ($lang === '') {
            global $CFG;
            $lang = $CFG->lang;
        }

        $city = clean_param($fields['city'] ?? '', PARAM_TEXT);
        $country = clean_param($fields['country'] ?? '', PARAM_ALPHA);

        $ltitool = (object) [
            'contextid' => \context_course::instance($course->id)->id,
            'ltiversion' => 'LTI-1p3',
            'institution' => '',
            'lang' => $lang,
            'timezone' => 99,
            'maxenrolled' => $maxenrolled,
            'maildisplay' => $maildisplay,
            'city' => $city,
            'country' => $country,
            'gradesync' => 1,
            'gradesynccompletion' => 0,
            'membersync' => 0,
            'membersyncmode' => 1,
            'roleinstructor' => 3,
            'rolelearner' => 5,
            'secret' => random_string(32),
            'provisioningmodelearner' => 1,
            'provisioningmodeinstructor' => 2,
        ];

        $enrol = enrol_get_plugin('lti');
        if (!$enrol) {
            return false;
        }

        $instanceid = $enrol->add_instance($course, (array) $ltitool);
        if (!$instanceid) {
            return false;
        }

        return $DB->get_field('enrol_lti_tools', 'uuid', ['enrolid' => $instanceid], IGNORE_MISSING) ?: false;
    }
}
