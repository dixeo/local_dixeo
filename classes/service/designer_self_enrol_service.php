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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_dixeo\service;

/**
 * Configures enrol_self for designer-finalized courses.
 *
 * @package    local_dixeo
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer_self_enrol_service {

    /**
     * Ensure the course has an enabled self-enrol instance; optionally set an enrolment key.
     *
     * No-op if enrol_self is disabled site-wide or the plugin is missing.
     *
     * @param int $courseid
     * @param bool $generateenrolmentkey When true, assign a new random enrolment key. When false, clear the key
     *        unless enrol_self requires a key site-wide, in which case a key is generated anyway.
     * @return void
     */
    public function configure_for_course(int $courseid, bool $generateenrolmentkey): void {
        if (!enrol_is_enabled('self')) {
            return;
        }

        $plugin = enrol_get_plugin('self');
        if (!$plugin) {
            return;
        }

        $course = get_course($courseid);
        $password = $this->resolve_enrolment_password($plugin, $generateenrolmentkey);

        $selfinstance = null;
        foreach (enrol_get_instances($courseid, false) as $instance) {
            if ($instance->enrol === 'self') {
                $selfinstance = $instance;
                break;
            }
        }

        if (!$selfinstance) {
            $fields = $plugin->get_instance_defaults();
            $fields['status'] = ENROL_INSTANCE_ENABLED;
            $fields['password'] = $password;
            $plugin->add_instance($course, $fields);
            return;
        }

        $data = new \stdClass();
        $data->status = ENROL_INSTANCE_ENABLED;
        $data->password = $password;
        $data->expirynotify = (int) $selfinstance->expirynotify;
        if (property_exists($selfinstance, 'customint6')) {
            $data->customint6 = $selfinstance->customint6;
        }

        $plugin->update_instance($selfinstance, $data);
    }

    /**
     * Resolve the enrolment key for a new self-enrol instance.
     *
     * @param \enrol_plugin $plugin enrol_self plugin instance
     * @param bool $generateenrolmentkey
     * @return string Enrolment key (plain), max 50 chars for enrol_self.
     */
    private function resolve_enrolment_password(\enrol_plugin $plugin, bool $generateenrolmentkey): string {
        $needkey = $generateenrolmentkey || (bool) $plugin->get_config('requirepassword');
        if (!$needkey) {
            return '';
        }

        if ($plugin->get_config('usepasswordpolicy')) {
            return generate_password(20);
        }

        return random_string(20);
    }
}
