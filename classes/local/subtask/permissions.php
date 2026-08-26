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

use context;
use required_capability_exception;

/**
 * Permission checks for subtask requests.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions {
    /**
     * Require general access to manage subtasks.
     *
     * @param context $context
     * @param int $userid
     * @return void
     */
    public static function require_page_access(context $context, int $userid): void {
        if (
            !has_any_capability(['local/dutydesk:manageall', 'local/dutydesk:manageown'], $context)
            && !\local_dutydesk_user_can_manage_departments($userid)
        ) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:manageown',
                'nopermissions',
                get_string('subtasks', 'local_dutydesk')
            );
        }
    }

    /**
     * Require permission to edit subtasks for a task.
     *
     * @param context $context
     * @param int $taskid
     * @param bool $canmanageall
     * @return void
     */
    public static function require_edit_task_subtasks(context $context, int $taskid, bool $canmanageall): void {
        if (!$canmanageall && !\local_dutydesk_user_can_edit_task($taskid)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:manageown',
                'nopermissions',
                get_string('subtasks', 'local_dutydesk')
            );
        }
    }

    /**
     * Require permission to delete a subtask.
     *
     * @param context $context
     * @return void
     */
    public static function require_delete_subtask(context $context): void {
        require_capability('local/dutydesk:manageall', $context);
    }
}
