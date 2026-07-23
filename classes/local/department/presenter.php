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
 * Prepares department records for Mustache templates.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presenter {
    /**
     * Build department list template data.
     *
     * @param array $records
     * @param \context $context
     * @param array $positionsbydepartment
     * @param array $managersbydepartment
     * @param bool $canmanageall
     * @param int[] $manageddepartmentids
     * @return array
     */
    public static function build(
        array $records,
        \context $context,
        array $positionsbydepartment,
        array $managersbydepartment,
        bool $canmanageall,
        array $manageddepartmentids
    ): array {
        $departmentsdata = [];
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);

        foreach ($records as $department) {
            $editurl = new \moodle_url('/local/dutydesk/departments.php', ['id' => $department->id]);
            $editmodalurl = new \moodle_url('/local/dutydesk/departments.php', [
                'id' => $department->id,
                'ajax' => 1,
                'modaledit' => 1,
                'sesskey' => sesskey(),
            ]);
            $deleteurl = new \moodle_url('/local/dutydesk/departments.php', [
                'delete' => 1,
                'id' => $department->id,
            ]);

            $description = trim($department->description ?? '');
            $departmentpositions = self::build_positions($positionsbydepartment[$department->id] ?? []);
            $departmentmanagers = self::build_managers($managersbydepartment[$department->id] ?? [], $canviewfullnames);
            $canmanage = $canmanageall || in_array((int)$department->id, $manageddepartmentids, true);
            $candelete = $canmanageall;

            $departmentsdata[] = [
                'id' => $department->id,
                'name' => format_string($department->name),
                'description' => $description !== '' ? format_text($description, FORMAT_PLAIN, ['context' => $context]) : '',
                'hasdescription' => $description !== '',
                'timestamp' => userdate($department->timestamp),
                'editurl' => $canmanage ? $editurl->out(false) : null,
                'editmodalurl' => $canmanage ? $editmodalurl->out(false) : null,
                'deleteurl' => $candelete ? $deleteurl->out(false) : null,
                'haspositions' => !empty($departmentpositions),
                'positions' => $departmentpositions,
                'positioncount' => count($departmentpositions),
                'hasmanagers' => !empty($departmentmanagers),
                'managers' => $departmentmanagers,
                'managercount' => count($departmentmanagers),
                'canmanage' => $canmanage,
                'candelete' => $candelete,
                'sesskey' => sesskey(),
            ];
        }

        return $departmentsdata;
    }

    /**
     * Build position links for a department.
     *
     * @param array $positions
     * @return array
     */
    private static function build_positions(array $positions): array {
        $departmentpositions = [];
        foreach ($positions as $position) {
            $positionurl = new \moodle_url('/local/dutydesk/positions.php', [
                'focus' => $position->id,
            ]);
            $positionurl->set_anchor('position-' . $position->id);
            $departmentpositions[] = [
                'title' => format_string($position->title),
                'url' => $positionurl->out(false),
            ];
        }

        return $departmentpositions;
    }

    /**
     * Build manager profile links for a department.
     *
     * @param array $managers
     * @param bool $canviewfullnames
     * @return array
     */
    private static function build_managers(array $managers, bool $canviewfullnames): array {
        $departmentmanagers = [];
        foreach ($managers as $manager) {
            $profileurl = new \moodle_url('/user/profile.php', ['id' => $manager->id]);
            $departmentmanagers[] = [
                'name' => fullname($manager, $canviewfullnames),
                'profileurl' => $profileurl->out(false),
            ];
        }

        return $departmentmanagers;
    }
}
