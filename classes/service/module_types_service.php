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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\service;

use cache;
use local_dixeo\api\client;
use local_dixeo\api\exception\api_exception;

/**
 * Service for Dixeo module type catalogue (activity chooser / designer).
 *
 * Caches the raw /v1/modules/types API payload in application cache (same pattern
 * as {@see course_template_service::get_cached_choices()}). Installed flags and
 * localized labels are applied per request in {@see \local_dixeo\external\get_module_types}.
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_types_service {

    /** @var string Dixeo API endpoint for module type catalogue. */
    private const ENDPOINT = '/v1/modules/types';

    /** @var string Application cache key for the raw type list. */
    private const CACHE_KEY = 'moduletypes';

    /** @var client The API client. */
    protected client $client;

    /**
     * Constructor.
     *
     * @param client|null $client Optional API client (creates new if not provided).
     */
    public function __construct(?client $client = null) {
        $this->client = $client ?? new client();
    }

    /**
     * Raw module type rows from the Dixeo API, application-cached when configured.
     *
     * Does not set installed or localized labels (done in the web service).
     *
     * @return array List of associative arrays (type, label, description, category, component, …).
     * @throws api_exception If the API is not configured or the request fails.
     */
    public function get_module_types_cached(): array {
        // When not configured, skip cache so we never serve stale data after key removal.
        if ($this->client->is_configured()) {
            $cache = cache::make('local_dixeo', 'moduletypes');
            $cached = $cache->get(self::CACHE_KEY);
            if ($cached !== false) {
                return $this->copy_type_rows($cached);
            }
        }

        $types = $this->client->get(self::ENDPOINT);
        if (!is_array($types)) {
            $types = [];
        }
        $types = array_values(array_filter($types, 'is_array'));

        if ($this->client->is_configured()) {
            $cache = cache::make('local_dixeo', 'moduletypes');
            $cache->set(self::CACHE_KEY, $types);
        }

        return $this->copy_type_rows($types);
    }

    /**
     * Shallow-copy each row so callers can add keys without mutating cached structures.
     *
     * @param array $types Raw rows.
     * @return array Copy of rows.
     */
    private function copy_type_rows(array $types): array {
        return array_map(static function (array $row): array {
            return array_merge([], $row);
        }, $types);
    }
}
