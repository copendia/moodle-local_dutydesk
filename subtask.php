<?php
// This file is part of Moodle - https://moodle.org/
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

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/lib.php');

require_login();

if (!function_exists('local_dutydesk_close_modal_and_exit')) {
    /**
     * Close parent modal from an embedded iframe and stop processing.
     *
     * @return void
     */
    function local_dutydesk_close_modal_and_exit(): void {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><body><script>'
            . 'if(window.parent&&window.parent!==window){'
            . 'window.parent.postMessage({type:"local_dutydesk_close_modal"}, window.location.origin);'
            . '}'
            . '</script></body></html>';
        die;
    }
}

$id = optional_param('id', 0, PARAM_INT);
$taskid = optional_param('taskid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$returnpage = max(0, optional_param('page', 0, PARAM_INT));
$returnperpage = optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT);
$returnperpage = local_dutydesk_normalize_perpage($returnperpage);
$returnfocus = optional_param('focus', 0, PARAM_INT);
$ismodal = optional_param('modal', 0, PARAM_BOOL);
$ispost = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

$context = context_system::instance();
$PAGE->set_context($context);
$canmanageall = has_capability('local/dutydesk:manageall', $context);

if (
    !has_any_capability(['local/dutydesk:manageall', 'local/dutydesk:manageown'], $context)
    && !local_dutydesk_user_can_manage_departments($USER->id)
) {
    throw new required_capability_exception(
        $context,
        'local/dutydesk:manageown',
        'nopermissions',
        get_string('subtasks', 'local_dutydesk')
    );
}

if ($id) {
    $subtask = $DB->get_record('local_dutydesk_subtask', ['id' => $id], '*', MUST_EXIST);
    $taskid = $subtask->taskid;
} else {
    $subtask = null;
}

if (!$taskid) {
    throw new moodle_exception('missingparam', 'error', '', 'taskid');
}

$task = $DB->get_record('local_dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);

if (!$canmanageall && !local_dutydesk_user_can_edit_task($taskid)) {
    throw new required_capability_exception(
        $context,
        'local/dutydesk:manageown',
        'nopermissions',
        get_string('subtasks', 'local_dutydesk')
    );
}

$pageparams = ['taskid' => $taskid];
if ($id) {
    $pageparams['id'] = $id;
}
$pageparams['page'] = $returnpage;
$pageparams['perpage'] = $returnperpage;
if ($returnfocus > 0) {
    $pageparams['focus'] = $returnfocus;
}
$pageparams['modal'] = $ismodal;
$PAGE->add_body_class('limitedwidth');
$PAGE->set_url(new moodle_url('/local/dutydesk/subtask.php', $pageparams));
$PAGE->set_title(get_string('subtasks', 'local_dutydesk'));
$PAGE->set_heading(format_string($task->title));
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagelayout($ismodal ? 'embedded' : 'standard');
if ($ismodal) {
    $PAGE->requires->js_init_code(<<<'JS'
(function() {
    if (window.top === window || !window.parent) {
        return;
    }
    var closeParentModal = function() {
        try {
            window.parent.postMessage({type: 'local_dutydesk_close_modal'}, window.location.origin);
        } catch (e) {
            // Ignore cross-window errors.
        }
    };

    document.addEventListener('click', function(event) {
        var trigger = event.target.closest('button[name="cancel"], input[name="cancel"]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        closeParentModal();
    });

    document.addEventListener('submit', function(event) {
        var submitter = event.submitter || document.activeElement;
        if (!submitter || submitter.name !== 'cancel') {
            return;
        }
        event.preventDefault();
        closeParentModal();
    }, true);
})();
JS
    );
}

$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'context' => $context,
    'subdirs' => 0,
    'return_types' => FILE_INTERNAL,
];
$filemanageroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'context' => $context,
    'subdirs' => 0,
    'return_types' => FILE_INTERNAL | FILE_CONTROLLED_LINK | FILE_REFERENCE,
];

if ($ispost && $delete && $id && confirm_sesskey()) {
    if (!$canmanageall) {
        throw new required_capability_exception($context, 'local/dutydesk:manageall', 'nopermissions', 'local_dutydesk');
    }
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_dutydesk', 'subtaskdescription', $id);
    $fs->delete_area_files($context->id, 'local_dutydesk', 'subtaskdocuments', $id);
    if (isset($subtask->taskid)) {
        $detail = get_string('taskhistory_detail_subtask_reference', 'local_dutydesk', format_string($subtask->title ?? ''));
        local_dutydesk_log_task_history((int)$subtask->taskid, 'subtask_deleted', $detail);
    }
    $DB->delete_records('local_dutydesk_subtask', ['id' => $id]);
    if ($ismodal) {
        local_dutydesk_close_modal_and_exit();
    } else {
        $redirecturl = new moodle_url('/local/dutydesk/tasks.php', [
            'page' => 0,
            'perpage' => $returnperpage,
            'focus' => $returnfocus > 0 ? $returnfocus : $taskid,
            'forcefirst' => 1,
        ]);
        $redirecturl->set_anchor('task-' . $taskid);
    }
    redirect($redirecturl, get_string('subtaskdeleted', 'local_dutydesk'));
}

$formaction = new moodle_url('/local/dutydesk/subtask.php', $pageparams);
$form = new \local_dutydesk\form\subtask_form($formaction->out(false), [
    'taskid' => $taskid,
    'context' => $context,
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
]);

if ($form->is_cancelled()) {
    if ($ismodal) {
        local_dutydesk_close_modal_and_exit();
    } else {
        $redirecturl = new moodle_url('/local/dutydesk/tasks.php', [
            'page' => 0,
            'perpage' => $returnperpage,
            'focus' => $returnfocus > 0 ? $returnfocus : $taskid,
            'forcefirst' => 1,
        ]);
        $redirecturl->set_anchor('task-' . $taskid);
    }
    redirect($redirecturl);
}

if ($data = $form->get_data()) {
    $record = new stdClass();
    $record->taskid = $taskid;
    $record->title = $data->title;
    $data->description_editor = $data->description_editor ?? ['text' => '', 'format' => FORMAT_HTML];
    $existingdocuments = [];
    if (!empty($data->id)) {
        $existingdocuments = local_dutydesk_get_subtask_document_snapshot($context->id, (int)$data->id);
    }

    if (!empty($data->id)) {
        $record->id = $data->id;
        if ($subtask && property_exists($subtask, 'sortorder')) {
            $record->sortorder = $subtask->sortorder;
        }
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $context,
            'local_dutydesk',
            'subtaskdescription',
            $record->id
        );
        $record->description = $data->description;
        $record->descriptionformat = $data->descriptionformat;
        $DB->update_record('local_dutydesk_subtask', $record);
        file_save_draft_area_files(
            $data->documents_filemanager ?? 0,
            $context->id,
            'local_dutydesk',
            'subtaskdocuments',
            $record->id,
            $filemanageroptions
        );
        $message = get_string('subtaskupdated', 'local_dutydesk');
        $changes = [];
        if ($subtask && trim((string)$subtask->title) !== trim((string)$record->title)) {
            $changes[] = get_string('taskhistory_detail_subtask_title', 'local_dutydesk', (object)[
                'old' => format_string($subtask->title ?? ''),
                'new' => format_string($record->title ?? ''),
            ]);
        }
        $olddescription = trim((string)($subtask->description ?? ''));
        $newdescription = trim((string)($record->description ?? ''));
        if ($subtask && $olddescription !== $newdescription) {
            $changes[] = get_string('taskhistory_detail_subtask_description', 'local_dutydesk');
        }
        $afterdocs = local_dutydesk_get_subtask_document_snapshot($context->id, $record->id);
        $docdetails = local_dutydesk_describe_document_changes($existingdocuments, $afterdocs);
        if ($docdetails !== '') {
            $changes[] = $docdetails;
        }
        if (!empty($changes)) {
            local_dutydesk_log_task_history($taskid, 'subtask_updated', implode("\n", $changes));
        }
    } else {
        $maxorder = (int)$DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {local_dutydesk_subtask} WHERE taskid = :taskid',
            ['taskid' => $taskid]
        );
        $record->sortorder = $maxorder + 1;
        $record->description = $data->description_editor['text'];
        $record->descriptionformat = $data->description_editor['format'];
        $subtaskid = $DB->insert_record('local_dutydesk_subtask', $record);
        $data->id = $subtaskid;
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $context,
            'local_dutydesk',
            'subtaskdescription',
            $subtaskid
        );
        $DB->update_record('local_dutydesk_subtask', [
            'id' => $subtaskid,
            'description' => $data->description,
            'descriptionformat' => $data->descriptionformat,
        ]);
        file_save_draft_area_files(
            $data->documents_filemanager ?? 0,
            $context->id,
            'local_dutydesk',
            'subtaskdocuments',
            $subtaskid,
            $filemanageroptions
        );
        $message = get_string('subtaskadded', 'local_dutydesk');
        $details = [get_string('taskhistory_detail_subtask_reference', 'local_dutydesk', format_string($record->title ?? ''))];
        $afterdocs = local_dutydesk_get_subtask_document_snapshot($context->id, $subtaskid);
        $docdetails = local_dutydesk_describe_document_changes([], $afterdocs);
        if ($docdetails !== '') {
            $details[] = $docdetails;
        }
        local_dutydesk_log_task_history($taskid, 'subtask_created', implode("\n", $details));
    }

    if ($ismodal) {
        local_dutydesk_close_modal_and_exit();
    } else {
        $redirecturl = new moodle_url('/local/dutydesk/tasks.php', [
            'page' => 0,
            'perpage' => $returnperpage,
            'focus' => $returnfocus > 0 ? $returnfocus : $taskid,
            'forcefirst' => 1,
        ]);
        $redirecturl->set_anchor('task-' . $taskid);
    }
    redirect($redirecturl, $message);
}

if ($subtask) {
    if (!isset($subtask->description)) {
        $subtask->description = '';
    }
    if (!isset($subtask->descriptionformat)) {
        $subtask->descriptionformat = FORMAT_HTML;
    }
    $subtask = file_prepare_standard_editor(
        $subtask,
        'description',
        $editoroptions,
        $context,
        'local_dutydesk',
        'subtaskdescription',
        $subtask->id
    );
    $subtask = file_prepare_standard_filemanager(
        $subtask,
        'documents',
        $filemanageroptions,
        $context,
        'local_dutydesk',
        'subtaskdocuments',
        $subtask->id
    );
    $form->set_data($subtask);
}

echo $OUTPUT->header();
if (!$ismodal) {
    echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
        'istasks' => true,
        'showdepartments' => local_dutydesk_show_departments_tab(),
        'showpositionsarchived' => local_dutydesk_show_archived_positions_tab(),
    ]);
    $backurl = new moodle_url('/local/dutydesk/tasks.php', [
        'page' => 0,
        'perpage' => $returnperpage,
        'focus' => $returnfocus > 0 ? $returnfocus : $taskid,
        'forcefirst' => 1,
    ]);
    $backurl->set_anchor('task-' . $taskid);
    $closelabel = get_string('returntotask', 'local_dutydesk');
    echo html_writer::div(
        html_writer::link($backurl, $closelabel, [
            'class' => 'btn btn-outline-secondary btn-sm float-end d-inline-flex align-items-center gap-1',
            'title' => $closelabel,
        ]),
        'clearfix mb-2 text-end'
    );
    echo $OUTPUT->heading(format_string($task->title));
    $form->display();
} else {
    ob_start();
    $form->display();
    $subtaskformhtml = ob_get_clean();

    echo html_writer::start_div('local-dutydesk-task-modal-editor');
    echo html_writer::start_div('local-dutydesk-task-modal-card');
    echo html_writer::start_div('local-dutydesk-task-modal-section local-dutydesk-task-modal-form');
    echo $subtaskformhtml;
    echo html_writer::end_div();

    if ($subtask && $canmanageall) {
        $deleteurl = new moodle_url('/local/dutydesk/subtask.php', [
            'id' => $subtask->id,
            'taskid' => $taskid,
            'delete' => 1,
            'modal' => 1,
        ]);
        echo html_writer::start_div('local-dutydesk-task-modal-section');
        echo html_writer::tag(
            'form',
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::tag('button', get_string('delete'), [
                'type' => 'submit',
                'class' => 'btn btn-outline-danger btn-sm',
            ]),
            [
                'method' => 'post',
                'action' => $deleteurl->out(false),
                'onsubmit' => "return confirm('" . get_string('confirmdelete', 'local_dutydesk') . "');",
                'class' => 'd-inline',
            ]
        );
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
}
if (!$ismodal) {
    $taskpreview = local_dutydesk_build_task_display($task, $context, true, $canmanageall, $returnpage, $returnperpage);
    if ($taskpreview) {
        echo html_writer::div(
            $OUTPUT->render_from_template('local_dutydesk/task_list', [
                'displaysearch' => false,
                'tasks' => [$taskpreview],
            ]),
            'local-dutydesk-task-edit-preview mt-4'
        );
    }
}

if ($subtask && !$ismodal && $canmanageall) {
    $deleteurl = new moodle_url('/local/dutydesk/subtask.php', [
        'id' => $subtask->id,
        'taskid' => $taskid,
        'delete' => 1,
        'modal' => $ismodal ? 1 : 0,
    ]);
    echo html_writer::div(
        html_writer::tag(
            'form',
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::tag('button', get_string('delete'), [
                'type' => 'submit',
                'class' => 'btn btn-outline-danger btn-sm',
            ]),
            [
                'method' => 'post',
                'action' => $deleteurl->out(false),
                'onsubmit' => "return confirm('" . get_string('confirmdelete', 'local_dutydesk') . "');",
                'class' => 'd-inline',
            ]
        ),
        'mt-3'
    );
}

echo $OUTPUT->footer();
// Helper to build combined task/subtask display reused from tasks page.
