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

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/classes/form/position_form.php');
require_once($pluginroot . '/classes/local/position/permissions.php');
require_once($pluginroot . '/classes/local/position/manager.php');
require_once($pluginroot . '/lib.php');

use context;
use moodle_url;
use required_capability_exception;

/**
 * Handles position form display and submission.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_handler {
    /**
     * Handles process.
     *
     * @return mixed
     */
    public static function process(
        int $id,
        bool $isajax,
        bool $ismodaledit,
        string $view,
        int $perpage,
        int $page,
        int $positionfilterid,
        string $requestedpositiontype,
        bool $hassearch,
        string $searchvalue,
        bool $canmanageall,
        array $manageddepartmentids,
        array $manageablepositionids,
        bool $caneditpositions,
        context $context,
        moodle_url $currentpageurl,
        bool &$showform
    ): string {
        global $DB, $PAGE;

        $departmentoptions = self::get_department_options($canmanageall, $manageddepartmentids);
        $taskconfig = self::get_task_form_config($id);
        $formactionurl = self::get_form_action_url(
            $isajax,
            $ismodaledit,
            $id,
            $view,
            $perpage,
            $page,
            $positionfilterid,
            $requestedpositiontype,
            $hassearch,
            $searchvalue
        );

        $form = new \local_dutydesk\form\position_form($formactionurl, [
            'departmentoptions' => $departmentoptions,
            'canmanageall' => $canmanageall,
            'taskoptions' => $taskconfig->taskoptions,
            'taskcategoryoptions' => $taskconfig->taskcategoryoptions,
            'taskcategorydepartments' => $taskconfig->taskcategorydepartments,
            'showtaskassignment' => true,
            'defaultpositiontype' => $requestedpositiontype,
        ]);

        if (!empty($taskconfig->taskoptioncategories)) {
            $PAGE->requires->js_call_amd('local_dutydesk/position_task_category_filter', 'init', [
                $taskconfig->taskoptioncategories,
                $taskconfig->taskcategorydepartments,
            ]);
        }

        if ($form->is_cancelled()) {
            if ($isajax && $ismodaledit) {
                self::close_modal_and_exit();
            }
            $redirecturl = new moodle_url($currentpageurl);
            if ($id) {
                $redirecturl->param('focus', $id);
            }
            redirect($redirecturl);
        } else if ($data = $form->get_data()) {
            self::save_form_data(
                $data,
                $isajax,
                $ismodaledit,
                $canmanageall,
                $manageddepartmentids,
                $caneditpositions,
                $manageablepositionids,
                $context,
                $currentpageurl
            );
        } else if ($id) {
            permissions::require_manage_position($id, $caneditpositions, $canmanageall, $manageablepositionids, $context);
            $record = $DB->get_record('local_dutydesk_position', ['id' => $id]);
            if ($record) {
                $deputy = $DB->get_record('local_dutydesk_posdeputy', ['positionid' => $record->id]);
                if ($deputy) {
                    $record->deputyuserid = $deputy->userid;
                }
                $record->positiontype = \local_dutydesk_normalize_position_type($record->positiontype ?? null);
                $taskassignmentrecords = $DB->get_records_menu(
                    'local_dutydesk_taskassign',
                    ['positionid' => $record->id],
                    '',
                    'taskid, taskid'
                );
                if (!empty($taskassignmentrecords)) {
                    $record->taskids = array_map('intval', array_keys($taskassignmentrecords));
                }
            }
            $form->set_data($record);
            $showform = 1;
        }

        if ($form->is_submitted() && !$form->is_cancelled()) {
            $showform = 1;
        }

        ob_start();
        $form->display();
        return ob_get_clean();
    }

    /**
     * Handles get_department_options.
     *
     * @return mixed
     */
    private static function get_department_options(bool $canmanageall, array $manageddepartmentids): array {
        global $DB;

        if ($canmanageall) {
            return $DB->get_records_menu('local_dutydesk_department', null, 'name ASC', 'id, name');
        }
        if (empty($manageddepartmentids)) {
            return [];
        }

        $departmentoptions = [];
        $departmentrecords = $DB->get_records_list('local_dutydesk_department', 'id', $manageddepartmentids, 'name ASC', 'id, name');
        foreach ($departmentrecords as $dept) {
            $departmentoptions[$dept->id] = $dept->name;
        }
        return $departmentoptions;
    }

    /**
     * Handles get_task_form_config.
     *
     * @return mixed
     */
    private static function get_task_form_config(int $positionid): \stdClass {
        global $DB;

        $taskoptions = [];
        $taskoptioncategories = [];
        $taskcategoryoptions = [];
        $taskcategorydepartments = [];
        $taskassignmentmap = $DB->get_records_menu('local_dutydesk_taskassign', null, '', 'taskid, positionid');
        $taskrecords = $DB->get_records_sql(
            "SELECT t.id, t.title, t.categoryid, c.name AS categoryname, c.departmentid AS categorydepartmentid
               FROM {local_dutydesk_task} t
          LEFT JOIN {local_dutydesk_category} c ON c.id = t.categoryid
           ORDER BY c.name ASC, t.title ASC"
        );
        $assignedlabel = ' ' . get_string('positiontasksassignedlabel', 'local_dutydesk');
        foreach ($taskrecords as $taskrecord) {
            $label = format_string($taskrecord->title);
            if (
                $positionid > 0 && isset($taskassignmentmap[$taskrecord->id])
                && (int)$taskassignmentmap[$taskrecord->id] === (int)$positionid
            ) {
                $label .= $assignedlabel;
            }
            $taskoptions[$taskrecord->id] = $label;
            $categoryid = !empty($taskrecord->categoryid) ? (int)$taskrecord->categoryid : 0;
            $taskoptioncategories[$taskrecord->id] = $categoryid;
            if ($categoryid > 0 && !isset($taskcategoryoptions[$categoryid])) {
                $taskcategoryoptions[$categoryid] = format_string($taskrecord->categoryname ?? '');
                $taskcategorydepartments[$categoryid] = !empty($taskrecord->categorydepartmentid)
                    ? (int)$taskrecord->categorydepartmentid
                    : 0;
            }
        }
        asort($taskcategoryoptions, SORT_NATURAL | SORT_FLAG_CASE);

        return (object) [
            'taskoptions' => $taskoptions,
            'taskoptioncategories' => $taskoptioncategories,
            'taskcategoryoptions' => $taskcategoryoptions,
            'taskcategorydepartments' => $taskcategorydepartments,
        ];
    }

    /**
     * Handles get_form_action_url.
     *
     * @return mixed
     */
    private static function get_form_action_url(
        bool $isajax,
        bool $ismodaledit,
        int $id,
        string $view,
        int $perpage,
        int $page,
        int $positionfilterid,
        string $requestedpositiontype,
        bool $hassearch,
        string $searchvalue
    ): ?moodle_url {
        if (!$isajax || !$ismodaledit) {
            return null;
        }

        $params = [
            'ajax' => 1,
            'modaledit' => 1,
            'view' => $view,
            'perpage' => $perpage,
            'page' => $page,
            'positiontype' => $requestedpositiontype,
        ];
        if ($id > 0) {
            $params['id'] = $id;
        }
        if ($positionfilterid > 0) {
            $params['positionid'] = $positionfilterid;
        }
        if ($hassearch) {
            $params['search'] = $searchvalue;
        }
        return new moodle_url('/local/dutydesk/positions.php', $params);
    }

    /**
     * Handles save_form_data.
     *
     * @return mixed
     */
    private static function save_form_data(
        \stdClass $data,
        bool $isajax,
        bool $ismodaledit,
        bool $canmanageall,
        array $manageddepartmentids,
        bool $caneditpositions,
        array $manageablepositionids,
        context $context,
        moodle_url $currentpageurl
    ): void {
        $positionpayload = self::map_form_data($data);
        $record = $positionpayload->position;
        $deputyuserid = $positionpayload->deputyuserid;
        $selectedtaskids = $positionpayload->taskids;

        if (!$canmanageall && !in_array($record->departmentid, $manageddepartmentids, true)) {
            throw new required_capability_exception($context, 'local/dutydesk:managepositions', 'nopermissions', 'local_dutydesk');
        }

        if ($data->id) {
            permissions::require_manage_position(
                (int)$data->id,
                $caneditpositions,
                $canmanageall,
                $manageablepositionids,
                $context
            );
            $record->id = $data->id;
            manager::update_position($record, $deputyuserid, $selectedtaskids);
            if ($isajax && $ismodaledit) {
                self::close_modal_and_exit();
            }
            $redirecturl = new moodle_url($currentpageurl);
            $redirecturl->param('focus', $record->id);
            redirect($redirecturl, get_string('updated', 'local_dutydesk'));
        }

        $positionid = manager::create_position($record, $deputyuserid, $selectedtaskids);
        if ($isajax && $ismodaledit) {
            self::close_modal_and_exit();
        }
        $redirecturl = new moodle_url($currentpageurl);
        $redirecturl->param('focus', $positionid);
        redirect($redirecturl, get_string('saved', 'local_dutydesk'));
    }

    /**
     * Handles map_form_data.
     *
     * @return mixed
     */
    private static function map_form_data(\stdClass $data): \stdClass {
        $position = (object) [
            'title' => $data->title,
            'description' => $data->description,
            'departmentid' => (int)$data->departmentid,
            'positiontype' => \local_dutydesk_normalize_position_type($data->positiontype ?? null),
            'timestamp' => time(),
        ];

        $position->isvacant = $position->positiontype === LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA
            ? 0
            : (!empty($data->isvacant) ? 1 : 0);

        $primaryuserid = !empty($data->primaryuserid) ? (int)$data->primaryuserid : 0;
        $deputyuserid = !empty($data->deputyuserid) ? (int)$data->deputyuserid : 0;

        if ($position->positiontype === LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA) {
            $primaryuserid = 0;
            $deputyuserid = 0;
        }

        if ($primaryuserid > 0 && $primaryuserid === $deputyuserid) {
            $deputyuserid = 0;
        }

        $position->primaryuserid = $primaryuserid > 0 ? $primaryuserid : null;

        $taskids = [];
        if (!empty($data->taskids)) {
            $taskids = array_values(array_unique(array_map('intval', (array)$data->taskids)));
        }

        return (object) [
            'position' => $position,
            'deputyuserid' => $deputyuserid,
            'taskids' => $taskids,
        ];
    }

    /**
     * Handles close_modal_and_exit.
     *
     * @return mixed
     */
    private static function close_modal_and_exit(): void {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><body><script>'
            . 'if(window.parent&&window.parent!==window){'
            . 'window.parent.postMessage({type:"local_dutydesk_close_modal"}, window.location.origin);'
            . '}'
            . '</script></body></html>';
        die;
    }
}
