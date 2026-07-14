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
 * CLI script to export course context markdown for debugging.
 *
 * Usage: php export_course_context.php --courseid=2
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_dixeo\context\context_builder_factory;
use local_dixeo\context\course_context_builder;

// CLI options.
list($options, $unrecognized) = cli_get_params(
    ['courseid' => 2, 'section' => null, 'mode' => 'teaching', 'help' => false],
    ['c' => 'courseid', 's' => 'section', 'm' => 'mode', 'h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOF
Export course context markdown to a file.

Options:
-c, --courseid=INT   Course ID to export (default: 2)
-s, --section=INT    Target section number for tiered detail (optional)
-m, --mode=STRING    Context mode: teaching or assessment (default: teaching)
-h, --help           Print this help

Examples:
  php export_course_context.php --courseid=2
  php export_course_context.php --courseid=2 --section=1
  php export_course_context.php --courseid=2 --mode=assessment

Modes:
  teaching   - Tiered by section proximity (for page, label, book)
  assessment - Full content everywhere (for quiz, glossary)

EOF;
    echo $help;
    exit(0);
}

$courseid = (int) $options['courseid'];
$targetsection = $options['section'] !== null ? (int) $options['section'] : null;
$mode = $options['mode'] === 'assessment'
    ? course_context_builder::MODE_ASSESSMENT
    : course_context_builder::MODE_TEACHING;

// Verify course exists.
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    cli_error("Course with ID {$courseid} not found.");
}

$sectioninfo = $targetsection !== null ? " (section: {$targetsection})" : "";
$modeinfo = $options['mode'] === 'assessment' ? " [ASSESSMENT MODE]" : " [TEACHING MODE]";
cli_writeln("Exporting context for course: {$course->fullname} (ID: {$courseid}){$sectioninfo}{$modeinfo}");

// Build context using the factory.
$context = context_builder_factory::buildcoursecontext($courseid, $targetsection, $mode);

// Save to output file.
$outputdir = __DIR__ . '/../output';
$sectionpart = $targetsection !== null ? "_section{$targetsection}" : "";
$modepart = $options['mode'] === 'assessment' ? "_assessment" : "";
$filename = "course_{$courseid}{$sectionpart}{$modepart}_context_" . date('Y-m-d_H-i-s') . '.md';
$filepath = $outputdir . '/' . $filename;

if (!file_put_contents($filepath, $context)) {
    cli_error("Failed to write output file: {$filepath}");
}

cli_writeln("Context exported successfully to:");
cli_writeln($filepath);
cli_writeln("");
cli_writeln("Preview (first 500 chars):");
cli_writeln("---");
cli_writeln(substr($context, 0, 500));
cli_writeln("---");
