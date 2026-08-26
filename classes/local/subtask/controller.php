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

use context_system;
use moodle_exception;
use moodle_url;

/**
 * Controller for subtask create, edit and delete requests.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Execute the subtask page request.
     *
     * @return void
     */
    public static function execute(): void {
        global $CFG, $PAGE, $USER;

        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/repository/lib.php');
        require_once(dirname(__DIR__, 3) . '/lib.php');

        require_login();

        $request = self::get_request_data();
        $context = context_system::instance();
        $PAGE->set_context($context);

        $canmanageall = has_capability('local/dutydesk:manageall', $context);
        permissions::require_page_access($context, (int)$USER->id);

        $subtask = repository::get_subtask($request['id']);
        if ($subtask) {
            $request['taskid'] = (int)$subtask->taskid;
        }
        if (empty($request['taskid'])) {
            throw new moodle_exception('missingparam', 'error', '', 'taskid');
        }

        $task = repository::get_task($request['taskid']);
        permissions::require_edit_task_subtasks($context, $request['taskid'], $canmanageall);

        self::setup_page($task, $request);

        $editoroptions = self::get_editor_options($context, $CFG->maxbytes);
        $filemanageroptions = self::get_filemanager_options($context, $CFG->maxbytes);

        if ($request['ispost'] && $request['delete'] && $subtask && confirm_sesskey()) {
            permissions::require_delete_subtask($context);
            manager::delete_subtask($subtask, $context);
            self::redirect_after_change($request, get_string('subtaskdeleted', 'local_dutydesk'));
        }

        $formaction = new moodle_url('/local/dutydesk/subtask.php', self::get_page_params($request));
        $form = form_handler::create_form(
            $formaction,
            $request['taskid'],
            $context,
            $editoroptions,
            $filemanageroptions
        );

        if ($form->is_cancelled()) {
            self::redirect_after_change($request);
        }

        if ($data = $form->get_data()) {
            $result = manager::save_subtask(
                $data,
                $subtask,
                $request['taskid'],
                $context,
                $editoroptions,
                $filemanageroptions
            );
            self::redirect_after_change($request, $result['message']);
        }

        form_handler::set_existing_data($form, $subtask, $context, $editoroptions, $filemanageroptions);
        presenter::render($form, $task, $subtask, $context, $canmanageall, $request);
    }

    /**
     * Collect request data.
     *
     * @return array
     */
    private static function get_request_data(): array {
        $perpage = optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT);

        return [
            'id' => optional_param('id', 0, PARAM_INT),
            'taskid' => optional_param('taskid', 0, PARAM_INT),
            'delete' => optional_param('delete', 0, PARAM_BOOL),
            'page' => max(0, optional_param('page', 0, PARAM_INT)),
            'perpage' => \local_dutydesk_normalize_perpage($perpage),
            'focus' => optional_param('focus', 0, PARAM_INT),
            'modal' => optional_param('modal', 0, PARAM_BOOL),
            'ispost' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST',
        ];
    }

    /**
     * Configure the Moodle page.
     *
     * @param \stdClass $task
     * @param array $request
     * @return void
     */
    private static function setup_page(\stdClass $task, array $request): void {
        global $PAGE;

        $PAGE->set_url(new moodle_url('/local/dutydesk/subtask.php', self::get_page_params($request)));
        $PAGE->set_title(get_string('subtasks', 'local_dutydesk'));
        $PAGE->set_heading(format_string($task->title));
        $PAGE->add_body_class('limitedwidth');
        $PAGE->set_pagelayout(!empty($request['modal']) ? 'embedded' : 'standard');
        if (!empty($request['modal'])) {
            $PAGE->requires->js_call_amd('local_dutydesk/task_modal', 'initEmbedded');
        }
    }

    /**
     * Build page params for links and form action.
     *
     * @param array $request
     * @return array
     */
    private static function get_page_params(array $request): array {
        $params = [
            'taskid' => (int)$request['taskid'],
            'page' => (int)$request['page'],
            'perpage' => (int)$request['perpage'],
            'modal' => !empty($request['modal']) ? 1 : 0,
        ];
        if (!empty($request['id'])) {
            $params['id'] = (int)$request['id'];
        }
        if (!empty($request['focus'])) {
            $params['focus'] = (int)$request['focus'];
        }

        return $params;
    }

    /**
     * Build editor options for subtask descriptions.
     *
     * @param \context $context
     * @param int $maxbytes
     * @return array
     */
    private static function get_editor_options(\context $context, int $maxbytes): array {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'context' => $context,
            'subdirs' => 0,
            'return_types' => FILE_INTERNAL,
        ];
    }

    /**
     * Build filemanager options for subtask documents.
     *
     * @param \context $context
     * @param int $maxbytes
     * @return array
     */
    private static function get_filemanager_options(\context $context, int $maxbytes): array {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'context' => $context,
            'subdirs' => 0,
            'return_types' => FILE_INTERNAL | FILE_CONTROLLED_LINK | FILE_REFERENCE,
        ];
    }

    /**
     * Redirect after save, delete or cancel.
     *
     * @param array $request
     * @param string $message
     * @return void
     */
    private static function redirect_after_change(array $request, string $message = ''): void {
        if (!empty($request['modal'])) {
            presenter::close_modal_and_exit();
        }

        $redirecturl = presenter::get_task_return_url($request);
        redirect($redirecturl, $message);
    }
}
