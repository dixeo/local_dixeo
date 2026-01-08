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
 * Credit report page for Dixeo plugin.
 *
 * Displays credit balance, usage statistics, and transaction history.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_dixeo\output\credit_report_page;

// Require login and admin access.
require_login();
require_capability('local/dixeo:manage', context_system::instance());

// Get parameters.
$limit = optional_param('limit', 50, PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/dixeo/credit_report.php', [
    'limit' => $limit,
    'offset' => $offset,
]));
$PAGE->set_title(get_string('credit_report', 'local_dixeo'));
$PAGE->set_heading(get_string('credit_report', 'local_dixeo'));
$PAGE->set_pagelayout('admin');

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_dixeo'), new moodle_url('/admin/settings.php', ['section' => 'local_dixeo']));
$PAGE->navbar->add(get_string('credit_report', 'local_dixeo'));

// Create renderable.
$report = new credit_report_page($limit, $offset);

// Output.
$output = $PAGE->get_renderer('local_dixeo');
echo $output->header();
echo $output->render($report);
echo $output->footer();
