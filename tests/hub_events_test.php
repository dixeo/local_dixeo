<?php
// This file is part of Moodle - https://moodle.org/
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
 * Tests for local_dixeo Moodle events on sync and job cancel.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\api\client;
use local_dixeo\event\file_sync_disabled;
use local_dixeo\event\file_sync_enabled;
use local_dixeo\event\file_sync_triggered;
use local_dixeo\event\job_cancelled;
use local_dixeo\repository\course_ai_repository;
use local_dixeo\repository\job_repository;
use local_dixeo\service\file_sync_service;
use local_dixeo\service\job_service;

/**
 * Hub sensitive-operation event coverage.
 *
 * @covers \local_dixeo\event\file_sync_enabled
 * @covers \local_dixeo\event\file_sync_disabled
 * @covers \local_dixeo\event\file_sync_triggered
 * @covers \local_dixeo\event\job_cancelled
 * @covers \local_dixeo\service\file_sync_service
 * @covers \local_dixeo\service\job_service
 */
final class hub_events_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Enable then disable sync must emit the corresponding events once each.
     */
    public function test_enable_and_disable_emit_events(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $userid = (int) $USER->id;

        // Ensure a known disabled baseline (some generators may leave site defaults).
        $DB->delete_records('local_dixeo_course_ai', ['courseid' => $course->id]);

        $mockclient = $this->createMock(client::class);
        $service = new file_sync_service(new course_ai_repository(), $mockclient);

        $this->assertFalse($service->is_enabled((int) $course->id));

        $sink = $this->redirectEvents();
        $service->enable_sync((int) $course->id, $userid);
        $enabled = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof file_sync_enabled
        ));
        $this->assertCount(1, $enabled);
        $this->assertEquals((int) $course->id, (int) $enabled[0]->courseid);

        $sink->clear();
        $service->enable_sync((int) $course->id, $userid);
        $enabledagain = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof file_sync_enabled
        ));
        $this->assertCount(0, $enabledagain, 'Re-enabling an already enabled course must not re-fire');

        $sink->clear();
        $service->disable_sync((int) $course->id, $userid, true);
        $disabled = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof file_sync_disabled
        ));
        $this->assertCount(1, $disabled);
        $this->assertSame(1, (int) $disabled[0]->other['removefiles']);
    }

    /**
     * A real sync run (empty course clears remote) must emit file_sync_triggered.
     */
    public function test_trigger_sync_emits_event_when_work_starts(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $userid = (int) $USER->id;
        $DB->delete_records('local_dixeo_course_ai', ['courseid' => $course->id]);

        $mockclient = $this->createMock(client::class);
        $mockclient->expects($this->once())
            ->method('delete_files')
            ->with((string) $course->id)
            ->willReturn([]);

        $service = new file_sync_service(new course_ai_repository(), $mockclient);
        $service->enable_sync((int) $course->id, $userid);

        $sink = $this->redirectEvents();
        $service->trigger_sync((int) $course->id);
        $triggered = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof file_sync_triggered
        ));
        $this->assertCount(1, $triggered);
        $this->assertEquals((int) $course->id, (int) $triggered[0]->courseid);
    }

    /**
     * Successful job cancel must emit job_cancelled with jobid only.
     */
    public function test_cancel_job_emits_event(): void {
        global $USER;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $userid = (int) $USER->id;

        $repo = new job_repository();
        $repo->register('job-event-cancel', (int) $course->id, 9, 'default', 'module_generate');

        $client = $this->createMock(client::class);
        $client->expects($this->once())
            ->method('post')
            ->with('/v1/jobs/job-event-cancel/cancel', [])
            ->willReturn(['status' => 'cancelled']);

        $service = new job_service($client, null, $repo);
        $sink = $this->redirectEvents();
        $service->cancel_job('job-event-cancel', (int) $course->id);

        $cancelled = array_values(array_filter(
            $sink->get_events(),
            static fn($event) => $event instanceof job_cancelled
        ));
        $this->assertCount(1, $cancelled);
        $this->assertSame('job-event-cancel', $cancelled[0]->other['jobid']);
        $this->assertEquals((int) $course->id, (int) $cancelled[0]->courseid);
        $this->assertEquals($userid, (int) $cancelled[0]->userid);
        $this->assertArrayNotHasKey('instructions', $cancelled[0]->other);
        $this->assertArrayNotHasKey('message', $cancelled[0]->other);
    }
}
