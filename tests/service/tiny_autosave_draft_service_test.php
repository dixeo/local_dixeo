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
 * Tests for tiny_autosave_draft_service.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use context_module;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Unit tests for tiny autosave draft service.
 *
 * @covers \local_dixeo\service\tiny_autosave_draft_service
 */
final class tiny_autosave_draft_service_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_get_draft_text_returns_null_when_no_row(): void {
        $this->setAdminUser();
        $service = new \local_dixeo\service\tiny_autosave_draft_service();
        $hash = 'a' . str_repeat('0', 39);
        $this->assertNull($service->get_draft_text(2, 999999, $hash, 'id_modulecontent'));
    }

    public function test_get_draft_text_returns_content_when_present(): void {
        global $DB;

        $this->setAdminUser();
        $user = $DB->get_record('user', ['username' => 'admin'], '*', MUST_EXIST);

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);
        $ctx = context_module::instance($cm->id);

        $pagehash = hash('sha1', 'testhash' . $ctx->id);
        $DB->insert_record('tiny_autosave', [
            'elementid' => 'id_modulecontent',
            'contextid' => $ctx->id,
            'userid' => $user->id,
            'pagehash' => $pagehash,
            'drafttext' => '  <p>Draft</p>  ',
            'pageinstance' => 'testinstance',
            'timemodified' => time(),
        ]);

        $service = new \local_dixeo\service\tiny_autosave_draft_service();
        $this->assertSame('<p>Draft</p>', $service->get_draft_text((int) $user->id, $ctx->id, $pagehash, 'id_modulecontent'));
    }

    public function test_get_draft_text_returns_null_when_stale(): void {
        global $DB;

        $this->setAdminUser();
        $user = $DB->get_record('user', ['username' => 'admin'], '*', MUST_EXIST);

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);
        $ctx = context_module::instance($cm->id);

        $pagehash = hash('sha1', 'stale' . $ctx->id);
        $DB->insert_record('tiny_autosave', [
            'elementid' => 'id_modulecontent',
            'contextid' => $ctx->id,
            'userid' => $user->id,
            'pagehash' => $pagehash,
            'drafttext' => '<p>Stale</p>',
            'pageinstance' => 'testinstance',
            'timemodified' => time() - (10 * YEARSECS),
        ]);

        $service = new \local_dixeo\service\tiny_autosave_draft_service();
        $this->assertNull($service->get_draft_text((int) $user->id, $ctx->id, $pagehash, 'id_modulecontent'));
    }
}
