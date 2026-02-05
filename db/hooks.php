<?php
/**
 * Hook callbacks for the Dixeo plugin.
 *
 * Defines hooks for injecting the sync indicator into course pages.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => [\local_dixeo\hook\output\sync_indicator_injector::class, 'callback'],
        'priority' => 500,
    ],
];
