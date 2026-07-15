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
 * Exception for job not found errors (HTTP 404).
 *
 * Thrown when the requested job does not exist.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class job_not_found_exception extends api_exception {
    /**
     * Constructor.
     *
     * @param string $message Human-readable error message.
     * @param array $details Additional error details.
     */
    public function __construct(string $message = 'Job not found', array $details = []) {
        parent::__construct('job_not_found', $message, 404, $details);
    }
}
