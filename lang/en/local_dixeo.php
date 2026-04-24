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
 * Language strings for the Dixeo plugin.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Dixeo AI';
$string['pluginname_desc'] = 'Dixeo AI integration for intelligent content generation and editing.';

// Capabilities.
$string['dixeo:manage'] = 'Manage Dixeo settings and view reports';
$string['dixeo:generate'] = 'Generate new modules using AI (page, label, quiz, glossary)';
$string['dixeo:edit'] = 'Edit existing modules using AI';
$string['dixeo:viewusage'] = 'View credit usage reports';

// Settings page.
$string['api_configuration'] = 'API Configuration';
$string['api_configuration_desc'] = 'Configure the connection to the Dixeo AI API.';
$string['api_url'] = 'API URL';
$string['api_url_desc'] = 'The base URL for the Dixeo API. Default: https://api.dixeo.com';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'Your Dixeo API key. Get one from the Dixeo dashboard.';
$string['namespace'] = 'Namespace';
$string['namespace_desc'] = 'Only needed when multiple Moodle sites share the same API key. Each site should use a different namespace (e.g., "production", "staging", "site1") to keep their data separate. Leave as "default" if this is the only site using this API key.';
$string['credit_information'] = 'Credit Information';
$string['current_balance'] = 'Current Balance';
$string['current_balance_desc'] = 'Your current Dixeo credit balance. Credits are used for AI operations.';
$string['credit_report'] = 'Credit Report';
$string['view_credit_report'] = 'View detailed credit report';
$string['configure_api'] = 'Configure API';

// Credit balance.
$string['state_active'] = 'Active';
$string['state_frozen'] = 'Frozen';
$string['state_suspended'] = 'Suspended';

// Credit report page.
$string['usage_statistics'] = 'Usage Statistics';
$string['this_week_usage'] = 'This Week';
$string['week_total'] = 'Total this week';
$string['recent_transactions'] = 'Transaction History';
$string['total_used'] = 'Total Used';
$string['average_per_period'] = 'Average per {$a}';
$string['data_points'] = 'Data Points';
$string['no_usage_data'] = 'No usage data available for the selected period.';
$string['no_transactions'] = 'No transactions found.';
$string['usage_chart_label'] = 'Credit Usage';

// Day names (short).
$string['day_mon'] = 'Mon';
$string['day_tue'] = 'Tue';
$string['day_wed'] = 'Wed';
$string['day_thu'] = 'Thu';
$string['day_fri'] = 'Fri';
$string['day_sat'] = 'Sat';
$string['day_sun'] = 'Sun';

// Day names (full).
$string['day_monday'] = 'Monday';
$string['day_tuesday'] = 'Tuesday';
$string['day_wednesday'] = 'Wednesday';
$string['day_thursday'] = 'Thursday';
$string['day_friday'] = 'Friday';
$string['day_saturday'] = 'Saturday';
$string['day_sunday'] = 'Sunday';

// Periods.
$string['period'] = 'Period';
$string['period_day'] = 'Daily';
$string['period_week'] = 'Weekly';
$string['period_month'] = 'Monthly';

// Transaction types.
$string['transaction_type_purchase'] = 'Purchase';
$string['transaction_type_deduction'] = 'Usage';
$string['transaction_type_refund'] = 'Refund';
$string['transaction_type_reset'] = 'Renewal';

// Table headers.
$string['date'] = 'Date';
$string['type'] = 'Type';
$string['description'] = 'Description';
$string['amount'] = 'Amount';

// Pagination.
$string['pagination'] = 'Page navigation';
$string['page_x_of_y'] = 'Page {$a->current} of {$a->total}';

// Warnings and errors.
$string['api_key_not_configured'] = 'The Dixeo API key is not configured. Please configure it in the plugin settings.';
$string['api_error'] = 'API Error: {$a}';
$string['account_frozen_warning'] = 'Your account is frozen due to low credit balance. Please add credits to continue using Dixeo AI features.';
$string['account_suspended_warning'] = 'Your account has been suspended. Please contact Dixeo support for assistance.';

// Errors (used in exceptions).
$string['error:authentication'] = 'Authentication failed. Please check your API key.';
$string['error:payment_required'] = 'Insufficient credits. Please add credits to continue.';
$string['error:rate_limit'] = 'Rate limit exceeded. Please wait before making more requests.';
$string['error:validation'] = 'Invalid request: {$a}';
$string['error:job_not_found'] = 'The requested job was not found.';
$string['error:upstream_ai'] = 'AI service error. Please try again later.';
$string['error:job_failed'] = 'Job processing failed: {$a}';
$string['error:connection'] = 'Failed to connect to the Dixeo API. Please check your network connection.';
$string['error:timeout'] = 'The operation timed out. You can check the job status later.';

// Overview page.
$string['overview'] = 'Dixeo Overview';
$string['credit_balance'] = 'Credit Balance';
$string['credits'] = 'credits';

// Privacy.
$string['privacy:metadata'] = 'The Dixeo plugin sends course content to the Dixeo AI API for processing but does not store personal data locally.';

// DSL errors.
$string['dsl_error'] = 'Module creation failed: {$a}';

// Quiz question feedback.
$string['feedback_correct'] = 'Correct!';

// Tasks.
$string['task_cleanup_jobs'] = 'Clean up old job records';
$string['task_process_file_sync'] = 'Process Dixeo file synchronization';
$string['task_poll_image_generation'] = 'Poll Dixeo image generation job';

// Async course / chapter images.
$string['dixeo_course_image_unsupported_type'] = 'Unsupported generated image type.';
$string['dixeo_image_job_empty_result'] = 'The image job returned no image data.';
$string['dixeo_pluginfile_not_found'] = 'Could not read the image file from storage.';

// File sync.
$string['filesync_title'] = 'Dixeo File Sync';
$string['filesync_label'] = 'Sync';
$string['filesync_status_none'] = 'No files synced';
$string['filesync_status_syncing'] = 'Syncing files...';
$string['filesync_status_synchronized'] = 'Files synchronized';
$string['filesync_status_error'] = 'Sync error';
$string['filesync_status_outdated'] = 'Content changed, sync needed';
$string['filesync_status_paused'] = 'Sync paused';
$string['filesync_status_disabled'] = 'Sync disabled';
$string['filesync_enable'] = 'Enable sync';
$string['filesync_pause'] = 'Pause sync';
$string['filesync_disable_remove'] = 'Turn off & clear sync data';
$string['filesync_resync'] = 'Sync now';
$string['filesync_files_count'] = '{$a} files synced';
$string['filesync_progress'] = '{$a}% complete';
$string['last_sync'] = 'Last sync';
$string['filesync_error_retry'] = 'Will retry automatically';
$string['files'] = 'files';
