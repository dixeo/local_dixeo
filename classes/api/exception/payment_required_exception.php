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

namespace local_dixeo\api\exception;

/**
 * Exception for payment required errors (HTTP 402).
 *
 * Thrown when the account is frozen due to insufficient credits.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_required_exception extends api_exception {

    /** @var int|null Current credit balance. */
    protected ?int $currentbalance;

    /**
     * Constructor.
     *
     * @param string $message Human-readable error message.
     * @param int|null $currentbalance Current credit balance.
     * @param array $details Additional error details.
     */
    public function __construct(
        string $message = 'Account frozen - insufficient credits',
        ?int $currentbalance = null,
        array $details = []
    ) {
        $this->currentbalance = $currentbalance;
        parent::__construct('payment_required', $message, 402, $details);
    }

    /**
     * Get the current credit balance.
     *
     * @return int|null The current balance, or null if not available.
     */
    public function get_current_balance(): ?int {
        return $this->currentbalance;
    }
}
