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

namespace local_dutydesk\local\task;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Persistence operations for tasks.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Delete a task and log the deletion in task history when the task exists.
     *
     * @param int $taskid
     * @return void
     */
    public static function delete_task(int $taskid): void {
        global $DB;

        if ($taskid <= 0) {
            return;
        }

        $taskrecord = $DB->get_record('dutydesk_task', ['id' => $taskid]);
        if ($taskrecord) {
            \local_dutydesk_log_task_history((int)$taskrecord->id, 'deleted');
        }

        $DB->delete_records('dutydesk_task', ['id' => $taskid]);
    }
}
