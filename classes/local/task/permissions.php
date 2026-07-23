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


use context;
use required_capability_exception;

/**
 * Permission checks for task pages and task records.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions {
    /**
     * Require access to the task page.
     *
     * @param context $context
     * @param int $userid
     * @return void
     */
    public static function require_view_page(context $context, int $userid): void {
        if (
            !has_any_capability(['local/dutydesk:viewown', 'local/dutydesk:viewall', 'local/dutydesk:manageall'], $context)
            && !\local_dutydesk_user_can_view_department_scope($userid)
        ) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:viewown',
                'nopermissions',
                get_string('tasks', 'local_dutydesk')
            );
        }
    }

    /**
     * Require permission to edit a task.
     *
     * @param int $taskid
     * @param bool $canmanageall
     * @param context $context
     * @return void
     */
    public static function require_edit_task(int $taskid, bool $canmanageall, context $context): void {
        if ($taskid > 0 && !$canmanageall && !\local_dutydesk_user_can_edit_task($taskid)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:manageown',
                'nopermissions',
                get_string('tasks', 'local_dutydesk')
            );
        }
    }

    /**
     * Require permission to create a new task.
     *
     * @param int $taskid
     * @param context $context
     * @return void
     */
    public static function require_create_task(int $taskid, context $context): void {
        if ($taskid <= 0) {
            require_capability('local/dutydesk:manageall', $context);
        }
    }

    /**
     * Require permission to delete a task.
     *
     * @param context $context
     * @return void
     */
    public static function require_delete_task(context $context): void {
        require_capability('local/dutydesk:manageall', $context);
    }

    /**
     * Resolve whether the current form may edit workload values.
     *
     * @param bool $canmanageall
     * @param int $currentassignmentdepartmentid
     * @param array $manageddepartmentids
     * @param int $userid
     * @return bool
     */
    public static function can_manage_workload_field(
        bool $canmanageall,
        int $currentassignmentdepartmentid,
        array $manageddepartmentids,
        int $userid
    ): bool {
        if ($canmanageall) {
            return true;
        }
        if ($currentassignmentdepartmentid > 0) {
            return \local_dutydesk_user_manages_department($currentassignmentdepartmentid, $userid);
        }
        return !empty($manageddepartmentids);
    }
}
