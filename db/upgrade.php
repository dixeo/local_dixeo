<?php
/**
 * Database upgrade steps for the Dixeo plugin.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_dixeo plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool True on success.
 */
function xmldb_local_dixeo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to add the course_ai table for file sync tracking.
    if ($oldversion < 2025122201) {
        $table = new xmldb_table('local_dixeo_course_ai');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sync_status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'none');
        $table->add_field('files_total', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('files_completed', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('progress_percent', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('last_error_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('last_sync_started', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('last_sync_completed', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enabled_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enabled_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('disabled_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('disabled_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);

        $table->add_index('idx_sync_status', XMLDB_INDEX_NOTUNIQUE, ['sync_status']);
        $table->add_index('idx_enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025122201, 'local', 'dixeo');
    }

    return true;
}
