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

/**
 * Tests for {@see \local_dixeo\service\designer_self_enrol_service}.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\service\designer_self_enrol_service;

/**
 * Unit tests for designer self enrol service.
 *
 * @covers \local_dixeo\service\designer_self_enrol_service
 */
final class designer_self_enrol_service_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Skip the test when enrol_self is unavailable.
     */
    private function require_self_enrol_enabled(): void {
        if (!enrol_is_enabled('self')) {
            $this->markTestSkipped('The enrol_self plugin is disabled.');
        }
        $plugin = enrol_get_plugin('self');
        if (!$plugin) {
            $this->markTestSkipped('The enrol_self plugin is not available.');
        }
    }

    /**
     * Return the first self-enrol instance for a course, if any.
     *
     * @param int $courseid
     * @return \stdClass|null
     */
    private function get_self_instance(int $courseid): ?\stdClass {
        foreach (enrol_get_instances($courseid, false) as $instance) {
            if ($instance->enrol === 'self') {
                return $instance;
            }
        }
        return null;
    }

    public function test_configure_enables_and_sets_password_when_generate_key_true(): void {
        global $DB;

        $this->require_self_enrol_enabled();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($instance);

        $instance->status = ENROL_INSTANCE_DISABLED;
        $instance->password = '';
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);

        $service = new designer_self_enrol_service();
        $service->configure_for_course((int) $course->id, true);

        $fresh = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($fresh);
        $this->assertSame(ENROL_INSTANCE_ENABLED, (int) $fresh->status);
        $this->assertNotSame('', trim((string) $fresh->password));
        $this->assertLessThanOrEqual(50, \core_text::strlen((string) $fresh->password));
    }

    public function test_configure_adds_instance_when_none_exists(): void {
        $this->require_self_enrol_enabled();

        $plugin = enrol_get_plugin('self');
        $course = $this->getDataGenerator()->create_course();

        foreach (enrol_get_instances($course->id, false) as $inst) {
            if ($inst->enrol === 'self') {
                $plugin->delete_instance($inst);
            }
        }
        $this->assertNull($this->get_self_instance((int) $course->id));

        $service = new designer_self_enrol_service();
        $service->configure_for_course((int) $course->id, true);

        $fresh = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($fresh);
        $this->assertSame(ENROL_INSTANCE_ENABLED, (int) $fresh->status);
        $this->assertNotSame('', trim((string) $fresh->password));
    }

    public function test_configure_with_generate_key_false_clears_password_when_requirepassword_off(): void {
        global $DB;

        $this->require_self_enrol_enabled();

        $selfplugin = enrol_get_plugin('self');
        $selfplugin->set_config('requirepassword', 0);

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($instance);

        $instance->status = ENROL_INSTANCE_ENABLED;
        $instance->password = 'previous-key-123';
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);

        $service = new designer_self_enrol_service();
        $service->configure_for_course((int) $course->id, false);

        $fresh = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($fresh);
        $this->assertSame(ENROL_INSTANCE_ENABLED, (int) $fresh->status);
        $this->assertSame('', (string) $fresh->password);
    }

    public function test_configure_with_generate_key_false_still_generates_when_requirepassword_on(): void {
        global $DB;

        $this->require_self_enrol_enabled();

        $selfplugin = enrol_get_plugin('self');
        $selfplugin->set_config('requirepassword', 1);

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($instance);

        $instance->status = ENROL_INSTANCE_DISABLED;
        $instance->password = '';
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);

        $service = new designer_self_enrol_service();
        $service->configure_for_course((int) $course->id, false);

        $fresh = $this->get_self_instance((int) $course->id);
        $this->assertNotNull($fresh);
        $this->assertSame(ENROL_INSTANCE_ENABLED, (int) $fresh->status);
        $this->assertNotSame('', trim((string) $fresh->password));
    }
}
