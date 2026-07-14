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
 * Tests for H5P library installation lookup.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\service\h5p_library_service;

/**
 * Unit tests for h5p library service.
 *
 * @covers \local_dixeo\service\h5p_library_service
 */
final class h5p_library_service_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        h5p_library_service::reset_cache();
    }

    public function test_is_installed_false_when_library_missing(): void {
        $this->assertFalse(h5p_library_service::is_installed('H5P.Flashcards 1.7'));
    }

    public function test_is_installed_true_when_library_seeded(): void {
        $this->seed_library('H5P.Flashcards', 1, 7);

        $this->assertTrue(h5p_library_service::is_installed('H5P.Flashcards 1.7'));
    }

    public function test_is_installed_false_for_malformed_identifier(): void {
        $this->seed_library('H5P.Flashcards', 1, 7);

        $this->assertFalse(h5p_library_service::is_installed('not a valid identifier'));
        $this->assertFalse(h5p_library_service::is_installed(''));
        $this->assertFalse(h5p_library_service::is_installed('H5P.Flashcards'));
    }

    public function test_is_installed_accepts_newer_minor_within_same_major(): void {
        // Site has a newer minor than what the API pinned — should still count as installed
        // because H5P guarantees backward compatibility within a major version.
        $this->seed_library('H5P.Flashcards', 1, 9);

        $this->assertTrue(h5p_library_service::is_installed('H5P.Flashcards 1.7'));
        $this->assertTrue(h5p_library_service::is_installed('H5P.Flashcards 1.9'));
        $this->assertFalse(h5p_library_service::is_installed('H5P.Flashcards 1.10'));
        $this->assertFalse(h5p_library_service::is_installed('H5P.Flashcards 2.0'));
    }

    public function test_resolve_returns_actual_installed_version(): void {
        // Multiple compatible versions installed — the highest-minor wins so the packaged
        // .h5p references a version Moodle actually has.
        $this->seed_library('H5P.Crossword', 0, 5);
        $this->seed_library('H5P.Crossword', 0, 7);

        $resolved = h5p_library_service::resolve_installed_version('H5P.Crossword 0.5');

        $this->assertNotNull($resolved);
        $this->assertSame('H5P.Crossword', $resolved['machinename']);
        $this->assertSame(0, $resolved['major']);
        $this->assertSame(7, $resolved['minor']);
    }

    public function test_resolve_returns_null_when_only_older_minor_installed(): void {
        $this->seed_library('H5P.Crossword', 0, 4);

        $this->assertNull(h5p_library_service::resolve_installed_version('H5P.Crossword 0.5'));
    }

    public function test_resolve_does_not_cross_major_boundary(): void {
        $this->seed_library('H5P.Flashcards', 2, 0);

        $this->assertNull(h5p_library_service::resolve_installed_version('H5P.Flashcards 1.7'));
    }

    /**
     * Insert a fake installed H5P library row for resolution tests.
     *
     * @param string $machinename
     * @param int $major
     * @param int $minor
     */
    private function seed_library(string $machinename, int $major, int $minor): void {
        global $DB;

        $DB->insert_record('h5p_libraries', (object) [
            'machinename' => $machinename,
            'majorversion' => $major,
            'minorversion' => $minor,
            'patchversion' => 0,
            'runnable' => 1,
            'enabled' => 1,
            'fullscreen' => 0,
            'embedtypes' => '',
            'preloadedjs' => '',
            'preloadedcss' => '',
            'droplibrarycss' => '',
            'semantics' => '[]',
            'addto' => '',
            'coremajor' => null,
            'coreminor' => null,
            'metadatasettings' => '',
            'tutorial' => '',
            'example' => '',
            'title' => $machinename,
        ]);
    }
}
