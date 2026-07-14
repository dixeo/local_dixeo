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
 * Exception for rate limit exceeded errors (HTTP 429).
 *
 * Thrown when too many requests have been made to the API.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limit_exception extends api_exception {
    /** @var int|null Seconds to wait before retrying. */
    protected ?int $retryafter;

    /**
     * Constructor.
     *
     * @param string $message Human-readable error message.
     * @param int|null $retryafter Seconds to wait before retrying.
     * @param array $details Additional error details.
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        ?int $retryafter = null,
        array $details = []
    ) {
        $this->retryafter = $retryafter;
        parent::__construct('rate_limit_exceeded', $message, 429, $details);
    }

    /**
     * Get the retry-after value.
     *
     * @return int|null Seconds to wait before retrying, or null if not specified.
     */
    public function get_retry_after(): ?int {
        return $this->retryafter;
    }
}
