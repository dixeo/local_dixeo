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

namespace local_dixeo\dto;

/**
 * Data transfer object for credit balance from the API.
 *
 * Represents the current credit balance and account state.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class credit_balance {
    /** @var string Account is active and can process jobs. */
    public const STATE_ACTIVE = 'active';

    /** @var string Account is frozen due to low balance. */
    public const STATE_FROZEN = 'frozen';

    /** @var string Account is suspended. */
    public const STATE_SUSPENDED = 'suspended';

    /**
     * Constructor.
     *
     * @param int $credits Current credit balance.
     * @param string $state Account state (active, frozen, suspended).
     */
    public function __construct(
        /** @var int Current credit balance. */
        public readonly int $credits,
        /** @var string Account state (active, frozen, suspended). */
        public readonly string $state
    ) {
    }

    /**
     * Create a credit_balance from API response array.
     *
     * @param array $data The API response data.
     * @return self The credit balance DTO.
     */
    public static function from_array(array $data): self {
        return new self(
            credits: $data['credits'],
            state: $data['state']
        );
    }

    /**
     * Check if the account is active.
     *
     * @return bool True if the account can process jobs.
     */
    public function is_active(): bool {
        return $this->state === self::STATE_ACTIVE;
    }

    /**
     * Check if the account is frozen.
     *
     * @return bool True if the account is frozen due to low balance.
     */
    public function is_frozen(): bool {
        return $this->state === self::STATE_FROZEN;
    }

    /**
     * Check if the account is suspended.
     *
     * @return bool True if the account is suspended.
     */
    public function is_suspended(): bool {
        return $this->state === self::STATE_SUSPENDED;
    }

    /**
     * Get the balance formatted as a credits string.
     *
     * @return string The formatted balance (e.g., '10,000 credits').
     */
    public function get_formatted_balance(): string {
        return number_format($this->credits) . ' ' . get_string('credits', 'local_dixeo');
    }

    /**
     * Get a human-readable state description.
     *
     * @return string The state description.
     */
    public function get_state_description(): string {
        return match ($this->state) {
            self::STATE_ACTIVE => get_string('state_active', 'local_dixeo'),
            self::STATE_FROZEN => get_string('state_frozen', 'local_dixeo'),
            self::STATE_SUSPENDED => get_string('state_suspended', 'local_dixeo'),
            default => ucfirst($this->state),
        };
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array The credit balance as an array.
     */
    public function to_array(): array {
        return [
            'credits' => $this->credits,
            'state' => $this->state,
        ];
    }
}
