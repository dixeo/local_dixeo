<?php
/**
 * Event observers for the Dixeo plugin.
 *
 * Defines event handlers for file sync triggers based on course module
 * and block events.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Trigger sync when a course module is created.
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_dixeo\observer\file_sync_observer::course_module_created',
        'internal' => false,
        'priority' => 0,
    ],

    // Trigger sync when a course module is updated.
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_dixeo\observer\file_sync_observer::course_module_updated',
        'internal' => false,
        'priority' => 0,
    ],

    // Trigger sync when a course module is deleted.
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\local_dixeo\observer\file_sync_observer::course_module_deleted',
        'internal' => false,
        'priority' => 0,
    ],

    // Auto-enable sync when Dixeo blocks are added.
    [
        'eventname' => '\core\event\block_created',
        'callback' => '\local_dixeo\observer\file_sync_observer::block_created',
        'internal' => false,
        'priority' => 0,
    ],
];
