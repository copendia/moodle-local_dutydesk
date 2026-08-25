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

namespace local_dutydesk\local\task;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


use context_system;
use core_text;
use html_writer;
use moodle_url;
use stdClass;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Controller for the task list and task form page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Execute the task page request.
     *
     * @return void
     */
    public static function execute(): void {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        require_login();

        $context = context_system::instance();
        \local_dutydesk\output\task_page::setup($context);

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

        $id = optional_param('id', 0, PARAM_INT);
        $delete = optional_param('delete', 0, PARAM_BOOL);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $perpage = optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT);
        $perpage = local_dutydesk_normalize_perpage($perpage);
        $focus = optional_param('focus', 0, PARAM_INT);
        $forcefirst = optional_param('forcefirst', 0, PARAM_BOOL);
        $showform = optional_param('showform', 0, PARAM_BOOL);
        $categoryid = optional_param('category', 0, PARAM_INT);
        $categoryid = $categoryid > 0 ? $categoryid : 0;
        $departmentfilterid = optional_param('departmentid', 0, PARAM_INT);
        $departmentfilterid = $departmentfilterid > 0 ? $departmentfilterid : 0;
        $vacantonly = optional_param('vacantonly', 0, PARAM_BOOL);
        $positionfilterid = optional_param('positionid', 0, PARAM_INT);
        $positionfilterid = $positionfilterid > 0 ? $positionfilterid : 0;
        $isajax = optional_param('ajax', 0, PARAM_BOOL);
        $ismodaledit = optional_param('modaledit', 0, PARAM_BOOL);
        $searchvalue = optional_param('search', '', PARAM_RAW_TRIMMED);
        $searchvalue = is_string($searchvalue) ? trim($searchvalue) : '';
        $searchnormalized = core_text::strtolower($searchvalue);
        $hassearch = $searchvalue !== '';
        $searchlikevalue = $hassearch ? '%' . $DB->sql_like_escape($searchnormalized) . '%' : '';
        $ispost = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $categoryrecords = repository::get_categories();

        if ($isajax) {
            require_sesskey();
            if ($ismodaledit) {
                $PAGE->set_pagelayout('embedded');
                $PAGE->requires->js_call_amd('local_dutydesk/task_modal', 'initEmbedded');
            }
        }

        permissions::require_view_page($context, (int)$USER->id);

        $canviewalltasks = has_any_capability(['local/dutydesk:viewall', 'local/dutydesk:manageall'], $context);
        $canmanagealltasks = has_capability('local/dutydesk:manageall', $context);
        $canmanageowntasks = has_capability('local/dutydesk:manageown', $context);
        $manageablepositionids = [];
        if (!$canviewalltasks) {
            $manageablepositionids = local_dutydesk_get_manageable_position_ids($USER->id);
        }
        $userdepartmentids = local_dutydesk_get_user_department_ids($USER->id);
        $manageddepartmentids = local_dutydesk_get_managed_department_ids($USER->id);

        if ($forcefirst) {
            $page = 0;
        }

        if ($focus > 0 && !$forcefirst) {
            $focuspage = local_dutydesk_calculate_task_page(
                $focus,
                $perpage,
                $canviewalltasks,
                $manageablepositionids,
                $userdepartmentids,
                $positionfilterid
            );
            if ($focuspage !== null) {
                $page = $focuspage;
            }
        }

        $offset = $page * $perpage;
        $baseparams = ['perpage' => $perpage];
        $currentpageparams = $baseparams + ['page' => $page];
        $firstpageparams = $baseparams + ['page' => 0];
        $filterparams = [];
        if ($categoryid > 0) {
            $filterparams['category'] = $categoryid;
        }
        if ($departmentfilterid > 0) {
            $filterparams['departmentid'] = $departmentfilterid;
        }
        if ($positionfilterid > 0) {
            $filterparams['positionid'] = $positionfilterid;
        }
        if ($vacantonly) {
            $filterparams['vacantonly'] = 1;
        }
        foreach ($filterparams as $name => $value) {
            $baseparams[$name] = $value;
            $currentpageparams[$name] = $value;
            $firstpageparams[$name] = $value;
        }
        if ($hassearch) {
            $baseparams['search'] = $searchvalue;
            $currentpageparams['search'] = $searchvalue;
            $firstpageparams['search'] = $searchvalue;
        }
        $listbaseurl = new moodle_url('/local/dutydesk/tasks.php', $baseparams);
        if ($focus > 0 && !$forcefirst) {
            $currentpageparams['focus'] = $focus;
        }
        $currentpageurl = new moodle_url('/local/dutydesk/tasks.php', $currentpageparams);

        if ($ispost && $delete && $id && confirm_sesskey()) {
            permissions::require_delete_task($context);
            \local_dutydesk\local\task\manager::delete_task($id);
            redirect($currentpageurl, get_string('deleted', 'local_dutydesk'));
        }

        permissions::require_edit_task($id, $canmanagealltasks, $context);

        $currentassignmentinfo = null;
        $currentassignmentdepartmentid = 0;
        if ($id) {
            $currentassignmentinfo = repository::get_assignment_info($id);
            if ($currentassignmentinfo && !empty($currentassignmentinfo->departmentid)) {
                $currentassignmentdepartmentid = (int)$currentassignmentinfo->departmentid;
            }
        }

        $canmanageworkloadfield = permissions::can_manage_workload_field(
            $canmanagealltasks,
            $currentassignmentdepartmentid,
            $manageddepartmentids,
            (int)$USER->id
        );

        $needsform = $ispost || $showform || $id > 0;
        $form = null;
        if ($needsform) {
            $formactionurl = null;
            if ($isajax && $ismodaledit) {
                $formactionparams = $currentpageparams + [
                    'ajax' => 1,
                    'modaledit' => 1,
                    'sesskey' => sesskey(),
                ];
                if ($showform) {
                    $formactionparams['showform'] = 1;
                }
                if ($id > 0) {
                    $formactionparams['id'] = $id;
                }
                $formactionurl = new moodle_url('/local/dutydesk/tasks.php', $formactionparams);
            }

            $form = new \local_dutydesk\form\task_form($formactionurl, [
                'context' => $context,
                'editoroptions' => $editoroptions,
                'filemanageroptions' => $filemanageroptions,
                'userid' => $USER->id,
                'canmanageall' => $canmanagealltasks,
                'canmanageworkload' => $canmanageworkloadfield,
            ]);
        }

        if ($form && $form->is_cancelled()) {
            if ($isajax && $ismodaledit) {
                self::close_modal_and_exit();
            }
            $redirectparams = $firstpageparams;
            if ($id) {
                $redirectparams['focus'] = $id;
                $redirectparams['forcefirst'] = 1;
            }
            $redirecturl = new moodle_url('/local/dutydesk/tasks.php', $redirectparams);
            if ($id) {
                $redirecturl->set_anchor('task-' . $id);
            }
            redirect($redirecturl);
        } else if ($form && $data = $form->get_data()) {
            if (!empty($data->id)) {
                permissions::require_edit_task((int)$data->id, $canmanagealltasks, $context);
            } else {
                permissions::require_create_task(0, $context);
            }

            $record = new stdClass();
            $canedittitle = local_dutydesk_user_can_manage_departments($USER->id) || $canmanagealltasks;
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
            $data->description_editor = $data->description_editor ?? ['text' => '', 'format' => FORMAT_HTML];
            $existingtask = null;
            $beforedocsnapshot = [];
            if (!empty($data->id)) {
                $existingtask = $DB->get_record('local_dutydesk_task', ['id' => $data->id]);
                $beforedocsnapshot = local_dutydesk_get_task_document_snapshot($context->id, (int)$data->id);
                if ($existingtask && !$canedittitle) {
                    // Keep the original title for users that are not allowed to edit it.
                    $record->title = $existingtask->title;
                }
            }

            if ($data->id) {
                $record->id = $data->id;
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
                if ($existingtask && $existingtask->title !== $record->title) {
                    $changedetails[] = get_string('taskhistory_detail_title', 'local_dutydesk', (object)[
                        'old' => format_string($existingtask->title ?? ''),
                        'new' => format_string($record->title ?? ''),
                    ]);
                }
                $existingdescription = trim((string)($existingtask->description ?? ''));
                $newdescription = trim((string)($record->description ?? ''));
                if ($existingtask && $existingdescription !== $newdescription) {
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
                $redirectparams = $firstpageparams;
                $redirectparams['focus'] = $record->id;
                $redirectparams['forcefirst'] = 1;
                $redirecturl = new moodle_url('/local/dutydesk/tasks.php', $redirectparams);
                $redirecturl->set_anchor('task-' . $record->id);
                if ($isajax && $ismodaledit) {
                    self::close_modal_and_exit();
                }
                redirect($redirecturl, get_string('updated', 'local_dutydesk'));
            } else {
                $record->description = $data->description_editor['text'];
                $record->descriptionformat = $data->description_editor['format'];
                $taskid = $DB->insert_record('local_dutydesk_task', $record);
                $data->id = $taskid;
                $data = file_postupdate_standard_editor(
                    $data,
                    'description',
                    $editoroptions,
                    $context,
                    'local_dutydesk',
                    'taskdescription',
                    $taskid
                );
                $DB->update_record('local_dutydesk_task', [
                    'id' => $taskid,
                    'description' => $data->description,
                    'descriptionformat' => $data->descriptionformat,
                ]);
                file_save_draft_area_files(
                    $data->documents_filemanager ?? 0,
                    $context->id,
                    'local_dutydesk',
                    'taskdocuments',
                    $taskid,
                    $filemanageroptions
                );
                local_dutydesk_save_task_assignment($taskid, $positionid, $normalizedworkload, $caneditworkload);
                local_dutydesk_log_task_history($taskid, 'created', format_string($record->title));
                $afterdocsnapshot = local_dutydesk_get_task_document_snapshot($context->id, $taskid);
                $docdetails = local_dutydesk_describe_document_changes([], $afterdocsnapshot);
                if ($docdetails !== '') {
                    local_dutydesk_log_task_history($taskid, 'documents', $docdetails);
                }
                $redirectparams = $firstpageparams;
                $redirectparams['focus'] = $taskid;
                $redirectparams['forcefirst'] = 1;
                $redirecturl = new moodle_url('/local/dutydesk/tasks.php', $redirectparams);
                $redirecturl->set_anchor('task-' . $taskid);
                if ($isajax && $ismodaledit) {
                    self::close_modal_and_exit();
                }
                redirect($redirecturl, get_string('saved', 'local_dutydesk'));
            }
        } else if ($form && $id) {
            $record = repository::get_task_for_form($id);
            $assignment = repository::get_task_assignment($id);
            if ($assignment) {
                $record->positionid = $assignment->positionid;
                if (isset($assignment->workloadpercent)) {
                    $record->workloadpercent = $assignment->workloadpercent;
                }
            }
            if (!isset($record->workloadpercent)) {
                $record->workloadpercent = '';
            }
            $record = file_prepare_standard_editor(
                $record,
                'description',
                $editoroptions,
                $context,
                'local_dutydesk',
                'taskdescription',
                $record->id
            );
            $record = file_prepare_standard_filemanager(
                $record,
                'documents',
                $filemanageroptions,
                $context,
                'local_dutydesk',
                'taskdocuments',
                $record->id
            );
            $form->set_data($record);
        }

        $displayform = ($canmanagealltasks && $showform) || ($id && local_dutydesk_user_can_edit_task($id));
        if ($form && $form->is_submitted() && !$form->is_cancelled()) {
            $displayform = true;
        }

        if ($isajax && $ismodaledit && $displayform && $form) {
            echo $OUTPUT->header();
            echo html_writer::start_div('local-dutydesk-task-modal-editor');
            echo html_writer::start_div('local-dutydesk-task-modal-card');
            echo html_writer::start_div('local-dutydesk-task-modal-section local-dutydesk-task-modal-form');
            $form->display();
            echo html_writer::end_div();
            echo html_writer::end_div();
            echo html_writer::end_div();
            echo $OUTPUT->footer();
            die;
        }

        if (!$isajax) {
            echo $OUTPUT->header();
            echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
                'istasks' => true,
                'showdepartments' => local_dutydesk_show_departments_tab(),
                'showpositionsarchived' => local_dutydesk_show_archived_positions_tab(),
            ]);
            if ($displayform && $form) {
                $form->display();
                if (empty($id)) {
                    echo $OUTPUT->footer();
                    die;
                }
                if ($id) {
                    $newtaskurl = new moodle_url('/local/dutydesk/tasks.php');
                    if ($categoryid > 0) {
                        $newtaskurl->param('category', $categoryid);
                    }
                    if ($departmentfilterid > 0) {
                        $newtaskurl->param('departmentid', $departmentfilterid);
                    }
                    if ($positionfilterid > 0) {
                        $newtaskurl->param('positionid', $positionfilterid);
                    }
                    if ($vacantonly) {
                        $newtaskurl->param('vacantonly', 1);
                    }
                    echo html_writer::div(
                        html_writer::link(
                            $newtaskurl,
                            get_string('newtask', 'local_dutydesk'),
                            ['class' => 'btn btn-secondary mt-2']
                        ),
                        'mb-4'
                    );
                    $previewrecord = $DB->get_record('local_dutydesk_task', ['id' => $id]);
                    if ($previewrecord) {
                        $previewdata = local_dutydesk_build_task_display(
                            $previewrecord,
                            $context,
                            true,
                            $canmanagealltasks,
                            $page,
                            $perpage
                        );
                        if ($previewdata) {
                            echo html_writer::div(
                                $OUTPUT->render_from_template('local_dutydesk/task_list', [
                                    'displaysearch' => false,
                                    'tasks' => [$previewdata],
                                ]),
                                'local-dutydesk-task-edit-preview mb-4'
                            );
                        }
                    }
                }
            } else if ($canmanagealltasks) {
                $toggleurl = new moodle_url('/local/dutydesk/tasks.php', [
                    'showform' => 1,
                    'perpage' => $perpage,
                    'page' => $page,
                ]);
                $togglemodalurl = new moodle_url('/local/dutydesk/tasks.php', [
                    'showform' => 1,
                    'perpage' => $perpage,
                    'page' => $page,
                    'ajax' => 1,
                    'modaledit' => 1,
                    'sesskey' => sesskey(),
                ]);
                if ($categoryid > 0) {
                    $toggleurl->param('category', $categoryid);
                    $togglemodalurl->param('category', $categoryid);
                }
                if ($departmentfilterid > 0) {
                    $toggleurl->param('departmentid', $departmentfilterid);
                    $togglemodalurl->param('departmentid', $departmentfilterid);
                }
                if ($positionfilterid > 0) {
                    $toggleurl->param('positionid', $positionfilterid);
                    $togglemodalurl->param('positionid', $positionfilterid);
                }
                if ($vacantonly) {
                    $toggleurl->param('vacantonly', 1);
                    $togglemodalurl->param('vacantonly', 1);
                }
                $importurl = new moodle_url('/local/dutydesk/task_import.php');
                echo html_writer::div(
                    html_writer::link($toggleurl, get_string('newtaskbutton', 'local_dutydesk'), [
                    'class' => 'btn btn-primary',
                    'data-action' => 'new-task',
                    'data-modal-url' => $togglemodalurl->out(false),
                    'data-modal-title' => get_string('newtaskbutton', 'local_dutydesk'),
                    ]) .
                    html_writer::link($importurl, get_string('taskimportbutton', 'local_dutydesk'), [
                    'class' => 'btn btn-outline-primary ml-2',
                    ]),
                    'mb-3'
                );
            }
        }

        $records = [];
        $totaltasks = 0;
        $tasksdatasql = '';
        $tasksdataparams = [];
        $taskconditions = [];
        if ($categoryid > 0) {
            $taskconditions['categoryid'] = $categoryid;
        }

        if ($hassearch) {
            if ($canviewalltasks) {
                $filters = [];
                $params = [];
                if ($categoryid > 0) {
                    $params['categoryidfilter'] = $categoryid;
                    $filters[] = 't.categoryid = :categoryidfilter';
                }
                if ($positionfilterid > 0) {
                    $params['positionidfilter'] = $positionfilterid;
                    $filters[] = 'searchta.positionid = :positionidfilter';
                }
                if ($departmentfilterid > 0) {
                    $params['departmentidfilter'] = $departmentfilterid;
                    $filters[] = 'searchp.departmentid = :departmentidfilter';
                }
                if ($vacantonly) {
                    $filters[] = 'COALESCE(searchp.isvacant, 0) = 1';
                }
                [$searchsql, $searchparams] = local_dutydesk_build_task_search_condition(
                    $searchlikevalue,
                    't',
                    'searchp',
                    'searchd',
                    'searchprimary',
                    'searchdeputy',
                    'globalsearch'
                );
                $filters[] = $searchsql;
                $params = array_merge($params, $searchparams);
                $searchjoins = " LEFT JOIN {local_dutydesk_taskassign} searchta ON searchta.taskid = t.id
                         LEFT JOIN {local_dutydesk_position} searchp ON searchp.id = searchta.positionid
                         LEFT JOIN {local_dutydesk_department} searchd ON searchd.id = searchp.departmentid
                         LEFT JOIN {user} searchprimary ON searchprimary.id = searchp.primaryuserid
                         LEFT JOIN {local_dutydesk_posdeputy} searchdeputyassign ON searchdeputyassign.positionid = searchp.id
                         LEFT JOIN {user} searchdeputy ON searchdeputy.id = searchdeputyassign.userid";
                $whereclause = 'WHERE ' . implode(' AND ', $filters);
                $countsql = "SELECT COUNT(DISTINCT t.id)
                       FROM {local_dutydesk_task} t
                       {$searchjoins}
                       {$whereclause}";
                $totaltasks = (int)$DB->count_records_sql($countsql, $params);
                if ($totaltasks > 0) {
                    $tasksdatasql = "SELECT DISTINCT t.*, COALESCE(searchp.isvacant, 0) AS vacant_sort
                               FROM {local_dutydesk_task} t
                               {$searchjoins}
                               {$whereclause}
                           ORDER BY vacant_sort DESC, t.title ASC, t.id ASC";
                    $tasksdataparams = $params;
                    $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                }
            } else {
                if (!empty($manageablepositionids) || !empty($userdepartmentids)) {
                    $params = [];
                    $accessconditions = [];
                    if (!empty($manageablepositionids)) {
                        [$positionsql, $posparams] = $DB->get_in_or_equal($manageablepositionids, SQL_PARAMS_NAMED, 'tp');
                        $accessconditions[] = "ta.positionid {$positionsql}";
                        $params = array_merge($params, $posparams);
                    }
                    if (!empty($userdepartmentids)) {
                        [$deptsql, $deptparams] = $DB->get_in_or_equal($userdepartmentids, SQL_PARAMS_NAMED, 'td');
                        $accessconditions[] = "p.departmentid {$deptsql}";
                        $params = array_merge($params, $deptparams);
                    }
                    $filters = [];
                    if (!empty($accessconditions)) {
                        $filters[] = '(' . implode(' OR ', array_map(static function ($condition) {
                            return "({$condition})";
                        }, $accessconditions)) . ')';
                    }
                    if ($categoryid > 0) {
                        $params['categoryidfilter'] = $categoryid;
                        $filters[] = 't.categoryid = :categoryidfilter';
                    }
                    if ($positionfilterid > 0) {
                        $params['positionidfilter'] = $positionfilterid;
                        $filters[] = 'ta.positionid = :positionidfilter';
                    }
                    if ($departmentfilterid > 0) {
                        $params['departmentidfilter'] = $departmentfilterid;
                        $filters[] = 'p.departmentid = :departmentidfilter';
                    }
                    if ($vacantonly) {
                        $filters[] = 'COALESCE(p.isvacant, 0) = 1';
                    }
                    [$restrictedsearchsql, $restrictedsearchparams] = local_dutydesk_build_task_search_condition(
                        $searchlikevalue,
                        't',
                        'p',
                        'd',
                        'primaryuser',
                        'deputyuser',
                        'restrictedsearch'
                    );
                    $filters[] = $restrictedsearchsql;
                    $params = array_merge($params, $restrictedsearchparams);
                    $joins = " JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                       JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                  LEFT JOIN {local_dutydesk_department} d ON d.id = p.departmentid
                  LEFT JOIN {user} primaryuser ON primaryuser.id = p.primaryuserid
                  LEFT JOIN {local_dutydesk_posdeputy} deputy ON deputy.positionid = p.id
                  LEFT JOIN {user} deputyuser ON deputyuser.id = deputy.userid";
                    $whereclause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
                    $countsql = "SELECT COUNT(DISTINCT t.id)
                           FROM {local_dutydesk_task} t
                           {$joins}
                           {$whereclause}";
                    $totaltasks = (int)$DB->count_records_sql($countsql, $params);
                    if ($totaltasks > 0) {
                        $tasksdatasql = "SELECT DISTINCT t.*, COALESCE(p.isvacant, 0) AS vacant_sort
                                   FROM {local_dutydesk_task} t
                                   {$joins}
                                   {$whereclause}
                               ORDER BY vacant_sort DESC, t.title ASC, t.id ASC";
                        $tasksdataparams = $params;
                        $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                    }
                }
            }
        } else if ($canviewalltasks) {
            if ($positionfilterid > 0) {
                $params = ['positionidfilter' => $positionfilterid];
                $categorysql = '';
                if ($categoryid > 0) {
                    $params['categoryidfilter'] = $categoryid;
                    $categorysql = ' AND t.categoryid = :categoryidfilter';
                }
                $departmentsql = '';
                if ($departmentfilterid > 0) {
                    $params['departmentidfilter'] = $departmentfilterid;
                    $departmentsql = ' AND p.departmentid = :departmentidfilter';
                }
                $vacantsql = $vacantonly ? ' AND COALESCE(p.isvacant, 0) = 1' : '';
                $countsql = "SELECT COUNT(DISTINCT t.id)
                       FROM {local_dutydesk_task} t
                       JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                       LEFT JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                      WHERE ta.positionid = :positionidfilter{$categorysql}{$departmentsql}{$vacantsql}";
                $totaltasks = (int)$DB->count_records_sql($countsql, $params);
                if ($totaltasks > 0) {
                    $tasksdatasql = "SELECT DISTINCT t.*, COALESCE(p.isvacant, 0) AS vacant_sort
                               FROM {local_dutydesk_task} t
                               JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                          LEFT JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                              WHERE ta.positionid = :positionidfilter{$categorysql}{$departmentsql}{$vacantsql}
                           ORDER BY vacant_sort DESC, t.title ASC, t.id ASC";
                    $tasksdataparams = $params;
                    $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                }
            } else {
                $params = [];
                $filters = [];
                if ($categoryid > 0) {
                    $params['categoryidfilter'] = $categoryid;
                    $filters[] = 't.categoryid = :categoryidfilter';
                }
                if ($departmentfilterid > 0) {
                    $params['departmentidfilter'] = $departmentfilterid;
                    $filters[] = 'p.departmentid = :departmentidfilter';
                }
                if ($vacantonly) {
                    $filters[] = 'COALESCE(p.isvacant, 0) = 1';
                }
                $whereclause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
                $countsql = "SELECT COUNT(DISTINCT t.id)
                       FROM {local_dutydesk_task} t
                  LEFT JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                  LEFT JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                       {$whereclause}";
                $totaltasks = (int)$DB->count_records_sql($countsql, $params);
                if ($totaltasks > 0) {
                    $tasksdatasql = "SELECT DISTINCT t.*, COALESCE(p.isvacant, 0) AS vacant_sort
                               FROM {local_dutydesk_task} t
                          LEFT JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                          LEFT JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                               {$whereclause}
                           ORDER BY vacant_sort DESC, t.title ASC, t.id ASC";
                    $tasksdataparams = $params;
                    $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                }
            }
        } else {
            if (!empty($manageablepositionids) || !empty($userdepartmentids)) {
                $params = [];
                $conditions = [];
                if (!empty($manageablepositionids)) {
                    [$positionsql, $posparams] = $DB->get_in_or_equal($manageablepositionids, SQL_PARAMS_NAMED, 'tp');
                    $conditions[] = "ta.positionid {$positionsql}";
                    $params = array_merge($params, $posparams);
                }
                if (!empty($userdepartmentids)) {
                    [$deptsql, $deptparams] = $DB->get_in_or_equal($userdepartmentids, SQL_PARAMS_NAMED, 'td');
                    $conditions[] = "p.departmentid {$deptsql}";
                    $params = array_merge($params, $deptparams);
                }
                $categorysql = '';
                if ($categoryid > 0) {
                    $params['categoryidfilter'] = $categoryid;
                    $categorysql = ' AND t.categoryid = :categoryidfilter';
                }
                $departmentfiltersql = '';
                if ($departmentfilterid > 0) {
                    $params['departmentidfilter'] = $departmentfilterid;
                    $departmentfiltersql = ' AND p.departmentid = :departmentidfilter';
                }
                $positionfiltersql = '';
                if ($positionfilterid > 0) {
                    $params['positionidfilter'] = $positionfilterid;
                    $positionfiltersql = ' AND ta.positionid = :positionidfilter';
                }
                $vacantsql = $vacantonly ? ' AND COALESCE(p.isvacant, 0) = 1' : '';
                $where = implode(' OR ', array_map(static function ($condition) {
                    return "({$condition})";
                }, $conditions));
                $countsql = "SELECT COUNT(DISTINCT t.id)
                       FROM {local_dutydesk_task} t
                       JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                       JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                      WHERE {$where}{$categorysql}{$departmentfiltersql}{$positionfiltersql}{$vacantsql}";
                $totaltasks = (int)$DB->count_records_sql($countsql, $params);
                if ($totaltasks > 0) {
                    $tasksdatasql = "SELECT DISTINCT t.*, COALESCE(p.isvacant, 0) AS vacant_sort
                               FROM {local_dutydesk_task} t
                               JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                               JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                              WHERE {$where}{$categorysql}{$departmentfiltersql}{$positionfiltersql}{$vacantsql}
                           ORDER BY vacant_sort DESC, t.title ASC, t.id ASC";
                    $tasksdataparams = $params;
                    $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                }
            }
        }

        if ($totaltasks > 0 && $offset >= $totaltasks) {
            $page = (int)floor(($totaltasks - 1) / $perpage);
            $offset = $page * $perpage;
            if ($canviewalltasks && !$hassearch) {
                if ($tasksdatasql !== '') {
                    $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
                } else if (!empty($taskconditions)) {
                    $records = $DB->get_records(
                        'local_dutydesk_task',
                        $taskconditions,
                        'title ASC, id ASC',
                        '*',
                        $offset,
                        $perpage
                    );
                } else {
                    $records = $DB->get_records('local_dutydesk_task', null, 'title ASC, id ASC', '*', $offset, $perpage);
                }
            } else if ($tasksdatasql !== '') {
                $records = $DB->get_records_sql($tasksdatasql, $tasksdataparams, $offset, $perpage);
            }
        }
        $tasksdata = [];

        $paginationhtml = '';
        if (!empty($records)) {
            $paginationhtml = $OUTPUT->paging_bar($totaltasks, $page, $perpage, $listbaseurl);
            if ($focus > 0) {
                $focusedrecord = null;
                $focuswasincluded = false;
                if (isset($records[$focus])) {
                    $focusedrecord = $records[$focus];
                    $focuswasincluded = true;
                } else if ($forcefirst && local_dutydesk_user_can_view_task($focus)) {
                    $focusedrecord = $DB->get_record('local_dutydesk_task', ['id' => $focus]);
                }
                if ($focusedrecord) {
                    unset($records[$focus]);
                    $records = [$focus => $focusedrecord] + $records;
                    if ($forcefirst && !$focuswasincluded && count($records) > $perpage) {
                        $records = array_slice($records, 0, $perpage, true);
                    }
                }
            }

            $taskids = array_keys($records);
            $assignmentsbytask = [];
            $subtasksbytask = [];

            if (!empty($taskids)) {
                [$insql, $params] = $DB->get_in_or_equal($taskids, SQL_PARAMS_NAMED);

                $assignmentssql = "SELECT ta.id, ta.taskid, ta.positionid, ta.workloadpercent,
                                  p.title AS positiontitle, p.departmentid, p.primaryuserid, p.archived AS positionarchived,
                                  p.isvacant AS positionvacant, p.positiontype,
                                  d.name AS departmentname,
                                  primaryuser.firstname AS primaryfirstname,
                                  primaryuser.lastname AS primarylastname,
                                  primaryuser.middlename AS primarymiddlename,
                                  primaryuser.alternatename AS primaryalternatename,
                                  primaryuser.firstnamephonetic AS primaryfirstnamephonetic,
                                  primaryuser.lastnamephonetic AS primarylastnamephonetic,
                                  primaryuser.idnumber AS primaryidnumber,
                                  primaryuser.email AS primaryemail,
                                  deputy.userid AS deputyuserid,
                                  deputyuser.firstname AS deputyfirstname,
                                  deputyuser.lastname AS deputylastname,
                                  deputyuser.middlename AS deputymiddlename,
                                  deputyuser.alternatename AS deputyalternatename,
                                  deputyuser.firstnamephonetic AS deputyfirstnamephonetic,
                                  deputyuser.lastnamephonetic AS deputylastnamephonetic,
                                  deputyuser.idnumber AS deputyidnumber,
                                  deputyuser.email AS deputyemail
                             FROM {local_dutydesk_taskassign} ta
                        LEFT JOIN {local_dutydesk_position} p ON p.id = ta.positionid
                        LEFT JOIN {local_dutydesk_department} d ON d.id = p.departmentid
                        LEFT JOIN {user} primaryuser ON primaryuser.id = p.primaryuserid
                        LEFT JOIN {local_dutydesk_posdeputy} deputy ON deputy.positionid = p.id
                        LEFT JOIN {user} deputyuser ON deputyuser.id = deputy.userid
                            WHERE ta.taskid {$insql}";
                $assignmentrecords = $DB->get_records_sql($assignmentssql, $params);
                foreach ($assignmentrecords as $assignment) {
                    $assignmentsbytask[$assignment->taskid] = $assignment;
                }

                $subtaskrecords = $DB->get_records_select(
                    'local_dutydesk_subtask',
                    "taskid {$insql}",
                    $params,
                    'sortorder ASC, id ASC'
                );
                foreach ($subtaskrecords as $subtask) {
                    $subtasksbytask[$subtask->taskid][] = $subtask;
                }
            }

            $fs = get_file_storage();

            foreach ($records as $task) {
                $editurl = new moodle_url('/local/dutydesk/tasks.php', [
                    'id' => $task->id,
                    'page' => $page,
                    'perpage' => $perpage,
                ]);
                if ($categoryid > 0) {
                    $editurl->param('category', $categoryid);
                }
                if ($departmentfilterid > 0) {
                    $editurl->param('departmentid', $departmentfilterid);
                }
                if ($positionfilterid > 0) {
                    $editurl->param('positionid', $positionfilterid);
                }
                if ($vacantonly) {
                    $editurl->param('vacantonly', 1);
                }
                $deleteurl = new moodle_url('/local/dutydesk/tasks.php', [
                    'delete' => 1,
                    'id' => $task->id,
                    'page' => $page,
                    'perpage' => $perpage,
                ]);
                if ($categoryid > 0) {
                    $deleteurl->param('category', $categoryid);
                }
                if ($departmentfilterid > 0) {
                    $deleteurl->param('departmentid', $departmentfilterid);
                }
                if ($positionfilterid > 0) {
                    $deleteurl->param('positionid', $positionfilterid);
                }
                if ($vacantonly) {
                    $deleteurl->param('vacantonly', 1);
                }
                $newsubtaskurl = new moodle_url('/local/dutydesk/subtask.php', [
                    'taskid' => $task->id,
                    'page' => $page,
                    'perpage' => $perpage,
                    'focus' => $task->id,
                ]);
                if ($categoryid > 0) {
                    $newsubtaskurl->param('category', $categoryid);
                }
                if ($departmentfilterid > 0) {
                    $newsubtaskurl->param('departmentid', $departmentfilterid);
                }
                if ($positionfilterid > 0) {
                    $newsubtaskurl->param('positionid', $positionfilterid);
                }
                if ($vacantonly) {
                    $newsubtaskurl->param('vacantonly', 1);
                }
                $taskmodalurl = new moodle_url('/local/dutydesk/task_modal.php', [
                    'taskid' => $task->id,
                    'modal' => 1,
                ]);
                $newsubtaskmodalurl = new moodle_url('/local/dutydesk/subtask.php', [
                    'taskid' => $task->id,
                    'modal' => 1,
                ]);
                $canedit = local_dutydesk_user_can_edit_task($task->id);

                $assignment = $assignmentsbytask[$task->id] ?? null;
                $istopicarea = $assignment && local_dutydesk_position_is_topic_area((object)[
                    'positiontype' => $assignment->positiontype ?? null,
                ]);
                $isvacanttask = $assignment && !$istopicarea && !empty($assignment->positionvacant);
                $primaryuserdisplay = get_string('notassigned', 'local_dutydesk');
                $primaryuserplain = $primaryuserdisplay;
                $deputydefault = get_string('nodeputy', 'local_dutydesk');
                $deputyuserdisplay = $deputydefault;
                $deputyuserplain = '';
                $assignedpositionplain = get_string('notassigned', 'local_dutydesk');
                $assignedpositiondisplay = s($assignedpositionplain);
                $positionviewurl = null;
                $departmentplain = get_string('notassigned', 'local_dutydesk');
                $departmentdisplay = s($departmentplain);
                $workloadvalue = null;
                $workloaddisplay = local_dutydesk_format_workload_display(null);

                if ($assignment && !$istopicarea && !empty($assignment->primaryuserid)) {
                    $primaryuser = (object) [
                        'id' => $assignment->primaryuserid,
                        'firstname' => $assignment->primaryfirstname ?? '',
                        'lastname' => $assignment->primarylastname ?? '',
                        'middlename' => $assignment->primarymiddlename ?? '',
                        'alternatename' => $assignment->primaryalternatename ?? '',
                        'firstnamephonetic' => $assignment->primaryfirstnamephonetic ?? '',
                        'lastnamephonetic' => $assignment->primarylastnamephonetic ?? '',
                    ];
                    $primaryfullname = fullname($primaryuser, has_capability('moodle/site:viewfullnames', $context));
                    $profileurl = new moodle_url('/user/profile.php', ['id' => $assignment->primaryuserid]);
                    $primaryuserdisplay = html_writer::link($profileurl, $primaryfullname);
                    $primaryuserplain = $primaryfullname;
                }

                if ($assignment && !$istopicarea && !empty($assignment->deputyuserid)) {
                    $deputyuser = (object) [
                        'id' => $assignment->deputyuserid,
                        'firstname' => $assignment->deputyfirstname ?? '',
                        'lastname' => $assignment->deputylastname ?? '',
                        'middlename' => $assignment->deputymiddlename ?? '',
                        'alternatename' => $assignment->deputyalternatename ?? '',
                        'firstnamephonetic' => $assignment->deputyfirstnamephonetic ?? '',
                        'lastnamephonetic' => $assignment->deputylastnamephonetic ?? '',
                    ];
                    $deputyfullname = fullname($deputyuser, has_capability('moodle/site:viewfullnames', $context));
                    $profileurl = new moodle_url('/user/profile.php', ['id' => $assignment->deputyuserid]);
                    $deputyuserdisplay = html_writer::link($profileurl, $deputyfullname);
                    $deputyuserplain = $deputyfullname;
                }

                if ($assignment && !empty($assignment->positiontitle)) {
                    $assignedpositionplain = format_string($assignment->positiontitle);
                    if (!empty($assignment->positionid)) {
                        $viewparam = !empty($assignment->positionarchived) ? 'archived' : 'active';
                        $positionurl = new moodle_url('/local/dutydesk/positions.php', [
                            'focus' => $assignment->positionid,
                            'positionid' => $assignment->positionid,
                            'view' => $viewparam,
                        ]);
                        $positionurl->set_anchor('position-' . $assignment->positionid);
                        $assignedpositiondisplay = html_writer::link($positionurl, $assignedpositionplain);
                        $positionviewurl = $positionurl->out(false);
                    } else {
                        $assignedpositiondisplay = $assignedpositionplain;
                    }
                }

                if ($assignment && !empty($assignment->departmentname)) {
                    $departmentplain = format_string($assignment->departmentname);
                    if (!empty($assignment->departmentid)) {
                        $departmenturl = new moodle_url('/local/dutydesk/departments.php', [
                            'focus' => $assignment->departmentid,
                        ]);
                        $departmenturl->set_anchor('department-' . $assignment->departmentid);
                        $departmentdisplay = html_writer::link($departmenturl, $departmentplain);
                    } else {
                        $departmentdisplay = $departmentplain;
                    }
                }
                if ($assignment && $assignment->workloadpercent !== null) {
                    $workloadvalue = (int)$assignment->workloadpercent;
                }
                $workloaddisplay = local_dutydesk_format_workload_display($workloadvalue);

                $taskdescriptionfromdb = isset($task->description) ? (string)$task->description : '';
                $taskdescriptionformat = property_exists($task, 'descriptionformat')
                    ? (int)$task->descriptionformat
                    : FORMAT_HTML;
                $taskdescriptionrendered = local_dutydesk_format_list_text(
                    $taskdescriptionfromdb,
                    $taskdescriptionformat,
                    $context,
                    'taskdescription',
                    (int)$task->id
                );
                $taskdescriptionplain = $taskdescriptionrendered !== '' ? html_to_text($taskdescriptionrendered, 0) : '';

                $taskdocuments = [];
                $taskdocumentsnames = [];
                $taskfiles = $fs->get_area_files(
                    $context->id,
                    'local_dutydesk',
                    'taskdocuments',
                    $task->id,
                    'filename',
                    false
                );
                if (!empty($taskfiles)) {
                    foreach ($taskfiles as $file) {
                        $filepath = moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            $file->get_itemid(),
                            $file->get_filepath(),
                            $file->get_filename(),
                            true
                        );
                        $taskdocuments[] = [
                            'name' => $file->get_filename(),
                            'url' => $filepath->out(false),
                            'mimetype' => $file->get_mimetype(),
                            'size' => display_size($file->get_filesize()),
                        ];
                        $taskdocumentsnames[] = $file->get_filename();
                    }
                }

                $subtasks = [];
                $subtasktitles = [];
                $subtasksearchentries = [];
                if (!empty($subtasksbytask[$task->id])) {
                    $tasksubtasks = $subtasksbytask[$task->id];
                    usort($tasksubtasks, static function ($a, $b) {
                        $aorder = property_exists($a, 'sortorder') && $a->sortorder !== null ? (int)$a->sortorder : PHP_INT_MAX;
                        $border = property_exists($b, 'sortorder') && $b->sortorder !== null ? (int)$b->sortorder : PHP_INT_MAX;
                        if ($aorder === $border) {
                            return $a->id <=> $b->id;
                        }
                        return $aorder <=> $border;
                    });

                    foreach ($tasksubtasks as $subtask) {
                        $subtasktitle = format_string($subtask->title);
                        $subtasktitles[] = $subtasktitle;

                        $editsubtaskurl = new moodle_url('/local/dutydesk/subtask.php', [
                            'taskid' => $task->id,
                            'id' => $subtask->id,
                        ]);
                        $editsubtaskmodalurl = new moodle_url('/local/dutydesk/subtask.php', [
                            'taskid' => $task->id,
                            'id' => $subtask->id,
                            'modal' => 1,
                        ]);

                        $descriptionfromdb = isset($subtask->description) ? (string)$subtask->description : '';
                        $subtaskdescriptionformat = property_exists($subtask, 'descriptionformat')
                            ? (int)$subtask->descriptionformat
                            : FORMAT_HTML;
                        $descriptionrendered = local_dutydesk_format_list_text(
                            $descriptionfromdb,
                            $subtaskdescriptionformat,
                            $context,
                            'subtaskdescription',
                            (int)$subtask->id
                        );

                        $documents = [];
                        $documentsnames = [];
                        $files = $fs->get_area_files(
                            $context->id,
                            'local_dutydesk',
                            'subtaskdocuments',
                            $subtask->id,
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
                                $documentsnames[] = $file->get_filename();
                            }
                        }

                        $subtaskdescriptionplain = $descriptionrendered !== '' ? html_to_text($descriptionrendered, 0) : '';
                        $subtasksearchentries[] = trim(implode(' ', array_filter([
                            $subtasktitle,
                            $subtaskdescriptionplain,
                            implode(' ', $documentsnames),
                        ])));

                        $subtasks[] = [
                            'id' => $subtask->id,
                            'title' => $subtasktitle,
                            'description' => $descriptionrendered !== '' ? $descriptionrendered : null,
                            'hasdocuments' => !empty($documents),
                            'documents' => $documents,
                            'editurl' => $canedit ? $editsubtaskurl->out(false) : null,
                            'editmodalurl' => $canedit ? $editsubtaskmodalurl->out(false) : null,
                            'canedit' => $canedit,
                        ];
                    }
                }

                $searchcomponents = array_filter([
                    format_string($task->title),
                    $taskdescriptionplain,
                    $primaryuserplain,
                    $deputyuserplain,
                    $assignedpositionplain,
                    $departmentplain,
                    implode(' ', $taskdocumentsnames),
                    implode(' ', $subtasktitles),
                    implode(' ', $subtasksearchentries),
                ]);
                $searchtext = core_text::strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $searchcomponents))));

                $candelete = $canmanagealltasks && $assignment && !empty($assignment->positionarchived);
                $canviewhistory = local_dutydesk_user_can_view_task_history((int)$task->id, (int)$USER->id);
                $tasksdata[] = [
                    'id' => $task->id,
                    'title' => format_string($task->title),
                    'description' => $taskdescriptionrendered !== '' ? $taskdescriptionrendered : null,
                    'hasdocuments' => !empty($taskdocuments),
                    'documents' => $taskdocuments,
                    'assigneduser' => $primaryuserdisplay,
                    'deputyuser' => $deputyuserdisplay,
                    'istopicarea' => $istopicarea,
                    'positiontypelabel' => $istopicarea
                        ? get_string('positiontype_topicarea', 'local_dutydesk')
                        : get_string('positiontype_position', 'local_dutydesk'),
                    'assignedposition' => $assignedpositionplain,
                    'assignedpositiondisplay' => $assignedpositiondisplay,
                    'positionurl' => $positionviewurl,
                    'department' => $departmentplain,
                    'departmentdisplay' => $departmentdisplay,
                    'hasworkload' => $workloadvalue !== null,
                    'workload' => $workloadvalue,
                    'workloaddisplay' => $workloaddisplay,
                    'isvacanttask' => $isvacanttask,
                    'vacanttasklabel' => get_string('taskvacantbadge', 'local_dutydesk'),
                    'timestamp' => userdate($task->timestamp),
                    'editurl' => $canedit ? $editurl->out(false) : null,
                    'editmodalurl' => $canedit ? $taskmodalurl->out(false) : null,
                    'deleteurl' => $candelete ? $deleteurl->out(false) : null,
                    'newsubtaskurl' => $canedit ? $newsubtaskurl->out(false) : null,
                    'newsubtaskmodalurl' => $canedit ? $newsubtaskmodalurl->out(false) : null,
                    'hassubtasks' => !empty($subtasks),
                    'subtaskcount' => count($subtasks),
                    'subtasks' => $subtasks,
                    'canedit' => $canedit,
                    'candelete' => $candelete,
                    'cancreatesubtask' => $canedit,
                    'cansortsubtasks' => false,
                    'showactions' => ($canedit || $canmanagealltasks),
                    'canviewhistory' => $canviewhistory,
                    'sesskey' => sesskey(),
                    'searchtext' => $searchtext,
                ];
            }
        }

        $searchbaseurl = new moodle_url('/local/dutydesk/tasks.php');
        $departmentrecords = repository::get_filter_departments($canviewalltasks, $userdepartmentids, $manageablepositionids);
        $categoryfilterdata = presenter::build_category_filter($categoryrecords, $categoryid);
        $departmentfilterdata = presenter::build_department_filter($departmentrecords, $departmentfilterid);
        $templatetdata = presenter::build_list_template_data(
            $tasksdata,
            $searchvalue,
            $searchbaseurl,
            $perpage,
            $page,
            $positionfilterid,
            $vacantonly,
            $categoryfilterdata,
            $departmentfilterdata
        );

        ob_start();
        if ($paginationhtml !== '') {
            echo $paginationhtml;
        }
        echo $OUTPUT->render_from_template('local_dutydesk/task_list', $templatetdata);
        if ($paginationhtml !== '') {
            echo $paginationhtml;
        }
        $resultsinnerhtml = ob_get_clean();

        if ($isajax) {
            header('Content-Type: text/html; charset=utf-8');
            echo $resultsinnerhtml;
            die;
        }

        $resultswrapper = html_writer::tag('div', $resultsinnerhtml, [
        'class' => 'local-dutydesk-task-results',
        'data-region' => 'task-results',
        'data-search-endpoint' => $searchbaseurl->out(false),
        'data-sesskey' => sesskey(),
        ]);

        echo $resultswrapper;
        echo $OUTPUT->footer();
    }

    /**
     * Close the parent modal from an embedded iframe and stop processing.
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
