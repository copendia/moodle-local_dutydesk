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

namespace local_dutydesk\local\department;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/lib.php');

/**
 * Persistence operations for departments.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Delete a department and its manager assignments.
     *
     * @param int $departmentid
     * @return void
     */
    public static function delete_department(int $departmentid): void {
        global $DB;

        if ($departmentid <= 0) {
            return;
        }

        $DB->delete_records('dutydesk_department', ['id' => $departmentid]);
        $DB->delete_records('dutydesk_department_manager', ['departmentid' => $departmentid]);
    }

    /**
     * Create a department.
     *
     * @param \stdClass $department
     * @param int[] $managerids
     * @param int[] $categoryids
     * @param int $assignedby
     * @return int
     */
    public static function create_department(\stdClass $department, array $managerids, array $categoryids, int $assignedby): int {
        global $DB;

        $departmentid = (int)$DB->insert_record('dutydesk_department', $department);
        \local_dutydesk_set_department_managers($departmentid, $managerids, $assignedby);
        self::sync_categories($departmentid, $categoryids);

        return $departmentid;
    }

    /**
     * Update a department.
     *
     * @param \stdClass $department
     * @param int[]|null $managerids Null keeps existing managers unchanged.
     * @param int[] $categoryids
     * @param int $assignedby
     * @return void
     */
    public static function update_department(\stdClass $department, ?array $managerids, array $categoryids, int $assignedby): void {
        global $DB;

        $DB->update_record('dutydesk_department', $department);

        if ($managerids !== null) {
            \local_dutydesk_set_department_managers((int)$department->id, $managerids, $assignedby);
        }

        self::sync_categories((int)$department->id, $categoryids);
    }

    /**
     * Return categories that can be assigned to a department.
     *
     * @param int $departmentid
     * @return array
     */
    public static function get_category_options(int $departmentid): array {
        global $DB;

        if ($departmentid > 0) {
            $records = $DB->get_records_select(
                'dutydesk_category',
                'departmentid IS NULL OR departmentid = :departmentid',
                ['departmentid' => $departmentid],
                'name ASC',
                'id, name'
            );
        } else {
            $records = $DB->get_records_select(
                'dutydesk_category',
                'departmentid IS NULL',
                [],
                'name ASC',
                'id, name'
            );
        }

        $options = [];
        foreach ($records as $record) {
            $options[(int)$record->id] = format_string($record->name);
        }

        return $options;
    }

    /**
     * Synchronise selected categories with a department.
     *
     * @param int $departmentid
     * @param int[] $categoryids
     * @return void
     */
    public static function sync_categories(int $departmentid, array $categoryids): void {
        global $DB;

        if ($departmentid <= 0) {
            return;
        }

        $categoryids = array_values(array_unique(array_filter(array_map('intval', $categoryids))));
        $allowedoptions = self::get_category_options($departmentid);
        $allowedids = array_map('intval', array_keys($allowedoptions));
        $categoryids = array_values(array_intersect($categoryids, $allowedids));

        if (!empty($categoryids)) {
            [$insql, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
            $params['departmentid'] = $departmentid;
            $DB->execute(
                "UPDATE {dutydesk_category}
                    SET departmentid = :departmentid
                  WHERE id {$insql}",
                $params
            );
        }

        $params = ['departmentid' => $departmentid];
        $wheresql = 'departmentid = :departmentid';
        if (!empty($categoryids)) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'keep', false);
            $wheresql .= " AND id {$notinsql}";
            $params = array_merge($params, $notinparams);
        }

        $DB->execute(
            "UPDATE {dutydesk_category}
                SET departmentid = NULL
              WHERE {$wheresql}",
            $params
        );
    }
}
