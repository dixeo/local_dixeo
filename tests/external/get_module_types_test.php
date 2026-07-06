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
 * Tests for the get_module_types external function, focused on label resolution.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\external\get_module_types;
use local_dixeo\external\service_factory;
use local_dixeo\service\h5p_library_service;
use local_dixeo\service\module_types_service;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_dixeo\external\get_module_types
 */
final class get_module_types_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        h5p_library_service::reset_cache();
        $this->setAdminUser();
    }

    protected function tearDown(): void {
        service_factory::reset();
        parent::tearDown();
    }

    /**
     * Create a course and return its id for capability-checked external calls.
     */
    private function create_test_course(): int {
        $course = $this->getDataGenerator()->create_course();
        return (int) $course->id;
    }

    public function test_classic_type_label_is_replaced_by_moodle_modulename_string(): void {
        $this->mock_catalogue([
            ['type' => 'page', 'label' => 'Page (API)', 'description' => '', 'category' => 'content', 'component' => 'mod_page'],
        ]);

        $response = get_module_types::execute($this->create_test_course());
        $page = $this->find_type($response['types'], 'page');

        $this->assertSame(get_string('modulename', 'mod_page'), $page['label']);
    }

    public function test_h5p_variants_keep_distinct_api_labels_when_sharing_a_component(): void {
        $this->mock_catalogue([
            ['type' => 'h5p_quiz', 'label' => 'Quiz', 'description' => '', 'category' => 'interactive', 'component' => 'mod_h5pactivity'],
            ['type' => 'h5p_flashcards', 'label' => 'Flashcards', 'description' => '', 'category' => 'interactive', 'component' => 'mod_h5pactivity'],
            ['type' => 'h5p_crossword', 'label' => 'Crossword', 'description' => '', 'category' => 'interactive', 'component' => 'mod_h5pactivity'],
        ]);

        $response = get_module_types::execute($this->create_test_course());
        $labels = array_column($response['types'], 'label', 'type');

        $this->assertSame('Quiz', $labels['h5p_quiz']);
        $this->assertSame('Flashcards', $labels['h5p_flashcards']);
        $this->assertSame('Crossword', $labels['h5p_crossword']);
    }

    public function test_courseid_zero_uses_local_create_capability_when_block_installed(): void {
        $this->mock_catalogue([
            ['type' => 'page', 'label' => 'Page (API)', 'description' => '', 'category' => 'content', 'component' => 'mod_page'],
        ]);

        $response = get_module_types::execute(0);

        $this->assertTrue($response['success']);
        $this->find_type($response['types'], 'page');
    }

    public function test_courseid_zero_rejects_user_without_local_create_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        get_module_types::execute(0);
    }

    /**
     * Inject a partial-mocked module_types_service: the raw API call is stubbed
     * with the given catalogue rows, but {@see module_types_service::get_module_types_resolved()}
     * runs unmodified so the test covers the real label-resolution logic.
     */
    private function mock_catalogue(array $catalogue): void {
        $stub = $this->getMockBuilder(module_types_service::class)
            ->onlyMethods(['get_module_types_cached'])
            ->getMock();
        $stub->method('get_module_types_cached')->willReturn($catalogue);
        service_factory::set_test_module_types_service($stub);
    }

    private function find_type(array $types, string $typeid): array {
        foreach ($types as $row) {
            if (($row['type'] ?? null) === $typeid) {
                return $row;
            }
        }
        $this->fail("Type {$typeid} not found in response");
    }
}
