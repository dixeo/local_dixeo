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
 * Tests for admin credit balance setting error output (DIXEO-SEC-008).
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\api\exception\api_exception;

/**
 * Escaping of remote API errors in the admin balance setting.
 *
 * @covers \local_dixeo\admin_setting_credit_balance
 */
final class admin_setting_credit_balance_test extends \advanced_testcase {
    /**
     * Load admin library required by admin_setting base class.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Build a test double that exposes protected formatting.
     *
     * @return admin_setting_credit_balance
     */
    private function setting_for_test(): admin_setting_credit_balance {
        return new class extends admin_setting_credit_balance {
            /**
             * Expose protected error formatting for unit tests.
             *
             * @param \Throwable $e Failure to format.
             * @return string
             */
            public function expose_format_balance_error_html(\Throwable $e): string {
                return $this->format_balance_error_html($e);
            }

            /**
             * Minimal constructor for tests.
             */
            public function __construct() {
                parent::__construct('local_dixeo/test_balance', 'Test', 'Test');
            }
        };
    }

    /**
     * RFC 7807 detail with HTML must be escaped and not executed.
     */
    public function test_format_balance_error_html_escapes_api_problem_detail(): void {
        $this->resetAfterTest();

        $payload = '<img src=x onerror="alert(1)">';
        $exception = api_exception::from_response([
            'type' => 'upstream_error',
            'detail' => $payload,
            'title' => 'Upstream failure',
        ], 502);

        $html = $this->setting_for_test()->expose_format_balance_error_html($exception);

        $this->assertStringContainsString(s($exception->getMessage()), $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src', $html);
        $this->resetDebugging();
    }

    /**
     * moodle_exception debuginfo must not replace the user-visible admin message.
     */
    public function test_format_balance_error_html_ignores_debuginfo(): void {
        $this->resetAfterTest();

        $exception = new \moodle_exception(
            'api_error',
            'local_dixeo',
            '',
            'Safe user-facing text',
            '<script>alert("xss")</script>'
        );

        $html = $this->setting_for_test()->expose_format_balance_error_html($exception);

        $this->assertStringContainsString(s($exception->getMessage()), $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->resetDebugging();
    }
}
