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
 * CLI script to bulk add visible "File" resources to a Moodle course.
 *
 * Creates text files with unique content for testing file imports and vector stores.
 *
 * Usage: php add_test_resources.php --courseid=2
 *        php add_test_resources.php --courseid=2 --number=50 --wipe
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/resourcelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params(
    [
        'courseid' => null,
        'number' => 10,
        'wipe' => false,
        'section' => 0,
        'help' => false,
    ],
    ['c' => 'courseid', 'n' => 'number', 'w' => 'wipe', 's' => 'section', 'h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['courseid']) || !is_numeric($options['courseid'])) {
    $help = <<<EOF
Bulk add visible "File" resources to a Moodle course.

Creates text files with unique identifiable content for testing.

Options:
-c, --courseid=INT   Target course ID (required)
-n, --number=INT     Number of resources to create (default: 10)
-s, --section=INT    Section number to add resources to (default: 0)
-w, --wipe           Delete all existing mod_resource first
-h, --help           Print this help

Examples:
  php add_test_resources.php --courseid=2
  php add_test_resources.php --courseid=2 --number=50
  php add_test_resources.php --courseid=2 --number=100 --wipe
  php add_test_resources.php --courseid=2 --section=1 --number=5

EOF;
    echo $help;
    exit(0);
}

// Validate course.
$courseid = (int) $options['courseid'];
$instances = max(1, (int) $options['number']);
$sectionnumber = (int) $options['section'];

$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    cli_error("Course {$courseid} not found.");
}

// Run as admin to avoid capability checks.
\core\session\manager::set_user(get_admin());

// Optional wipe.
if (!empty($options['wipe'])) {
    cli_writeln("Removing existing File resources...");

    $fs = get_file_storage();
    $wiped = 0;

    foreach (get_course_mods($courseid) as $cm) {
        if ($cm->modname !== 'resource') {
            continue;
        }
        $resource = $DB->get_record('resource', ['id' => $cm->instance], 'id, name');
        if ($resource && preg_match('/^Test File \d+$/i', $resource->name)) {
            $ctxid = context_module::instance($cm->id)->id;
            $fs->delete_area_files($ctxid);
            course_delete_module($cm->id, true);
            $wiped++;
        }
    }

    rebuild_course_cache($courseid, true);
    cli_writeln("{$wiped} test file resources removed.");
}

// Determine starting index by checking existing "Test File N" resources in the course.
// Skip detection when --wipe is used since all resources were removed.
$startindex = 1;
if (empty($options['wipe'])) {
    $sql = "SELECT r.id, r.name
              FROM {resource} r
              JOIN {course_modules} cm ON cm.instance = r.id
                   AND cm.module = (SELECT id FROM {modules} WHERE name = 'resource')
             WHERE r.course = :courseid
               AND cm.deletioninprogress = 0";
    $existingresources = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    foreach ($existingresources as $res) {
        if (preg_match('/^Test File (\d+)$/i', $res->name, $matches)) {
            $num = (int) $matches[1];
            if ($num >= $startindex) {
                $startindex = $num + 1;
            }
        }
    }

    if ($startindex > 1) {
        cli_writeln("Found existing test files up to number " . ($startindex - 1) . ", starting at {$startindex}.");
    }
}

// Main loop.
cli_heading("Creating {$instances} File activities in course \"{$course->fullname}\" (ID {$courseid})");
$fs = get_file_storage();
global $USER;

for ($i = 0; $i < $instances; $i++) {
    $num = $startindex + $i;

    // Unique content.
    $filename = "test_file_{$num}.txt";
    $uniquekey = 'KEY-' . strtoupper(bin2hex(random_bytes(6)));
    $content = "Automated test file {$num}\nUnique key: {$uniquekey}\n"
        . "Course: {$course->fullname}\nCreated: " . date('Y-m-d H:i:s') . "\n";

    // Draft file-manager.
    $draftitemid = file_get_unused_draft_itemid();
    $introitemid = file_get_unused_draft_itemid();

    $fs->create_file_from_string([
        'contextid' => context_user::instance($USER->id)->id,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $draftitemid,
        'filepath' => '/',
        'filename' => $filename,
    ], $content);

    // Module info object.
    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'resource';
    $moduleinfo->course = $courseid;
    $moduleinfo->section = $sectionnumber;
    $moduleinfo->visible = 1;
    $moduleinfo->groupmode = 0;
    $moduleinfo->name = "Test File {$num}";
    $moduleinfo->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => $introitemid];
    $moduleinfo->display = RESOURCELIB_DISPLAY_OPEN;
    $moduleinfo->files = ['itemid' => $draftitemid];

    // Create module.
    $created = create_module($moduleinfo);
    $instid = $created->instance;

    // Bump revision.
    $DB->set_field('resource', 'revision', time(), ['id' => $instid]);

    cli_writeln("  [" . ($i + 1) . "/{$instances}] {$filename} ({$uniquekey})");
}

// Rebuild cache once.
rebuild_course_cache($courseid, true);
cli_writeln("");
cli_writeln("Done - {$instances} resources created in section {$sectionnumber}.");
exit(0);
