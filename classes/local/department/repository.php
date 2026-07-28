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


/**
 * Read operations for departments.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /**
     * Fetch paginated department records for the current user scope.
     *
     * @param bool $canmanageall
     * @param int[] $manageddepartmentids
     * @param int $page
     * @param int $perpage
     * @return \stdClass
     */
    public static function get_paginated_departments(
        bool $canmanageall,
        array $manageddepartmentids,
        int $page,
        int $perpage
    ): \stdClass {
        global $DB;

        $offset = $page * $perpage;
        $records = [];
        $totaldepartments = 0;

        if ($canmanageall) {
            $totaldepartments = $DB->count_records('local_dutydesk_department');
            if ($totaldepartments > 0) {
                $records = $DB->get_records('local_dutydesk_department', null, 'name ASC, id ASC', '*', $offset, $perpage);
            }
        } else if (!empty($manageddepartmentids)) {
            $totaldepartments = count($manageddepartmentids);
            [$insql, $params] = $DB->get_in_or_equal($manageddepartmentids, SQL_PARAMS_NAMED);
            $records = $DB->get_records_select(
                'local_dutydesk_department',
                "id {$insql}",
                $params,
                'name ASC, id ASC',
                '*',
                $offset,
                $perpage
            );
        }

        if ($totaldepartments > 0 && $offset >= $totaldepartments) {
            $page = (int)floor(($totaldepartments - 1) / $perpage);
            return self::get_paginated_departments($canmanageall, $manageddepartmentids, $page, $perpage);
        }

        return (object) [
            'records' => $records,
            'totaldepartments' => $totaldepartments,
            'page' => $page,
        ];
    }

    /**
     * Fetch positions grouped by department id.
     *
     * @param int[] $departmentids
     * @return array
     */
    public static function get_positions_by_department(array $departmentids): array {
        global $DB;

        if (empty($departmentids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($departmentids, SQL_PARAMS_NAMED);
        $positionrecords = $DB->get_records_sql(
            "SELECT p.id, p.title, p.departmentid
               FROM {local_dutydesk_position} p
              WHERE p.departmentid {$insql}
           ORDER BY p.title ASC",
            $params
        );

        $positionsbydepartment = [];
        foreach ($positionrecords as $position) {
            if (!empty($position->departmentid)) {
                $positionsbydepartment[$position->departmentid][] = $position;
            }
        }

        return $positionsbydepartment;
    }

    /**
     * Fetch department managers grouped by department id.
     *
     * @param int[] $departmentids
     * @return array
     */
    public static function get_managers_by_department(array $departmentids): array {
        global $DB;

        if (empty($departmentids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($departmentids, SQL_PARAMS_NAMED);
        $managerrecords = $DB->get_records_sql(
            "SELECT dm.departmentid, u.id, u.firstname, u.lastname, u.middlename,
                    u.alternatename, u.firstnamephonetic, u.lastnamephonetic
               FROM {local_dutydesk_deptmgr} dm
               JOIN {user} u ON u.id = dm.userid
              WHERE dm.departmentid {$insql}
           ORDER BY u.lastname ASC, u.firstname ASC",
            $params
        );

        $managersbydepartment = [];
        foreach ($managerrecords as $manager) {
            $managersbydepartment[$manager->departmentid][] = $manager;
        }

        return $managersbydepartment;
    }
}
