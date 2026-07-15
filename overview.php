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
 * Dixeo overview page - displays credit balance.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_dixeo\service\credit_service;

require_login();
$context = context_system::instance();
require_capability('local/dixeo:viewusage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dixeo/overview.php'));
$PAGE->set_title(get_string('overview', 'local_dixeo'));
$PAGE->set_heading(get_string('overview', 'local_dixeo'));
$PAGE->set_pagelayout('admin');

$templatedata = ['error' => false];

$apikey = get_config('local_dixeo', 'api_key');
if (empty($apikey)) {
    $templatedata['error'] = true;
    $templatedata['errormessage'] = get_string('api_key_not_configured', 'local_dixeo');
} else {
    try {
        $service = new credit_service();
        $balance = $service->get_balance();

        $templatedata['credits'] = $balance->credits;
        $templatedata['statelabel'] = $balance->get_state_description();
        $templatedata['stateclass'] = match ($balance->state) {
            'active' => 'success',
            'frozen' => 'warning',
            'suspended' => 'danger',
            default => 'secondary',
        };
        $templatedata['isfrozen'] = $balance->is_frozen();
        $templatedata['issuspended'] = $balance->is_suspended();
    } catch (Exception $e) {
        $templatedata['error'] = true;
        $templatedata['errormessage'] = get_string('api_error', 'local_dixeo', $e->getMessage());
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dixeo/overview', $templatedata);
echo $OUTPUT->footer();
