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


/**
 * Read operations for tasks.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /**
     * Get task categories for filter rendering.
     *
     * @return array
     */
    public static function get_categories(): array {
        global $DB;

        return $DB->get_records('dutydesk_category', null, 'name ASC', 'id, name');
    }

    /**
     * Get the current task assignment with its department.
     *
     * @param int $taskid
     * @return \stdClass|null
     */
    public static function get_assignment_info(int $taskid): ?\stdClass {
        global $DB;

        if ($taskid <= 0) {
            return null;
        }

        $record = $DB->get_record_sql(
            "SELECT ta.positionid, p.departmentid
               FROM {dutydesk_taskassignment} ta
               JOIN {dutydesk_position} p ON p.id = ta.positionid
              WHERE ta.taskid = :taskid",
            ['taskid' => $taskid]
        );

        return $record ?: null;
    }

    /**
     * Get a task record for form editing.
     *
     * @param int $taskid
     * @return \stdClass
     */
    public static function get_task_for_form(int $taskid): \stdClass {
        global $DB;

        return $DB->get_record('dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);
    }

    /**
     * Get the assignment record for a task.
     *
     * @param int $taskid
     * @return \stdClass|null
     */
    public static function get_task_assignment(int $taskid): ?\stdClass {
        global $DB;

        $record = $DB->get_record('dutydesk_taskassignment', ['taskid' => $taskid]);
        return $record ?: null;
    }

    /**
     * Get departments available for the task filter.
     *
     * @param bool $canviewalltasks
     * @param array $userdepartmentids
     * @param array $manageablepositionids
     * @return array
     */
    public static function get_filter_departments(
        bool $canviewalltasks,
        array $userdepartmentids,
        array $manageablepositionids
    ): array {
        global $DB;

        if ($canviewalltasks) {
            return $DB->get_records('dutydesk_department', null, 'name ASC', 'id, name');
        }

        $alloweddepartmentids = $userdepartmentids;
        if (!empty($manageablepositionids)) {
            [$positionsql, $positionparams] = $DB->get_in_or_equal($manageablepositionids, SQL_PARAMS_NAMED);
            $manageddepartmentidsrecords = $DB->get_fieldset_sql(
                "SELECT DISTINCT departmentid
                   FROM {dutydesk_position}
                  WHERE id {$positionsql}
                    AND departmentid IS NOT NULL
                    AND departmentid > 0",
                $positionparams
            );
            $alloweddepartmentids = array_merge($alloweddepartmentids, array_map('intval', $manageddepartmentidsrecords));
        }

        $alloweddepartmentids = array_values(array_unique(array_filter(array_map('intval', $alloweddepartmentids))));
        if (empty($alloweddepartmentids)) {
            return [];
        }

        [$deptsql, $deptparams] = $DB->get_in_or_equal($alloweddepartmentids, SQL_PARAMS_NAMED);
        return $DB->get_records_select('dutydesk_department', "id {$deptsql}", $deptparams, 'name ASC', 'id, name');
    }
}
