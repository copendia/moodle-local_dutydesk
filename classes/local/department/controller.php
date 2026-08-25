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

/**
 * DutyDesk local plugin.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dutydesk\local\department;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/lib.php');

use context;
use local_dutydesk\output\department_page;
use moodle_url;
use required_capability_exception;

/**
 * Controller for the departments page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Execute the departments page.
     *
     * @return void
     */
    public static function execute(): void {
        global $OUTPUT, $PAGE, $USER;

        $context = \context_system::instance();
        self::setup_page($context);

        $id = optional_param('id', 0, PARAM_INT);
        $delete = optional_param('delete', 0, PARAM_BOOL);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $perpage = \local_dutydesk_normalize_perpage(optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT));
        $focus = optional_param('focus', 0, PARAM_INT);
        $showform = optional_param('showform', 0, PARAM_BOOL);
        $isajax = optional_param('ajax', 0, PARAM_BOOL);
        $ismodaledit = optional_param('modaledit', 0, PARAM_BOOL);
        $ispost = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        $canmanageall = has_capability('local/dutydesk:manageall', $context);
        $canassignmanagers = $canmanageall;
        $manageddepartmentids = \local_dutydesk_get_managed_department_ids($USER->id);
        if (!$canmanageall && !has_capability('local/dutydesk:managepositions', $context) && empty($manageddepartmentids)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:managepositions',
                'nopermissions',
                'local_dutydesk'
            );
        }

        if ($focus > 0) {
            $focuspage = \local_dutydesk_calculate_department_page($focus, $perpage, $canmanageall, $manageddepartmentids);
            if ($focuspage !== null) {
                $page = $focuspage;
            }
        }

        $listbaseurl = new moodle_url('/local/dutydesk/departments.php', ['perpage' => $perpage]);
        $currentpageurl = new moodle_url('/local/dutydesk/departments.php', [
            'page' => $page,
            'perpage' => $perpage,
        ]);
        if ($focus > 0) {
            $currentpageurl->param('focus', $focus);
        }

        self::handle_delete($ispost, $delete, $id, $canmanageall, $context, $currentpageurl);
        if ($isajax) {
            $PAGE->set_pagelayout('embedded');
        }
        if ($isajax && $ismodaledit) {
            $PAGE->requires->js_call_amd('local_dutydesk/department_modal', 'initEmbedded');
        }

        $renderedform = '';
        $needsform = $ispost || $showform || $id > 0 || ($isajax && $ismodaledit);
        if ($needsform) {
            $renderedform = form_handler::process(
                $id,
                $isajax,
                $ismodaledit,
                $canmanageall,
                $canassignmanagers,
                $manageddepartmentids,
                $context,
                $currentpageurl,
                $showform
            );
        }

        if ($isajax && $ismodaledit) {
            department_page::render_modal_form($renderedform);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
            'isdepartments' => true,
            'showdepartments' => true,
            'showpositionsarchived' => \local_dutydesk_show_archived_positions_tab(),
        ]);

        if ($showform) {
            department_page::render_form($renderedform);
            if (empty($id)) {
                echo $OUTPUT->footer();
                die;
            }
        } else {
            department_page::render_create_button($perpage);
        }

        $departmentresult = repository::get_paginated_departments($canmanageall, $manageddepartmentids, $page, $perpage);
        $records = $departmentresult->records;
        $totaldepartments = $departmentresult->totaldepartments;
        $page = $departmentresult->page;
        $departmentsdata = [];

        if (!empty($records)) {
            echo $OUTPUT->paging_bar($totaldepartments, $page, $perpage, $listbaseurl);

            $departmentids = array_keys($records);
            $positionsbydepartment = repository::get_positions_by_department($departmentids);
            $managersbydepartment = repository::get_managers_by_department($departmentids);
            $departmentsdata = presenter::build(
                $records,
                $context,
                $positionsbydepartment,
                $managersbydepartment,
                $canmanageall,
                $manageddepartmentids
            );
        }

        echo $OUTPUT->render_from_template('local_dutydesk/department_list', [
            'departments' => $departmentsdata,
            'sesskey' => sesskey(),
        ]);
        if (!empty($records)) {
            echo $OUTPUT->paging_bar($totaldepartments, $page, $perpage, $listbaseurl);
        }

        echo $OUTPUT->footer();
    }

    /**
     * Configure the Moodle page.
     *
     * @param context $context
     * @return void
     */
    private static function setup_page(context $context): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/dutydesk/departments.php'));
        $PAGE->set_title(get_string('departments', 'local_dutydesk'));
        $PAGE->set_heading(get_string('departments', 'local_dutydesk'));
        $PAGE->add_body_class('limitedwidth');
        $PAGE->add_body_class('local-dutydesk-hide-required-note');
        $PAGE->requires->js_call_amd('local_dutydesk/department_modal', 'initParent');
    }

    /**
     * Handle department deletion.
     *
     * @param bool $ispost
     * @param bool $delete
     * @param int $id
     * @param bool $canmanageall
     * @param context $context
     * @param moodle_url $currentpageurl
     * @return void
     */
    private static function handle_delete(
        bool $ispost,
        bool $delete,
        int $id,
        bool $canmanageall,
        context $context,
        moodle_url $currentpageurl
    ): void {
        if (!$ispost || !$delete || !$id || !confirm_sesskey()) {
            return;
        }

        if (!$canmanageall) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:manageall',
                'nopermissions',
                'local_dutydesk'
            );
        }

        manager::delete_department($id);
        redirect($currentpageurl, get_string('deleted', 'local_dutydesk'));
    }
}
