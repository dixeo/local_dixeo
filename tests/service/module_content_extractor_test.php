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

/**
 * Tests for module_content_extractor edit paths with autosave draft.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * @covers \local_dixeo\service\module_content_extractor
 */
final class module_content_extractor_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_page_merges_intro_from_db_with_draft_content(): void {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', [
            'course' => $course->id,
            'intro' => '<p>Intro only</p>',
            'introformat' => FORMAT_HTML,
            'content' => '<p>Saved body</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);
        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($cm->id);

        $extractor = new \local_dixeo\service\module_content_extractor();
        $out = $extractor->get_full_content_for_edit($cminfo, '<p>Draft body</p>');

        $this->assertNotNull($out);
        $this->assertStringContainsString('**Introduction:**', $out);
        $this->assertStringContainsString('<p>Intro only</p>', $out);
        $this->assertStringContainsString('**Content:**', $out);
        $this->assertStringContainsString('<p>Draft body</p>', $out);
        $this->assertStringNotContainsString('<p>Saved body</p>', $out);
    }

    public function test_label_replaces_intro_with_draft(): void {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $label = $gen->create_module('label', [
            'course' => $course->id,
            'intro' => '<p>Old label</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('label', $label->id, $course->id);
        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($cm->id);

        $extractor = new \local_dixeo\service\module_content_extractor();
        $out = $extractor->get_full_content_for_edit($cminfo, '<p>New label</p>');
        $this->assertSame('<p>New label</p>', $out);
    }

    public function test_null_draft_same_as_omitted_for_page(): void {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', [
            'course' => $course->id,
            'intro' => '<p>I</p>',
            'introformat' => FORMAT_HTML,
            'content' => '<p>C</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);
        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($cm->id);

        $extractor = new \local_dixeo\service\module_content_extractor();
        $without = $extractor->get_full_content_for_edit($cminfo);
        $withnull = $extractor->get_full_content_for_edit($cminfo, null);
        $this->assertSame($without, $withnull);
    }
}
