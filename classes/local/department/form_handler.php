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

use context;
use moodle_url;
use required_capability_exception;

/**
 * Handles department form display and submission.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_handler {
    /**
     * Process department form state and return rendered HTML.
     *
     * @param int $id
     * @param bool $isajax
     * @param bool $ismodaledit
     * @param bool $canmanageall
     * @param bool $canassignmanagers
     * @param int[] $manageddepartmentids
     * @param context $context
     * @param moodle_url $currentpageurl
     * @param bool $showform
     * @return string
     */
    public static function process(
        int $id,
        bool $isajax,
        bool $ismodaledit,
        bool $canmanageall,
        bool $canassignmanagers,
        array &$manageddepartmentids,
        context $context,
        moodle_url $currentpageurl,
        bool &$showform
    ): string {
        global $DB, $USER;

        $categoryoptions = manager::get_category_options($id);
        $form = new \local_dutydesk\form\department_form(
            self::get_form_action_url($id, $isajax, $ismodaledit),
            [
            'canassignmanagers' => $canassignmanagers,
            'categoryoptions' => $categoryoptions,
            ]
        );

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
                $canassignmanagers,
                $manageddepartmentids,
                $context,
                $currentpageurl
            );
        } else if ($id) {
            self::require_manage_department($id, $canmanageall, $manageddepartmentids, $context);

            $record = $DB->get_record('local_dutydesk_department', ['id' => $id], '*', MUST_EXIST);
            if ($canassignmanagers) {
                $managerids = $DB->get_fieldset_select(
                    'local_dutydesk_deptmgr',
                    'userid',
                    'departmentid = ?',
                    [$record->id]
                ) ?? [];
                $record->managerids = !empty($managerids) ? (int)reset($managerids) : 0;
            }
            $record->categoryids = $DB->get_fieldset_select(
                'local_dutydesk_category',
                'id',
                'departmentid = ?',
                [$record->id]
            ) ?? [];
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
     * Build the form action for modal form submissions.
     *
     * @param int $id
     * @param bool $isajax
     * @param bool $ismodaledit
     * @return moodle_url|null
     */
    private static function get_form_action_url(int $id, bool $isajax, bool $ismodaledit): ?moodle_url {
        if (!$isajax || !$ismodaledit) {
            return null;
        }

        $params = [
            'ajax' => 1,
            'modaledit' => 1,
            'showform' => 1,
        ];
        if ($id > 0) {
            $params['id'] = $id;
        }
        return new moodle_url('/local/dutydesk/departments.php', $params);
    }

    /**
     * Save submitted department form data.
     *
     * @param \stdClass $data
     * @param bool $isajax
     * @param bool $ismodaledit
     * @param bool $canmanageall
     * @param bool $canassignmanagers
     * @param int[] $manageddepartmentids
     * @param context $context
     * @param moodle_url $currentpageurl
     * @return void
     */
    private static function save_form_data(
        \stdClass $data,
        bool $isajax,
        bool $ismodaledit,
        bool $canmanageall,
        bool $canassignmanagers,
        array &$manageddepartmentids,
        context $context,
        moodle_url $currentpageurl
    ): void {
        global $USER;

        $record = (object) [
            'name' => $data->name,
            'description' => $data->description,
            'timestamp' => time(),
        ];

        if (!empty($data->id)) {
            self::require_manage_department((int)$data->id, $canmanageall, $manageddepartmentids, $context);

            $record->id = (int)$data->id;
            $managerids = null;
            if ($canassignmanagers) {
                $managerid = (int)($data->managerids ?? 0);
                $managerids = $managerid > 0 ? [$managerid] : [];
            }
            manager::update_department($record, $managerids, (array)($data->categoryids ?? []), $USER->id);
            if ($isajax && $ismodaledit) {
                self::close_modal_and_exit();
            }

            $redirecturl = new moodle_url($currentpageurl);
            $redirecturl->param('focus', $record->id);
            redirect($redirecturl, get_string('updated', 'local_dutydesk'));
        }

        if ($canassignmanagers) {
            $managerid = (int)($data->managerids ?? 0);
            $managerids = $managerid > 0 ? [$managerid] : [];
        } else {
            $managerids = [$USER->id];
        }

        $departmentid = manager::create_department($record, $managerids, (array)($data->categoryids ?? []), $USER->id);
        if (!$canassignmanagers) {
            $manageddepartmentids[] = $departmentid;
        }
        if ($isajax && $ismodaledit) {
            self::close_modal_and_exit();
        }

        $redirecturl = new moodle_url($currentpageurl);
        $redirecturl->param('focus', $departmentid);
        redirect($redirecturl, get_string('saved', 'local_dutydesk'));
    }

    /**
     * Require permission to manage a department.
     *
     * @param int $departmentid
     * @param bool $canmanageall
     * @param int[] $manageddepartmentids
     * @param context $context
     * @return void
     */
    private static function require_manage_department(
        int $departmentid,
        bool $canmanageall,
        array $manageddepartmentids,
        context $context
    ): void {
        if (!$canmanageall && !in_array($departmentid, $manageddepartmentids, true)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:managepositions',
                'nopermissions',
                'local_dutydesk'
            );
        }
    }

    /**
     * Notify the parent page to close the modal and stop rendering.
     *
     * @return void
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
