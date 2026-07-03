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

/**
 * Tests for file sync policy helpers.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\service\file_sync_policy;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_dixeo\service\file_sync_policy
 */
final class file_sync_policy_test extends \advanced_testcase {

    public function test_course_has_sync_blocks_false_for_site_course(): void {
        $this->resetAfterTest();

        $this->assertFalse(file_sync_policy::course_has_sync_blocks(SITEID));
    }

    public function test_course_has_sync_blocks_detects_tutor_block(): void {
        global $DB;

        $this->resetAfterTest();

        if (!file_sync_policy::get_sync_block_names()) {
            $this->markTestSkipped('No sync blocks installed in this environment.');
        }

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $hadblocksbefore = file_sync_policy::course_has_sync_blocks($course->id);

        $blockname = file_sync_policy::get_sync_block_names()[0];
        $DB->insert_record('block_instances', (object) [
            'blockname' => $blockname,
            'parentcontextid' => $coursecontext->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => '*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->assertTrue(file_sync_policy::course_has_sync_blocks($course->id));
        $this->assertTrue(file_sync_policy::should_show_sync_indicator($course->id));
        if (!$hadblocksbefore) {
            $this->assertFalse($hadblocksbefore);
        }
    }

    public function test_resolve_courseid_from_block_parent(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $this->assertEquals(
            $course->id,
            file_sync_policy::resolve_courseid_from_block_parent($coursecontext->id)
        );
        $this->assertNull(file_sync_policy::resolve_courseid_from_block_parent(0));
    }

    public function test_get_courseids_with_sync_blocks(): void {
        global $DB;

        $this->resetAfterTest();

        if (!file_sync_policy::get_sync_block_names()) {
            $this->markTestSkipped('No sync blocks installed in this environment.');
        }

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $blockname = file_sync_policy::get_sync_block_names()[0];

        $DB->insert_record('block_instances', (object) [
            'blockname' => $blockname,
            'parentcontextid' => $coursecontext->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => '*',
            'defaultregion' => 'side-pre',
            'defaultweight' => 0,
            'configdata' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $courseids = file_sync_policy::get_courseids_with_sync_blocks();
        $this->assertContains((int) $course->id, array_map('intval', $courseids));
    }
}
