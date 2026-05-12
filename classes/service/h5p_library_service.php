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

/**
 * Resolve H5P libraries on this Moodle site against minimum-version requirements.
 *
 * Requirements are expressed as `"Machine.Name MAJOR.MINOR"`. The MAJOR must
 * match exactly; any installed MINOR `>=` the requested one is considered
 * compatible (H5P's library convention guarantees backward compatibility
 * within a major version). The site's actually-installed version is exposed
 * via {@see resolve_installed_version()} so packagers can write the real
 * version into `h5p.json` — writing a version Moodle doesn't have causes the
 * import to fail.
 *
 * @package    local_dixeo
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5p_library_service {

    /** @var array<string, array{machinename: string, major: int, minor: int}|null> Per-request memo. */
    private static array $cache = [];

    /**
     * Whether any installed H5P library satisfies the minimum-version requirement.
     *
     * @param string $library Library identifier as emitted by the API (e.g. "H5P.QuestionSet 1.20").
     */
    public static function is_installed(string $library): bool {
        return self::resolve_installed_version($library) !== null;
    }

    /**
     * Resolve the actual installed version that satisfies the requirement.
     *
     * Returns the highest installed minor for the requested major, or null when
     * no compatible version is present. Callers needing to embed the version in
     * a packaged `.h5p` must use this rather than the API's pinned identifier —
     * Moodle rejects packages referencing a library version it does not have.
     *
     * @return array{machinename: string, major: int, minor: int}|null
     */
    public static function resolve_installed_version(string $library): ?array {
        global $DB;

        $library = trim($library);
        if ($library === '') {
            return null;
        }
        if (array_key_exists($library, self::$cache)) {
            return self::$cache[$library];
        }

        $parsed = self::parse_identifier($library);
        if ($parsed === null) {
            return self::$cache[$library] = null;
        }

        $row = $DB->get_record_sql(
            'SELECT machinename, majorversion, minorversion
               FROM {h5p_libraries}
              WHERE machinename = :name
                AND majorversion = :major
                AND minorversion >= :minor
           ORDER BY minorversion DESC, patchversion DESC',
            ['name' => $parsed['machinename'], 'major' => $parsed['major'], 'minor' => $parsed['minor']],
            IGNORE_MULTIPLE,
        );
        if (!$row) {
            return self::$cache[$library] = null;
        }

        return self::$cache[$library] = [
            'machinename' => $row->machinename,
            'major' => (int) $row->majorversion,
            'minor' => (int) $row->minorversion,
        ];
    }

    /**
     * Reset the per-request cache; intended for tests.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

    /**
     * Split "Machine.Name MAJOR.MINOR" into structured fields.
     *
     * @return array{machinename: string, major: int, minor: int}|null
     */
    private static function parse_identifier(string $library): ?array {
        if (!preg_match('/^(\S+)\s+(\d+)\.(\d+)$/', $library, $matches)) {
            return null;
        }
        return [
            'machinename' => $matches[1],
            'major' => (int) $matches[2],
            'minor' => (int) $matches[3],
        ];
    }
}
