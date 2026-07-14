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
 * Tests for {@see \local_dixeo\service\module_activity_defaults_registry}.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\service\module_activity_defaults_registry;

/**
 * Unit tests for module activity defaults registry.
 *
 * @covers \local_dixeo\service\module_activity_defaults_registry
 */
final class module_activity_defaults_registry_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(false);
    }

    public function test_label_course_module_has_no_completion_tracking(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('label');
        $this->assertSame(0, $cm['completion']);
        $this->assertSame(0, $cm['completionview']);
    }

    public function test_url_course_module_has_no_completion_tracking(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('url');
        $this->assertSame(0, $cm['completion']);
    }

    public function test_page_course_module_matches_tracking_defaults(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('page');
        $this->assertSame(2, $cm['completion']);
        $this->assertSame(1, $cm['completionview']);
        $inst = module_activity_defaults_registry::get_instance_completion_defaults('page');
        $this->assertSame(1, $inst['visible']);
    }

    public function test_quiz_course_module_has_pass_grade_completion(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('quiz');
        $this->assertSame(2, $cm['completion']);
        $this->assertSame(0, $cm['completiongradeitemnumber']);
        $this->assertSame(1, $cm['completionpassgrade']);
        $inst = module_activity_defaults_registry::get_instance_completion_defaults('quiz');
        $this->assertSame(1, $inst['completionpassgrade']);
    }

    public function test_h5pactivity_course_module_matches_edai_pass_grade_completion(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('h5pactivity');
        $this->assertSame(1, $cm['visible']);
        $this->assertSame(2, $cm['completion']);
        $this->assertSame(0, $cm['completiongradeitemnumber']);
        $this->assertSame(1, $cm['completionpassgrade']);
        $inst = module_activity_defaults_registry::get_instance_completion_defaults('h5pactivity');
        $this->assertSame([], $inst);
    }

    public function test_assign_course_module_has_submit_completion(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('assign');
        $this->assertSame(1, $cm['visibleoncoursepage']);
        $this->assertSame(2, $cm['completion']);
        $this->assertSame(0, $cm['completionview']);
        $this->assertSame(1, $cm['completionsubmit']);
        $inst = module_activity_defaults_registry::get_instance_completion_defaults('assign');
        $this->assertSame(1, $inst['completionsubmit']);
    }

    public function test_unknown_module_falls_back_to_page_like(): void {
        $cm = module_activity_defaults_registry::get_course_module_defaults('forum');
        $this->assertSame(2, $cm['completion']);
        $this->assertSame(1, $cm['completionview']);
    }
}
