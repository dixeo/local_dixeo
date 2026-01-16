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

namespace local_dixeo\output;

use renderable;
use templatable;
use renderer_base;
use local_dixeo\service\credit_service;
use local_dixeo\dto\credit_balance;

/**
 * Renderable for the credit report page.
 *
 * Prepares data for the credit report template, including balance,
 * usage statistics (current week view), and transaction history.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class credit_report_page implements renderable, templatable {

    /** @var credit_service The credit service. */
    protected credit_service $creditservice;

    /** @var int Pagination limit. */
    protected int $limit;

    /** @var int Pagination offset. */
    protected int $offset;

    /** @var credit_balance|null Cached balance. */
    protected ?credit_balance $balance = null;

    /** @var string|null Error message if API call fails. */
    protected ?string $error = null;

    /**
     * Constructor.
     *
     * @param int $limit Pagination limit for transactions.
     * @param int $offset Pagination offset for transactions.
     */
    public function __construct(int $limit = 50, int $offset = 0) {
        $this->creditservice = new credit_service();
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output The renderer.
     * @return array The template data.
     */
    public function export_for_template(renderer_base $output): array {
        if (!$this->creditservice->is_configured()) {
            return [
                'configured' => false,
                'error' => get_string('api_key_not_configured', 'local_dixeo'),
                'settings_url' => (new \moodle_url('/admin/settings.php', ['section' => 'local_dixeo']))->out(false),
            ];
        }

        try {
            return $this->build_template_data();
        } catch (\Exception $e) {
            return [
                'configured' => true,
                'error' => get_string('api_error', 'local_dixeo', $e->getMessage()),
            ];
        }
    }

    /**
     * Build the template data from API responses.
     *
     * @return array The template data.
     */
    protected function build_template_data(): array {
        // Get balance.
        $balance = $this->creditservice->get_balance();

        // Get current week date range (Monday to Sunday).
        $weekdates = $this->get_current_week_dates();

        // Get usage stats for the current week.
        $usagestats = $this->creditservice->get_usage_stats(
            'day',
            $weekdates['start'],
            $weekdates['end']
        );

        // Build weekly chart data.
        $chartdata = $this->build_weekly_chart_data($usagestats['stats'], $weekdates);

        // Calculate week totals.
        $weektotal = array_sum($chartdata['values']);

        // Get transactions.
        $transactionsresult = $this->creditservice->get_transactions(null, $this->limit, $this->offset);

        // Format transactions for display with improved descriptions.
        $transactions = array_map(function ($tx) {
            return $this->format_transaction($tx);
        }, $transactionsresult['transactions']);

        // Build pagination data.
        $pagination = $this->build_pagination($transactionsresult['pagination']);

        return [
            'configured' => true,
            'error' => null,

            // Balance section.
            'balance' => [
                'credits' => $balance->credits,
                'formatted' => $balance->get_formatted_balance(),
                'state' => $balance->state,
                'state_description' => $balance->get_state_description(),
                'state_class' => $this->get_state_class($balance->state),
                'is_active' => $balance->is_active(),
                'is_frozen' => $balance->is_frozen(),
                'is_suspended' => $balance->is_suspended(),
            ],

            // Usage section - weekly view.
            'usage' => [
                'week_total' => $weektotal,
                'week_total_formatted' => credit_service::format_credits($weektotal),
                'week_range' => $this->format_week_range($weekdates),
            ],

            // Chart data for JavaScript.
            'chart_data' => json_encode($chartdata),
            'has_chart_data' => true, // Always show the week chart.

            // Transactions table.
            'transactions' => $transactions,
            'has_transactions' => !empty($transactions),

            // Pagination.
            'pagination' => $pagination,
            'has_pagination' => $pagination['total_pages'] > 1,

            // URLs.
            'report_url' => (new \moodle_url('/local/dixeo/credit_report.php'))->out(false),
        ];
    }

    /**
     * Get the current week's date range (Monday to Sunday).
     *
     * @return array Array with 'start' and 'end' dates in Y-m-d format, plus 'dates' array.
     */
    protected function get_current_week_dates(): array {
        $now = new \DateTime();
        $dayofweek = (int) $now->format('N'); // 1 = Monday, 7 = Sunday.

        // Calculate Monday of this week.
        $monday = clone $now;
        $monday->modify('-' . ($dayofweek - 1) . ' days');

        // Calculate Sunday of this week.
        $sunday = clone $monday;
        $sunday->modify('+6 days');

        // Generate all dates in the week.
        $dates = [];
        $current = clone $monday;
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        return [
            'start' => $monday->format('Y-m-d'),
            'end' => $sunday->format('Y-m-d'),
            'dates' => $dates,
            'today' => $now->format('Y-m-d'),
        ];
    }

    /**
     * Build chart data for the weekly view.
     *
     * @param array $stats The usage statistics from API.
     * @param array $weekdates The week date information.
     * @return array The chart data with labels, values, and highlighting info.
     */
    protected function build_weekly_chart_data(array $stats, array $weekdates): array {
        // Map API stats by date for quick lookup.
        $statsByDate = [];
        foreach ($stats as $stat) {
            $date = $stat['date'] ?? '';
            $statsByDate[$date] = $stat['total_credits'] ?? 0;
        }

        // Short day names for chart labels.
        $daynames = [
            get_string('day_mon', 'local_dixeo'),
            get_string('day_tue', 'local_dixeo'),
            get_string('day_wed', 'local_dixeo'),
            get_string('day_thu', 'local_dixeo'),
            get_string('day_fri', 'local_dixeo'),
            get_string('day_sat', 'local_dixeo'),
            get_string('day_sun', 'local_dixeo'),
        ];

        // Full day names for tooltips.
        $fulldaynames = [
            get_string('day_monday', 'local_dixeo'),
            get_string('day_tuesday', 'local_dixeo'),
            get_string('day_wednesday', 'local_dixeo'),
            get_string('day_thursday', 'local_dixeo'),
            get_string('day_friday', 'local_dixeo'),
            get_string('day_saturday', 'local_dixeo'),
            get_string('day_sunday', 'local_dixeo'),
        ];

        $labels = [];
        $fulllabels = [];
        $values = [];
        $istoday = [];

        foreach ($weekdates['dates'] as $index => $date) {
            $labels[] = $daynames[$index];
            $fulllabels[] = $fulldaynames[$index];
            $values[] = $statsByDate[$date] ?? 0;
            $istoday[] = ($date === $weekdates['today']);
        }

        return [
            'labels' => $labels,
            'full_labels' => $fulllabels,
            'values' => $values,
            'is_today' => $istoday,
            'today_index' => array_search(true, $istoday),
            'label' => get_string('usage_chart_label', 'local_dixeo'),
        ];
    }

    /**
     * Format the week range for display.
     *
     * @param array $weekdates The week date information.
     * @return string Formatted string like "Dec 16 - Dec 22, 2025".
     */
    protected function format_week_range(array $weekdates): string {
        $start = new \DateTime($weekdates['start']);
        $end = new \DateTime($weekdates['end']);

        return userdate($start->getTimestamp(), '%b %d') . ' - ' . userdate($end->getTimestamp(), '%b %d, %Y');
    }

    /**
     * Format a transaction for display.
     *
     * @param array $tx The transaction data.
     * @return array The formatted transaction.
     */
    protected function format_transaction(array $tx): array {
        $amount = $tx['amount'] ?? 0;
        $type = $tx['type'] ?? 'unknown';

        return [
            'id' => $tx['id'] ?? '',
            'type' => $type,
            'type_label' => get_string('transaction_type_' . $type, 'local_dixeo'),
            'type_class' => $this->get_transaction_type_class($type),
            'amount' => $amount,
            'amount_formatted' => credit_service::format_credits(abs($amount)),
            'amount_sign' => $amount >= 0 ? '+' : '-',
            'description' => $tx['description'] ?? '',
            'created_at' => $tx['created_at'] ?? 0,
            'created_at_formatted' => userdate($tx['created_at'] ?? 0, get_string('strftimedatetime', 'langconfig')),
            'balance_after' => $tx['balance_after'] ?? null,
            'balance_after_formatted' => isset($tx['balance_after'])
                ? credit_service::format_credits($tx['balance_after'])
                : null,
        ];
    }

    /**
     * Build pagination data.
     *
     * @param array $apipagination The pagination from API.
     * @return array The pagination data for template.
     */
    protected function build_pagination(array $apipagination): array {
        $total = $apipagination['total'] ?? 0;
        $limit = $apipagination['limit'] ?? $this->limit;
        $offset = $apipagination['offset'] ?? $this->offset;
        $hasmore = $apipagination['has_more'] ?? false;

        $totalpages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $currentpage = $limit > 0 ? (int) floor($offset / $limit) + 1 : 1;

        return [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => $hasmore,
            'total_pages' => $totalpages,
            'current_page' => $currentpage,
            'has_prev' => $offset > 0,
            'has_next' => $hasmore,
            'prev_offset' => max(0, $offset - $limit),
            'next_offset' => $offset + $limit,
        ];
    }

    /**
     * Get CSS class for account state.
     *
     * @param string $state The account state.
     * @return string The CSS class.
     */
    protected function get_state_class(string $state): string {
        return match ($state) {
            'active' => 'success',
            'frozen' => 'warning',
            'suspended' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get CSS class for transaction type.
     *
     * @param string $type The transaction type.
     * @return string The CSS class.
     */
    protected function get_transaction_type_class(string $type): string {
        return match ($type) {
            'purchase' => 'success',
            'deduction' => 'danger',
            'refund' => 'info',
            default => 'secondary',
        };
    }
}
