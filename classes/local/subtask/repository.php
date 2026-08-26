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

namespace local_dutydesk\local\subtask;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

/**
 * Read operations for subtasks.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /**
     * Get a subtask by id.
     *
     * @param int $subtaskid
     * @return \stdClass|null
     */
    public static function get_subtask(int $subtaskid): ?\stdClass {
        global $DB;

        if ($subtaskid <= 0) {
            return null;
        }

        $record = $DB->get_record('local_dutydesk_subtask', ['id' => $subtaskid], '*', MUST_EXIST);
        return $record ?: null;
    }

    /**
     * Get a task by id.
     *
     * @param int $taskid
     * @return \stdClass
     */
    public static function get_task(int $taskid): \stdClass {
        global $DB;

        return $DB->get_record('local_dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);
    }

    /**
     * Get the highest sort order for a task's subtasks.
     *
     * @param int $taskid
     * @return int
     */
    public static function get_max_sortorder(int $taskid): int {
        global $DB;

        return (int)$DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {local_dutydesk_subtask} WHERE taskid = :taskid',
            ['taskid' => $taskid]
        );
    }
}
