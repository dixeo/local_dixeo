<?php
// This file is part of Moodle - http://moodle.org/
//
// @package    local_dixeo
// @category   test
// @copyright  2026 Edunao SAS (contact@edunao.com)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_dixeo\dsl;

use local_dixeo\dsl\actions\create_h5p_module_action;
use local_dixeo\service\h5p_packaging_service;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_dixeo\dsl\actions\create_h5p_module_action
 */
final class create_h5p_module_action_test extends \advanced_testcase {

    public function test_execute_allows_missing_intro_in_ai_data(): void {
        $this->resetAfterTest(false);

        $packaging = $this->createMock(h5p_packaging_service::class);
        $packaging->expects($this->once())
            ->method('create_activity')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                'H5P Quiz',
                '',
                'H5P.QuestionSet 1.20',
                $this->isType('array'),
                $this->anything(),
                null
            )
            ->willReturn(['id' => 1, 'cmid' => 42]);

        $action = new create_h5p_module_action($packaging);
        $resolver = new value_resolver([
            'name' => 'H5P Quiz',
            'content' => ['library' => 'H5P.QuestionSet 1.20', 'params' => []],
        ], [], [
            'courseid' => 1,
            'sectionid' => 2,
            'sectionnum' => 1,
        ]);

        $result = $action->execute([
            'main_library' => 'H5P.QuestionSet 1.20',
            'fields' => [
                'name' => ['source' => '$.name'],
                'intro' => ['source' => '$.intro'],
                'content' => ['source' => '$.content'],
            ],
        ], $resolver);

        $this->assertSame(42, $result['cmid']);
    }
}
