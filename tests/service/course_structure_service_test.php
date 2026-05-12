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
 * Tests for the course structure service module-type filtering.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\api\exception\api_exception;
use local_dixeo\service\course_structure_service;
use local_dixeo\service\h5p_library_service;
use local_dixeo\service\job_service;
use local_dixeo\service\module_types_service;
use ReflectionMethod;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_dixeo\service\course_structure_service
 */
final class course_structure_service_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        h5p_library_service::reset_cache();
    }

    public function test_available_types_keep_only_types_whose_plugin_is_installed(): void {
        $catalogue = [
            ['type' => 'page', 'component' => 'mod_page'],
            ['type' => 'inexistent_xyz', 'component' => 'mod_inexistent_xyz'],
        ];

        $available = $this->invoke_get_installed_module_types($this->build_service($catalogue));

        $this->assertContains('page', $available);
        $this->assertNotContains('inexistent_xyz', $available);
    }

    public function test_available_types_exclude_h5p_variants_when_library_missing(): void {
        $catalogue = [
            ['type' => 'page', 'component' => 'mod_page'],
            [
                'type' => 'h5p_quiz',
                'component' => 'mod_h5pactivity',
                'requirements' => ['H5P.QuestionSet 1.20'],
            ],
        ];

        $available = $this->invoke_get_installed_module_types($this->build_service($catalogue));

        $this->assertContains('page', $available);
        $this->assertNotContains('h5p_quiz', $available);
    }

    public function test_available_types_include_h5p_variant_when_library_present(): void {
        global $DB;

        $DB->insert_record('h5p_libraries', (object) [
            'machinename' => 'H5P.QuestionSet',
            'majorversion' => 1,
            'minorversion' => 20,
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
            'title' => 'H5P QuestionSet',
        ]);

        $catalogue = [
            [
                'type' => 'h5p_quiz',
                'component' => 'mod_h5pactivity',
                'requirements' => ['H5P.QuestionSet 1.20'],
            ],
        ];

        $available = $this->invoke_get_installed_module_types($this->build_service($catalogue));

        $this->assertContains('h5p_quiz', $available);
    }

    public function test_available_types_fall_back_to_legacy_plugin_names_on_api_failure(): void {
        $service = $this->build_service_throwing(new api_exception('not_configured', 'API not configured'));

        $available = $this->invoke_get_installed_module_types($service);

        $this->assertContains('page', $available);
        $this->assertContains('quiz', $available);
    }

    /**
     * Build a service with a stub module_types_service returning the given catalogue.
     *
     * @param array $catalogue Type rows to return.
     */
    private function build_service(array $catalogue): course_structure_service {
        $stub = $this->createMock(module_types_service::class);
        $stub->method('get_module_types_cached')->willReturn($catalogue);

        return new course_structure_service(
            jobservice: $this->createMock(job_service::class),
            moduletypesservice: $stub,
            namespace: 'test'
        );
    }

    /**
     * Build a service whose module_types_service throws the given exception.
     */
    private function build_service_throwing(\Throwable $exception): course_structure_service {
        $stub = $this->createMock(module_types_service::class);
        $stub->method('get_module_types_cached')->willThrowException($exception);

        return new course_structure_service(
            jobservice: $this->createMock(job_service::class),
            moduletypesservice: $stub,
            namespace: 'test'
        );
    }

    /**
     * @return string[]
     */
    private function invoke_get_installed_module_types(course_structure_service $service): array {
        $method = new ReflectionMethod($service, 'get_installed_module_types');
        $method->setAccessible(true);
        $result = $method->invoke($service);

        return is_array($result) ? $result : [];
    }
}
