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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_dutydesk\local\task_import;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


/**
 * Session storage for pending task imports.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_store {
    /** @var string Session key for pending task imports. */
    private const SESSION_KEY = 'local_dutydesk_task_import';

    /**
     * Ensure the session bucket exists.
     *
     * @return void
     */
    public static function initialise(): void {
        global $SESSION;

        if (!isset($SESSION->{self::SESSION_KEY}) || !is_array($SESSION->{self::SESSION_KEY})) {
            $SESSION->{self::SESSION_KEY} = [];
        }
    }

    /**
     * Store a pending import and return its token.
     *
     * @param array $items
     * @param array $warnings
     * @param int $departmentid
     * @param string $departmentname
     * @return string
     */
    public static function add(array $items, array $warnings, int $departmentid, string $departmentname): string {
        global $SESSION;

        self::initialise();
        $token = random_string(24);
        $SESSION->{self::SESSION_KEY}[$token] = [
            'items' => $items,
            'warnings' => $warnings,
            'departmentid' => $departmentid,
            'departmentname' => $departmentname,
            'timecreated' => time(),
        ];

        return $token;
    }

    /**
     * Return a pending import by token.
     *
     * @param string $token
     * @return array|null
     */
    public static function get(string $token): ?array {
        global $SESSION;

        self::initialise();
        return $SESSION->{self::SESSION_KEY}[$token] ?? null;
    }

    /**
     * Remove a pending import.
     *
     * @param string $token
     * @return void
     */
    public static function remove(string $token): void {
        global $SESSION;

        self::initialise();
        unset($SESSION->{self::SESSION_KEY}[$token]);
    }

    /**
     * Return a pending import plus its token for preview rendering.
     *
     * @param string $token
     * @return array
     */
    public static function get_preview(string $token): array {
        $preview = self::get($token);
        if ($preview === null) {
            return [];
        }
        $preview['token'] = $token;

        return $preview;
    }
}
