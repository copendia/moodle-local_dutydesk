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

$taskid = required_param('taskid', PARAM_INT);
$ismodal = optional_param('modal', 0, PARAM_BOOL);

$context = context_system::instance();
$PAGE->set_context($context);
$pageparams = ['taskid' => $taskid];
if ($ismodal) {
    $pageparams['modal'] = 1;
}
$PAGE->set_url(new moodle_url('/local/dutydesk/task_modal.php', $pageparams));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('tasks', 'local_dutydesk'));
$PAGE->set_heading(get_string('tasks', 'local_dutydesk'));
$PAGE->requires->css('/local/dutydesk/styles.css');
$PAGE->requires->js_call_amd('local_dutydesk/subtasks_toggle', 'init');
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
$PAGE->add_body_class('local-dutydesk-hide-required-note');

$canmanagealltasks = has_capability('local/dutydesk:manageall', $context);

if (
    !has_any_capability(['local/dutydesk:manageall', 'local/dutydesk:manageown'], $context)
    && !local_dutydesk_user_can_manage_departments($USER->id)
) {
    throw new required_capability_exception(
        $context,
        'local/dutydesk:manageown',
        'nopermissions',
        get_string('tasks', 'local_dutydesk')
    );
}

if (!$canmanagealltasks && !local_dutydesk_user_can_edit_task($taskid)) {
    throw new required_capability_exception(
        $context,
        'local/dutydesk:manageown',
        'nopermissions',
        get_string('tasks', 'local_dutydesk')
    );
}

$task = $DB->get_record('local_dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);

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

$currentassignmentinfo = $DB->get_record_sql(
    "SELECT ta.positionid, p.departmentid
       FROM {local_dutydesk_taskassign} ta
       JOIN {local_dutydesk_position} p ON p.id = ta.positionid
      WHERE ta.taskid = :taskid",
    ['taskid' => $taskid]
);
$currentassignmentdepartmentid = ($currentassignmentinfo && !empty($currentassignmentinfo->departmentid))
    ? (int)$currentassignmentinfo->departmentid
    : 0;

$canmanageworkloadfield = $canmanagealltasks;
if (!$canmanageworkloadfield) {
    if ($currentassignmentdepartmentid > 0) {
        $canmanageworkloadfield = local_dutydesk_user_manages_department($currentassignmentdepartmentid, $USER->id);
    } else {
        $manageddepartmentids = local_dutydesk_get_managed_department_ids($USER->id);
        $canmanageworkloadfield = !empty($manageddepartmentids);
    }
}

$formaction = new moodle_url('/local/dutydesk/task_modal.php', ['taskid' => $taskid]);
$form = new \local_dutydesk\form\task_form($formaction->out(false), [
    'context' => $context,
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'userid' => $USER->id,
    'canmanageall' => $canmanagealltasks,
    'canmanageworkload' => $canmanageworkloadfield,
]);

if ($form->is_cancelled()) {
    if ($ismodal) {
        local_dutydesk_close_modal_and_exit();
    }
    redirect(new moodle_url('/local/dutydesk/task_modal.php', $pageparams));
}

$message = '';
if ($data = $form->get_data()) {
    if ((int)$data->id !== $taskid) {
        throw new moodle_exception('invalidrecord', 'error');
    }

    if (!$canmanagealltasks && !local_dutydesk_user_can_edit_task($taskid)) {
        throw new required_capability_exception(
            $context,
            'local/dutydesk:manageown',
            'nopermissions',
            get_string('tasks', 'local_dutydesk')
        );
    }

    $record = new stdClass();
    $record->id = $taskid;
    $record->title = $data->title;
    $record->timestamp = time();
    $positionid = isset($data->positionid) ? (int)$data->positionid : 0;

    $submittedworkload = property_exists($data, 'workloadpercent') ? $data->workloadpercent : null;
    if ($submittedworkload === '' || $submittedworkload === null) {
        $submittedworkload = null;
    }
    $caneditworkload = local_dutydesk_user_can_edit_workload($positionid, $USER->id);
    $normalizedworkload = $caneditworkload
        ? local_dutydesk_normalize_workload_value($submittedworkload)
        : null;

    $existingtask = $DB->get_record('local_dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);
    $beforedocsnapshot = local_dutydesk_get_task_document_snapshot($context->id, $taskid);
    $canedittitle = local_dutydesk_user_can_manage_departments($USER->id) || $canmanagealltasks;
    if (!$canedittitle) {
        $record->title = $existingtask->title;
    }

    $data = file_postupdate_standard_editor(
        $data,
        'description',
        $editoroptions,
        $context,
        'local_dutydesk',
        'taskdescription',
        $record->id
    );

    $record->description = $data->description;
    $record->descriptionformat = $data->descriptionformat;
    $DB->update_record('local_dutydesk_task', $record);

    file_save_draft_area_files(
        $data->documents_filemanager ?? 0,
        $context->id,
        'local_dutydesk',
        'taskdocuments',
        $record->id,
        $filemanageroptions
    );

    local_dutydesk_save_task_assignment($record->id, $positionid, $normalizedworkload, $caneditworkload);

    $changedetails = [];
    if ($existingtask->title !== $record->title) {
        $changedetails[] = get_string('taskhistory_detail_title', 'local_dutydesk', (object)[
            'old' => format_string($existingtask->title ?? ''),
            'new' => format_string($record->title ?? ''),
        ]);
    }

    $existingdescription = trim((string)($existingtask->description ?? ''));
    $newdescription = trim((string)($record->description ?? ''));
    if ($existingdescription !== $newdescription) {
        $changedetails[] = get_string('taskhistory_detail_description', 'local_dutydesk');
    }
    if (!empty($changedetails)) {
        local_dutydesk_log_task_history($record->id, 'updated', implode("\n", $changedetails));
    }

    $afterdocsnapshot = local_dutydesk_get_task_document_snapshot($context->id, $record->id);
    $docdetails = local_dutydesk_describe_document_changes($beforedocsnapshot, $afterdocsnapshot);
    if ($docdetails !== '') {
        local_dutydesk_log_task_history($record->id, 'documents', $docdetails);
    }

    $message = get_string('updated', 'local_dutydesk');
    $task = $DB->get_record('local_dutydesk_task', ['id' => $taskid], '*', MUST_EXIST);
    if ($ismodal) {
        local_dutydesk_close_modal_and_exit();
    }
}

$taskrecord = $task;
$assignment = $DB->get_record('local_dutydesk_taskassign', ['taskid' => $taskid]);
if ($assignment) {
    $taskrecord->positionid = $assignment->positionid;
    if (isset($assignment->workloadpercent)) {
        $taskrecord->workloadpercent = $assignment->workloadpercent;
    }
}
if (!isset($taskrecord->workloadpercent)) {
    $taskrecord->workloadpercent = '';
}

$taskrecord = file_prepare_standard_editor(
    $taskrecord,
    'description',
    $editoroptions,
    $context,
    'local_dutydesk',
    'taskdescription',
    $taskrecord->id
);
$taskrecord = file_prepare_standard_filemanager(
    $taskrecord,
    'documents',
    $filemanageroptions,
    $context,
    'local_dutydesk',
    'taskdocuments',
    $taskrecord->id
);
$form->set_data($taskrecord);

ob_start();
$form->display();
$taskformhtml = ob_get_clean();

$subtasks = [];
$fs = get_file_storage();
$subtaskrecords = $DB->get_records(
    'local_dutydesk_subtask',
    ['taskid' => $taskid],
    'sortorder ASC, id ASC',
    'id, title, description, descriptionformat'
);
foreach ($subtaskrecords as $subtaskrecord) {
    $descriptionfromdb = isset($subtaskrecord->description) ? (string)$subtaskrecord->description : '';
    $descriptionformat = property_exists($subtaskrecord, 'descriptionformat')
        ? (int)$subtaskrecord->descriptionformat
        : FORMAT_HTML;
    $descriptionwithfiles = $descriptionfromdb !== ''
        ? file_rewrite_pluginfile_urls(
            $descriptionfromdb,
            'pluginfile.php',
            $context->id,
            'local_dutydesk',
            'subtaskdescription',
            $subtaskrecord->id
        )
        : '';
    $descriptionrendered = '';
    if (trim($descriptionwithfiles) !== '') {
        $descriptionrendered = format_text(
            $descriptionwithfiles,
            $descriptionformat,
            ['context' => $context]
        );
    }

    $documents = [];
    $files = $fs->get_area_files(
        $context->id,
        'local_dutydesk',
        'subtaskdocuments',
        $subtaskrecord->id,
        'filename',
        false
    );
    if (!empty($files)) {
        foreach ($files as $file) {
            $filepath = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                true
            );
            $documents[] = [
                'name' => $file->get_filename(),
                'url' => $filepath->out(false),
                'mimetype' => $file->get_mimetype(),
                'size' => display_size($file->get_filesize()),
            ];
        }
    }

    $subtasks[] = [
        'id' => $subtaskrecord->id,
        'title' => format_string($subtaskrecord->title),
        'description' => $descriptionrendered !== '' ? $descriptionrendered : null,
        'hasdocuments' => !empty($documents),
        'documents' => $documents,
        'editurl' => (new moodle_url('/local/dutydesk/subtask.php', [
            'taskid' => $taskid,
            'id' => $subtaskrecord->id,
            'modal' => 1,
        ]))->out(false),
    ];
}

$newsubtaskurl = (new moodle_url('/local/dutydesk/subtask.php', [
    'taskid' => $taskid,
    'modal' => 1,
]))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dutydesk/task_modal_editor', [
    'tasktitle' => format_string($task->title),
    'taskformhtml' => $taskformhtml,
    'hasmessage' => $message !== '',
    'message' => $message,
    'hassubtasks' => !empty($subtasks),
    'subtasks' => $subtasks,
    'newsubtaskurl' => $newsubtaskurl,
]);
echo $OUTPUT->footer();
