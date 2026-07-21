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
 * Tests for HTTPS API URL enforcement and curl transport options.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

use local_dixeo\api\client;
use local_dixeo\api\exception\api_exception;
use local_dixeo\admin_setting_configapiurl;

/**
 * HTTPS transport and admin URL validation tests.
 *
 * @covers \local_dixeo\api\client
 * @covers \local_dixeo\admin_setting_configapiurl
 */
final class client_https_test extends \advanced_testcase {
    /**
     * Reset state between tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * is_https_url accepts only absolute HTTPS URLs.
     *
     * @dataProvider https_url_provider
     * @param string $url Candidate URL.
     * @param bool $expected Expected is_https_url result.
     */
    public function test_is_https_url(string $url, bool $expected): void {
        $this->assertSame($expected, client::is_https_url($url));
    }

    /**
     * Data provider for HTTPS URL validation cases.
     *
     * @return array[]
     */
    public static function https_url_provider(): array {
        return [
            'https default' => ['https://api.dixeo.com', true],
            'https with path' => ['https://api.dixeo.com/v1', true],
            'http rejected' => ['http://api.dixeo.com', false],
            'empty rejected' => ['', false],
            'relative rejected' => ['/api', false],
            'ftp rejected' => ['ftp://api.dixeo.com', false],
            'https no host' => ['https://', false],
        ];
    }

    /**
     * validate_configuration rejects an HTTP API base URL.
     */
    public function test_validate_configuration_rejects_http_baseurl(): void {
        $client = new class ('http://api.dixeo.com', 'test-key') extends client {
            /**
             * Expose protected validate_configuration for the test.
             */
            public function expose_validate(): void {
                $this->validate_configuration();
            }
        };

        $this->expectException(api_exception::class);
        $this->expectExceptionMessage(get_string('error:api_url_https_required', 'local_dixeo'));
        $client->expose_validate();
    }

    /**
     * validate_configuration accepts an HTTPS API base URL.
     */
    public function test_validate_configuration_accepts_https_baseurl(): void {
        $client = new class ('https://api.dixeo.com', 'test-key') extends client {
            /**
             * Expose protected validate_configuration for the test.
             */
            public function expose_validate(): void {
                $this->validate_configuration();
            }
        };

        $client->expose_validate();
        $this->assertTrue(true);
    }

    /**
     * Default curl options disable redirects and keep TLS verification.
     */
    public function test_curl_options_disable_redirects_and_verify_tls(): void {
        $client = new class ('https://api.dixeo.com', 'test-key') extends client {
            /**
             * Expose protected curl defaults for the test.
             *
             * @param int $timeout Request timeout in seconds.
             * @return array
             */
            public function expose_options(int $timeout): array {
                return $this->get_default_curl_options($timeout);
            }
        };

        $options = $client->expose_options(30);
        $this->assertFalse($options['CURLOPT_FOLLOWLOCATION']);
        $this->assertTrue($options['CURLOPT_SSL_VERIFYPEER']);
        $this->assertSame(2, $options['CURLOPT_SSL_VERIFYHOST']);
        $this->assertSame(30, $options['CURLOPT_TIMEOUT']);
    }

    /**
     * Admin setting validation rejects HTTP URLs.
     */
    public function test_admin_setting_rejects_http_url(): void {
        $setting = new admin_setting_configapiurl(
            'local_dixeo/api_url',
            'API URL',
            'desc',
            'https://api.dixeo.com',
            PARAM_URL
        );

        $result = $setting->validate('http://api.dixeo.com');
        $this->assertIsString($result);
        $this->assertStringContainsString('HTTPS', $result);
    }

    /**
     * Admin setting validation accepts HTTPS URLs.
     */
    public function test_admin_setting_accepts_https_url(): void {
        $setting = new admin_setting_configapiurl(
            'local_dixeo/api_url',
            'API URL',
            'desc',
            'https://api.dixeo.com',
            PARAM_URL
        );

        $this->assertTrue($setting->validate('https://api.dixeo.com'));
    }
}
