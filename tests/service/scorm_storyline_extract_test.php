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
 * Articulate Storyline HTML5 SCORM extract tests.
 *
 * @package    local_dixeo
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo;

/**
 * Unit tests for scorm storyline extract.
 *
 * @covers \local_dixeo\service\scorm_vector_extract_service
 */
final class scorm_storyline_extract_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_extracts_articulate_storyline_slide_json(): void {
        global $CFG;

        $inner = [
            'title' => 'Hello Slide',
            'slideNumberInScene' => 1,
            'id' => '5dTESTZZ11',
            'slideLayers' => [
                [
                    'objects' => [
                        [
                            'textLib' => [
                                [
                                    'vartext' => [
                                        'blocks' => [
                                            [
                                                'spans' => [
                                                    ['text' => 'Body paragraph one.'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $json = json_encode($inner, JSON_UNESCAPED_UNICODE);
        $escaped = str_replace(
            ['\\', "'"],
            ['\\\\', "\\'"],
            $json
        );
        $slidejs = "window.globalProvideData('slide', '" . $escaped . "');";

        $datajs = "window.globalProvideData('data', '{\"slideMap\":{}}');\n" .
            'var x="6sc00000000.5dTESTZZ11";';

        $zippath = $CFG->tempdir . '/dixeo_test_storyline_' . uniqid('', true) . '.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zippath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('html5/data/js/data.js', $datajs);
        $zip->addFromString('html5/data/js/5dTESTZZ11.js', $slidejs);
        $zip->close();

        $service = new \local_dixeo\service\scorm_vector_extract_service();
        $out = $service->extract_sco_text_from_zip_path($zippath);
        @unlink($zippath);

        $this->assertStringContainsString('Hello Slide', $out);
        $this->assertStringContainsString('Body paragraph one', $out);
    }
}
