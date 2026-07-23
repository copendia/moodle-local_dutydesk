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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Prepares position records for Mustache templates.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presenter {
    /**
     * Build template data for a list of positions.
     *
     * @param array $records
     * @param \context $context
     * @param array $options
     * @return \stdClass
     */
    public static function build(array $records, \context $context, array $options): \stdClass {
        global $DB, $OUTPUT, $USER;

        $positionsdata = [];
        $selectedpositiondata = null;
        $previewhtml = '';

        if (empty($records)) {
            return (object) [
                'positions' => $positionsdata,
                'previewhtml' => $previewhtml,
            ];
        }

        $positionids = array_keys($records);
        $departments = $DB->get_records_menu('dutydesk_department', null, 'name ASC', 'id, name');
        $tasksbyposition = [];
        $subtasksbytask = [];
        $deputiesbyposition = [];
        $primaryuserids = [];
        $deputyuserids = [];

        if (!empty($positionids)) {
            [$insql, $params] = $DB->get_in_or_equal($positionids, SQL_PARAMS_NAMED);
            $assignmentsql = "SELECT ta.id, ta.positionid, ta.taskid, ta.workloadpercent, t.title AS tasktitle,
                                     t.description AS taskdescription, t.descriptionformat AS taskdescriptionformat
                                FROM {dutydesk_taskassignment} ta
                           LEFT JOIN {dutydesk_task} t ON t.id = ta.taskid
                               WHERE ta.positionid {$insql}
                            ORDER BY t.title ASC";
            $assignmentrecords = $DB->get_records_sql($assignmentsql, $params);

            foreach ($assignmentrecords as $assignment) {
                if (empty($assignment->taskid) || empty($assignment->tasktitle)) {
                    continue;
                }
                $tasksbyposition[$assignment->positionid][] = $assignment;
            }

            $assignedtaskids = array_values(array_unique(array_map(static function ($assignment) {
                return (int)$assignment->taskid;
            }, array_filter($assignmentrecords, static function ($assignment) {
                return !empty($assignment->taskid);
            }))));
            if (!empty($assignedtaskids)) {
                [$taskinsql, $taskparams] = $DB->get_in_or_equal($assignedtaskids, SQL_PARAMS_NAMED);
                $subtaskrecords = $DB->get_records_select(
                    'dutydesk_subtask',
                    "taskid {$taskinsql}",
                    $taskparams,
                    'sortorder ASC, id ASC',
                    'id, taskid, title, description, descriptionformat'
                );
                foreach ($subtaskrecords as $subtaskrecord) {
                    $subtasksbytask[(int)$subtaskrecord->taskid][] = $subtaskrecord;
                }
            }

            $deputyrecords = $DB->get_records_list('dutydesk_position_deputy', 'positionid', $positionids);
            foreach ($deputyrecords as $deputy) {
                $deputiesbyposition[$deputy->positionid] = $deputy;
                if (!empty($deputy->userid)) {
                    $deputyuserids[] = (int)$deputy->userid;
                }
            }
        }

        foreach ($records as $position) {
            if (!empty($position->primaryuserid)) {
                $primaryuserids[] = (int)$position->primaryuserid;
            }
        }

        $userids = array_unique(array_filter(array_merge($primaryuserids, $deputyuserids)));
        $users = [];
        if (!empty($userids)) {
            $users = $DB->get_records_list('user', 'id', $userids);
        }

        $previewlimit = 5;
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
        foreach ($records as $position) {
            $currentdata = self::build_position(
                $position,
                $context,
                $options,
                $departments,
                $users,
                $deputiesbyposition,
                $tasksbyposition,
                $subtasksbytask,
                $previewlimit,
                $canviewfullnames
            );

            $positionsdata[] = $currentdata;
            if (
                !empty($options['showform'])
                && !empty($options['id'])
                && (int)$position->id === (int)$options['id']
            ) {
                $selectedpositiondata = $currentdata;
            }
        }

        if (!empty($options['showform']) && $selectedpositiondata !== null) {
            $previewhtml = \html_writer::div(
                $OUTPUT->render_from_template('local_dutydesk/position_list', [
                    'positions' => [$selectedpositiondata],
                ]),
                'local-dutydesk-position-preview mb-4'
            );
        }

        return (object) [
            'positions' => $positionsdata,
            'previewhtml' => $previewhtml,
        ];
    }

    /**
     * Build template data for one position.
     *
     * @param \stdClass $position
     * @param \context $context
     * @param array $options
     * @param array $departments
     * @param array $users
     * @param array $deputiesbyposition
     * @param array $tasksbyposition
     * @param array $subtasksbytask
     * @param int $previewlimit
     * @param bool $canviewfullnames
     * @return array
     */
    private static function build_position(
        \stdClass $position,
        \context $context,
        array $options,
        array $departments,
        array $users,
        array $deputiesbyposition,
        array $tasksbyposition,
        array $subtasksbytask,
        int $previewlimit,
        bool $canviewfullnames
    ): array {
        global $USER;

        $istopicarea = \local_dutydesk_position_is_topic_area($position);
        $editurl = new \moodle_url('/local/dutydesk/positions.php', ['id' => $position->id, 'view' => $options['view']]);
        $editmodalurl = new \moodle_url('/local/dutydesk/positions.php', [
            'id' => $position->id,
            'view' => $options['view'],
            'ajax' => 1,
            'modaledit' => 1,
            'sesskey' => sesskey(),
        ]);
        $deleteurl = new \moodle_url('/local/dutydesk/positions.php', [
            'delete' => 1,
            'id' => $position->id,
            'page' => $options['page'],
            'perpage' => $options['perpage'],
            'view' => $options['view'],
        ]);
        $editurl->param('page', $options['page']);
        $editurl->param('perpage', $options['perpage']);
        $editmodalurl->param('page', $options['page']);
        $editmodalurl->param('perpage', $options['perpage']);
        if (!empty($options['positionfilterid'])) {
            $editurl->param('positionid', $options['positionfilterid']);
            $editmodalurl->param('positionid', $options['positionfilterid']);
        }
        if (!empty($options['hassearch'])) {
            $editurl->param('search', $options['searchvalue']);
            $editmodalurl->param('search', $options['searchvalue']);
        }
        $canmanageposition = !empty($options['caneditpositions'])
            && (!empty($options['canmanageall'])
                || in_array((int)$position->id, $options['manageablepositionids'], true));
        $archiveurl = new \moodle_url('/local/dutydesk/positions.php', [
            'archive' => $position->id,
            'page' => $options['page'],
            'perpage' => $options['perpage'],
            'view' => $options['view'],
        ]);
        $restoreurl = new \moodle_url('/local/dutydesk/positions.php', [
            'restore' => $position->id,
            'page' => $options['page'],
            'perpage' => $options['perpage'],
            'view' => $options['view'],
        ]);
        if (!empty($options['hassearch'])) {
            $deleteurl->param('search', $options['searchvalue']);
            $archiveurl->param('search', $options['searchvalue']);
            $restoreurl->param('search', $options['searchvalue']);
        }
        if (!empty($options['positionfilterid'])) {
            $deleteurl->param('positionid', $options['positionfilterid']);
            $archiveurl->param('positionid', $options['positionfilterid']);
            $restoreurl->param('positionid', $options['positionfilterid']);
        }

        $departmentname = $departments[$position->departmentid] ?? get_string('notassigned', 'local_dutydesk');
        $departmentdisplay = $departmentname;
        $cannavigatetodepartment = \local_dutydesk_show_departments_tab((int)$USER->id);
        if (!empty($position->departmentid) && $cannavigatetodepartment) {
            $departmenturl = new \moodle_url('/local/dutydesk/departments.php', [
                'focus' => $position->departmentid,
            ]);
            $departmenturl->set_anchor('department-' . $position->departmentid);
            $departmentdisplay = \html_writer::link($departmenturl, format_string($departmentname));
        } else {
            $departmentdisplay = format_string($departmentdisplay);
        }

        $primaryuserdisplay = get_string('notassigned', 'local_dutydesk');
        $hasprimaryuser = false;
        if (!empty($position->primaryuserid) && isset($users[$position->primaryuserid])) {
            $primaryuserrecord = $users[$position->primaryuserid];
            $primaryname = fullname($primaryuserrecord, $canviewfullnames);
            $profileurl = new \moodle_url('/user/profile.php', ['id' => $primaryuserrecord->id]);
            $primaryuserdisplay = \html_writer::link($profileurl, $primaryname);
            $hasprimaryuser = true;
        }

        $deputydata = $deputiesbyposition[$position->id] ?? null;
        $deputyuserdisplay = get_string('nodeputy', 'local_dutydesk');
        $hasdeputyuser = false;
        if ($deputydata && !empty($deputydata->userid) && isset($users[$deputydata->userid])) {
            $deputyrecord = $users[$deputydata->userid];
            $deputyname = fullname($deputyrecord, $canviewfullnames);
            $profileurl = new \moodle_url('/user/profile.php', ['id' => $deputyrecord->id]);
            $deputyuserdisplay = \html_writer::link($profileurl, $deputyname);
            $hasdeputyuser = true;
        }

        [$tasks, $positionworkloadtotal] = self::build_tasks(
            $position,
            $context,
            $tasksbyposition,
            $subtasksbytask
        );

        $description = trim($position->description ?? '');
        $descriptionrendered = $description !== ''
            ? \local_dutydesk_format_list_text($description, FORMAT_HTML, $context)
            : '';
        $positionarchived = !empty($position->archived);
        $archivedtime = $positionarchived && !empty($position->archivedtime) ? (int)$position->archivedtime : 0;
        if ($positionarchived && $archivedtime <= 0 && !empty($position->timestamp)) {
            $archivedtime = (int)$position->timestamp;
        }
        $archiveddate = $archivedtime > 0 ? userdate($archivedtime) : null;

        $taskcount = count($tasks);
        $previewtasks = $tasks;
        $overflowtasks = [];
        $hasoverflow = false;

        if ($taskcount > $previewlimit) {
            $previewtasks = array_slice($tasks, 0, $previewlimit);
            $overflowtasks = array_slice($tasks, $previewlimit);
            $hasoverflow = true;
        }

        $positiontitleformatted = format_string($position->title);
        $tileclass = \local_dutydesk_get_position_tile_pattern_class((int)$position->id, $positiontitleformatted);
        $workloadtotal = (int)$positionworkloadtotal;
        $workloadgaugevalue = max(0, min(100, $workloadtotal));
        $workloadgaugetone = 'low';
        if ($workloadtotal >= 80) {
            $workloadgaugetone = 'high';
        } else if ($workloadtotal >= 50) {
            $workloadgaugetone = 'mid';
        }

        return [
            'id' => $position->id,
            'title' => $positiontitleformatted,
            'istopicarea' => $istopicarea,
            'positiontypelabel' => $istopicarea
                ? get_string('positiontype_topicarea', 'local_dutydesk')
                : get_string('positiontype_position', 'local_dutydesk'),
            'headerpatternclass' => $tileclass,
            'taskheaderpatternclass' => \local_dutydesk_get_position_task_header_pattern_class($tileclass),
            'departmentdisplay' => $departmentdisplay,
            'department' => format_string($departmentname),
            'description' => $descriptionrendered,
            'hasdescription' => $descriptionrendered !== '',
            'timestamp' => userdate($position->timestamp),
            'primaryuser' => $primaryuserdisplay,
            'hasprimaryuser' => $hasprimaryuser,
            'deputyuser' => $deputyuserdisplay,
            'hasdeputyuser' => $hasdeputyuser,
            'editurl' => $canmanageposition ? $editurl->out(false) : null,
            'editmodalurl' => $canmanageposition ? $editmodalurl->out(false) : null,
            'deleteurl' => ($canmanageposition && $positionarchived) ? $deleteurl->out(false) : null,
            'canarchive' => $canmanageposition && !$positionarchived,
            'archiveurl' => ($canmanageposition && !$positionarchived) ? $archiveurl->out(false) : null,
            'canrestore' => $canmanageposition && $positionarchived,
            'restoreurl' => ($canmanageposition && $positionarchived) ? $restoreurl->out(false) : null,
            'candelete' => $canmanageposition && $positionarchived,
            'hastasks' => !empty($tasks),
            'tasks' => $previewtasks,
            'overflowtasks' => $overflowtasks,
            'hasoverflow' => $hasoverflow,
            'taskcount' => $taskcount,
            'collapseheight' => 224,
            'canmanage' => $canmanageposition,
            'isarchived' => $positionarchived,
            'hasarchiveddate' => !empty($archiveddate),
            'archiveddate' => $archiveddate,
            'isvacant' => !$istopicarea && !empty($position->isvacant),
            'vacantbadge' => get_string('positionvacantbadge', 'local_dutydesk'),
            'workloadtotal' => $workloadtotal,
            'workloadtotaldisplay' => $workloadtotal . '%',
            'workloadgaugevalue' => $workloadgaugevalue,
            'workloadgaugetone' => $workloadgaugetone,
            'sesskey' => sesskey(),
        ];
    }

    /**
     * Build task template data for a position.
     *
     * @param \stdClass $position
     * @param \context $context
     * @param array $tasksbyposition
     * @param array $subtasksbytask
     * @return array
     */
    private static function build_tasks(
        \stdClass $position,
        \context $context,
        array $tasksbyposition,
        array $subtasksbytask
    ): array {
        $tasks = [];
        $positionworkloadtotal = 0;

        if (empty($tasksbyposition[$position->id])) {
            return [$tasks, $positionworkloadtotal];
        }

        foreach ($tasksbyposition[$position->id] as $taskinfo) {
            $subtasks = self::build_subtasks($position, $context, $taskinfo, $subtasksbytask);
            $workloadvalue = isset($taskinfo->workloadpercent) && $taskinfo->workloadpercent !== null
                ? (int)$taskinfo->workloadpercent
                : null;
            if ($workloadvalue !== null) {
                $positionworkloadtotal += $workloadvalue;
            }

            [$hasdescription, $descriptiondisplay, $descriptionrendered] = self::build_task_description(
                $context,
                $taskinfo
            );

            $taskurl = new \moodle_url('/local/dutydesk/task_modal.php', [
                'taskid' => $taskinfo->taskid,
                'modal' => 1,
            ]);
            $tasks[] = [
                'id' => (int)$taskinfo->taskid,
                'subtaskpanelid' => 'position-task-subtasks-' . (int)$position->id . '-' . (int)$taskinfo->taskid,
                'title' => format_string($taskinfo->tasktitle),
                'description' => $descriptiondisplay,
                'hasdescription' => $hasdescription,
                'descriptionhtml' => $hasdescription ? $descriptionrendered : null,
                'url' => $taskurl->out(false),
                'hasworkload' => $workloadvalue !== null,
                'workloaddisplay' => \local_dutydesk_format_workload_display($workloadvalue),
                'hassubtasks' => !empty($subtasks),
                'hasdetails' => $hasdescription || !empty($subtasks),
                'subtasks' => $subtasks,
            ];
        }

        return [$tasks, $positionworkloadtotal];
    }

    /**
     * Build subtask template data for a task.
     *
     * @param \stdClass $position
     * @param \context $context
     * @param \stdClass $taskinfo
     * @param array $subtasksbytask
     * @return array
     */
    private static function build_subtasks(
        \stdClass $position,
        \context $context,
        \stdClass $taskinfo,
        array $subtasksbytask
    ): array {
        $subtasks = [];
        if (empty($subtasksbytask[(int)$taskinfo->taskid])) {
            return $subtasks;
        }

        foreach ($subtasksbytask[(int)$taskinfo->taskid] as $subtask) {
            $subtaskurl = new \moodle_url('/local/dutydesk/subtask.php', [
                'id' => $subtask->id,
                'taskid' => $taskinfo->taskid,
                'modal' => 1,
            ]);
            $subtaskdescriptionraw = trim((string)($subtask->description ?? ''));
            $subtaskdescriptionformat = isset($subtask->descriptionformat)
                ? (int)$subtask->descriptionformat
                : FORMAT_HTML;
            $subtaskcontenthtml = '';
            if ($subtaskdescriptionraw !== '') {
                $subtaskcontenthtml = trim(\local_dutydesk_format_list_text(
                    $subtaskdescriptionraw,
                    $subtaskdescriptionformat,
                    $context,
                    'subtaskdescription',
                    (int)$subtask->id
                ));
            }
            $subtaskhascontent = $subtaskcontenthtml !== '';
            $subtaskpanelid = 'position-subtask-content-' . (int)$position->id
                . '-' . (int)$taskinfo->taskid . '-' . (int)$subtask->id;
            $subtasks[] = [
                'id' => (int)$subtask->id,
                'title' => format_string($subtask->title),
                'url' => $subtaskurl->out(false),
                'hascontent' => $subtaskhascontent,
                'content' => $subtaskhascontent ? $subtaskcontenthtml : null,
                'contentpanelid' => $subtaskpanelid,
            ];
        }

        return $subtasks;
    }

    /**
     * Build task description preview and rendered HTML.
     *
     * @param \context $context
     * @param \stdClass $taskinfo
     * @return array
     */
    private static function build_task_description(\context $context, \stdClass $taskinfo): array {
        $descriptionraw = trim((string)($taskinfo->taskdescription ?? ''));
        $descriptionformat = isset($taskinfo->taskdescriptionformat)
            ? (int)$taskinfo->taskdescriptionformat
            : FORMAT_HTML;
        $descriptiondisplay = '';
        $hasdescription = false;
        $descriptionrendered = '';

        if ($descriptionraw === '') {
            return [$hasdescription, $descriptiondisplay, $descriptionrendered];
        }

        $descriptionrendered = trim(\local_dutydesk_format_list_text(
            $descriptionraw,
            $descriptionformat,
            $context,
            'taskdescription',
            (int)$taskinfo->taskid
        ));
        $descriptionclean = trim((string)strip_tags($descriptionrendered));
        if ($descriptionclean !== '') {
            $hasdescription = true;
            if (\core_text::strlen($descriptionclean) > 140) {
                $descriptiondisplay = \core_text::substr($descriptionclean, 0, 140) . '...';
            } else {
                $descriptiondisplay = $descriptionclean;
            }
        }

        return [$hasdescription, $descriptiondisplay, $descriptionrendered];
    }
}
