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


use moodle_url;

/**
 * Builds template data for the task page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presenter {
    /**
     * Build the category filter dropdown data.
     *
     * @param array $categoryrecords
     * @param int $selectedcategoryid
     * @return array|null
     */
    public static function build_category_filter(array $categoryrecords, int $selectedcategoryid): ?array {
        if (empty($categoryrecords)) {
            return null;
        }

        $options = [[
            'value' => 0,
            'label' => get_string('taskcategoryfilter_all', 'local_dutydesk'),
            'selected' => $selectedcategoryid === 0,
        ]];
        foreach ($categoryrecords as $category) {
            $options[] = [
                'value' => (int)$category->id,
                'label' => format_string($category->name),
                'selected' => (int)$category->id === $selectedcategoryid,
            ];
        }

        return [
            'label' => get_string('taskcategoryfilter', 'local_dutydesk'),
            'options' => $options,
        ];
    }

    /**
     * Build the department filter dropdown data.
     *
     * @param array $departmentrecords
     * @param int $selecteddepartmentid
     * @return array|null
     */
    public static function build_department_filter(array $departmentrecords, int $selecteddepartmentid): ?array {
        if (empty($departmentrecords)) {
            return null;
        }

        $options = [[
            'value' => 0,
            'label' => get_string('taskdepartmentfilter_all', 'local_dutydesk'),
            'selected' => $selecteddepartmentid === 0,
        ]];
        foreach ($departmentrecords as $departmentrecord) {
            $options[] = [
                'value' => (int)$departmentrecord->id,
                'label' => format_string($departmentrecord->name),
                'selected' => (int)$departmentrecord->id === $selecteddepartmentid,
            ];
        }

        return [
            'label' => get_string('taskdepartmentfilter', 'local_dutydesk'),
            'options' => $options,
        ];
    }

    /**
     * Build the task list template data.
     *
     * @param array $tasksdata
     * @param string $searchvalue
     * @param moodle_url $searchbaseurl
     * @param int $perpage
     * @param int $page
     * @param int $positionfilterid
     * @param bool $vacantonly
     * @param array|null $categoryfilterdata
     * @param array|null $departmentfilterdata
     * @return array
     */
    public static function build_list_template_data(
        array $tasksdata,
        string $searchvalue,
        moodle_url $searchbaseurl,
        int $perpage,
        int $page,
        int $positionfilterid,
        bool $vacantonly,
        ?array $categoryfilterdata,
        ?array $departmentfilterdata
    ): array {
        $searchhiddenparams = [
            ['name' => 'perpage', 'value' => $perpage],
            ['name' => 'page', 'value' => $page],
        ];
        if ($positionfilterid > 0) {
            $searchhiddenparams[] = ['name' => 'positionid', 'value' => $positionfilterid];
        }

        $templatedata = [
            'displaysearch' => true,
            'cansearch' => true,
            'searchplaceholder' => get_string('searchtasksplaceholder', 'local_dutydesk'),
            'searchvalue' => $searchvalue,
            'searchaction' => $searchbaseurl->out(false),
            'searchhiddenparams' => $searchhiddenparams,
            'vacantfilter' => [
                'name' => 'vacantonly',
                'label' => get_string('taskvacantfilter', 'local_dutydesk'),
                'checked' => $vacantonly,
            ],
            'perpage' => $perpage,
            'tasks' => array_map(static function ($task) {
                $task['searchtext'] = $task['searchtext'] ?? '';
                return $task;
            }, $tasksdata),
            'sesskey' => sesskey(),
            'historyendpoint' => (new moodle_url('/local/dutydesk/task_history.php'))->out(false),
        ];

        if ($categoryfilterdata !== null) {
            $templatedata['categoryfilter'] = $categoryfilterdata;
        }
        if ($departmentfilterdata !== null) {
            $templatedata['departmentfilter'] = $departmentfilterdata;
        }

        return $templatedata;
    }
}
