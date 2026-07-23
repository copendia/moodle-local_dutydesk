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

namespace local_dutydesk\local\dashboard;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


use context;
use core_text;
use html_writer;
use moodle_url;

/**
 * Builds template data for the assigned positions dashboard.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presenter {
    /** @var int Maximum number of tasks shown before collapsing overflow tasks. */
    private const TASK_PREVIEW_LIMIT = 5;

    /**
     * Build dashboard render data.
     *
     * @param context $context
     * @param int $userid
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public static function build(context $context, int $userid, int $page, int $perpage): array {
        global $DB;

        $positions = [];
        $positionroles = [];
        $offset = $page * $perpage;
        $listbaseurl = new moodle_url('/local/dutydesk/index.php', ['perpage' => $perpage]);

        $canviewallpositions = has_any_capability(['local/dutydesk:viewall', 'local/dutydesk:manageall'], $context);
        $canviewownpositions = has_capability('local/dutydesk:viewown', $context);
        $manageddepartmentids = \local_dutydesk_get_managed_department_ids($userid);
        $userdepartmentids = \local_dutydesk_get_user_department_ids($userid);

        if ($canviewallpositions) {
            $positions = $DB->get_records('dutydesk_position', null, 'title ASC');
            if ($canviewownpositions) {
                $ownpositionids = \local_dutydesk_get_user_position_ids($userid);
                if (!empty($ownpositionids)) {
                    foreach ($ownpositionids as $positionid) {
                        if (!isset($positions[$positionid])) {
                            continue;
                        }
                        $isprimary = ((int)$positions[$positionid]->primaryuserid === $userid);
                        if ($isprimary) {
                            $positionroles[$positionid]['primary'] = true;
                        } else {
                            $positionroles[$positionid]['deputy'] = true;
                        }
                    }
                }
            }
        } else if ($canviewownpositions) {
            $primarypositions = $DB->get_records('dutydesk_position', ['primaryuserid' => $userid]);
            foreach ($primarypositions as $position) {
                $positions[$position->id] = $position;
                $positionroles[$position->id]['primary'] = true;
            }

            $deputyassignments = $DB->get_records('dutydesk_position_deputy', ['userid' => $userid]);
            if (!empty($deputyassignments)) {
                $deputypositionids = array_map(static function ($record) {
                    return (int)$record->positionid;
                }, $deputyassignments);
                $deputypositionids = array_filter($deputypositionids);

                if (!empty($deputypositionids)) {
                    $deputypositions = $DB->get_records_list('dutydesk_position', 'id', $deputypositionids);
                    foreach ($deputypositions as $position) {
                        $positions[$position->id] = $position;
                        $positionroles[$position->id]['deputy'] = true;
                    }
                }
            }
        }

        if (!empty($manageddepartmentids)) {
            $managedpositions = $DB->get_records_list('dutydesk_position', 'departmentid', $manageddepartmentids);
            foreach ($managedpositions as $position) {
                $positions[$position->id] = $position;
            }
        }

        if (!empty($userdepartmentids)) {
            $departmentpositions = $DB->get_records_list('dutydesk_position', 'departmentid', $userdepartmentids);
            foreach ($departmentpositions as $position) {
                $positions[$position->id] = $position;
            }
        }

        [$positions, $ownpositionids] = self::filter_visible_positions(
            $positions,
            $positionroles,
            $userid,
            $manageddepartmentids,
            $userdepartmentids
        );
        self::sort_positions($positions);

        $positionids = array_keys($positions);
        $departments = self::load_departments($positions);
        [$deputiesbypostion, $users] = self::load_position_users($context, $positions, $positionids);
        [$tasksbyposition, $subtasksbytask] = self::load_tasks($positionids);

        $positiongroups = self::build_position_groups(
            $context,
            $userid,
            $page,
            $perpage,
            $positions,
            $positionroles,
            $ownpositionids,
            $departments,
            $deputiesbypostion,
            $users,
            $tasksbyposition,
            $subtasksbytask
        );

        self::sort_position_groups($positiongroups);

        $allgroups = array_values($positiongroups);
        $totalgroups = count($allgroups);
        if ($page > 0 && $offset >= $totalgroups && $totalgroups > 0) {
            $page = (int)floor(($totalgroups - 1) / $perpage);
            $offset = $page * $perpage;
        }
        $paginatedgroups = array_slice($allgroups, $offset, $perpage);
        $haspositions = $totalgroups > 0;

        return [
            'templatecontext' => self::build_template_context($paginatedgroups, $haspositions),
            'haspositions' => $haspositions,
            'totalgroups' => $totalgroups,
            'page' => $page,
            'perpage' => $perpage,
            'listbaseurl' => $listbaseurl,
        ];
    }

    /**
     * Filter positions visible on the dashboard and return own/deputy ids.
     *
     * @param array $positions
     * @param array $positionroles
     * @param int $userid
     * @param array $manageddepartmentids
     * @param array $userdepartmentids
     * @return array
     */
    private static function filter_visible_positions(
        array $positions,
        array $positionroles,
        int $userid,
        array $manageddepartmentids,
        array $userdepartmentids
    ): array {
        $filteredpositions = [];
        $ownpositionids = [];
        foreach ($positions as $position) {
            $isown = !empty($position->primaryuserid) && (int)$position->primaryuserid === $userid;
            $isdeputy = !empty($positionroles[$position->id]['deputy']);
            $departmentid = !empty($position->departmentid) ? (int)$position->departmentid : 0;
            $ismanagersdepartment = $departmentid > 0 && in_array($departmentid, $manageddepartmentids, true);
            $isdepartmenttopicarea = $departmentid > 0
                && in_array($departmentid, $userdepartmentids, true)
                && \local_dutydesk_position_is_topic_area($position);
            $isuserdepartmentposition = $departmentid > 0
                && in_array($departmentid, $userdepartmentids, true)
                && !\local_dutydesk_position_is_topic_area($position);

            if ($isown || $isdeputy || $ismanagersdepartment || $isdepartmenttopicarea || $isuserdepartmentposition) {
                $filteredpositions[$position->id] = $position;
                if ($isown || $isdeputy) {
                    $ownpositionids[] = (int)$position->id;
                }
            }
        }

        return [
            $filteredpositions,
            array_values(array_unique($ownpositionids)),
        ];
    }

    /**
     * Sort positions by display title.
     *
     * @param array $positions
     * @return void
     */
    private static function sort_positions(array &$positions): void {
        if (empty($positions)) {
            return;
        }

        uasort($positions, static function ($a, $b) {
            $atitle = format_string($a->title ?? '');
            $btitle = format_string($b->title ?? '');
            return strcasecmp($atitle, $btitle);
        });
    }

    /**
     * Load departments referenced by positions.
     *
     * @param array $positions
     * @return array
     */
    private static function load_departments(array $positions): array {
        global $DB;

        $departmentids = [];
        foreach ($positions as $position) {
            if (!empty($position->departmentid)) {
                $departmentids[] = (int)$position->departmentid;
            }
        }

        $departmentids = array_values(array_unique(array_filter($departmentids)));
        if (empty($departmentids)) {
            return [];
        }

        return $DB->get_records_list('dutydesk_department', 'id', $departmentids);
    }

    /**
     * Load primary/deputy users for positions.
     *
     * @param context $context
     * @param array $positions
     * @param array $positionids
     * @return array
     */
    private static function load_position_users(context $context, array $positions, array $positionids): array {
        global $DB;

        $primaryuserids = [];
        foreach ($positions as $position) {
            if (!empty($position->primaryuserid)) {
                $primaryuserids[] = (int)$position->primaryuserid;
            }
        }

        $deputiesbypostion = [];
        $deputyuserids = [];
        if (!empty($positionids)) {
            $alldeputies = $DB->get_records_list('dutydesk_position_deputy', 'positionid', $positionids);
            foreach ($alldeputies as $deputy) {
                $deputiesbypostion[$deputy->positionid] = $deputy;
                if (!empty($deputy->userid)) {
                    $deputyuserids[] = (int)$deputy->userid;
                }
            }
        }

        $userids = array_values(array_unique(array_filter(array_merge($primaryuserids, $deputyuserids))));
        $users = [];
        if (!empty($userids)) {
            $users = $DB->get_records_list('user', 'id', $userids);
        }

        return [$deputiesbypostion, $users];
    }

    /**
     * Load assigned tasks and subtasks for positions.
     *
     * @param array $positionids
     * @return array
     */
    private static function load_tasks(array $positionids): array {
        global $DB;

        $tasksbyposition = [];
        $subtasksbytask = [];
        if (empty($positionids)) {
            return [$tasksbyposition, $subtasksbytask];
        }

        [$insql, $params] = $DB->get_in_or_equal($positionids, SQL_PARAMS_NAMED);
        $tasksql = "SELECT t.*, ta.positionid, ta.workloadpercent
                      FROM {dutydesk_taskassignment} ta
                      JOIN {dutydesk_task} t ON t.id = ta.taskid
                     WHERE ta.positionid {$insql}
                  ORDER BY t.title ASC";
        $taskrecords = $DB->get_records_sql($tasksql, $params);

        $taskids = [];
        foreach ($taskrecords as $taskrecord) {
            $tasksbyposition[$taskrecord->positionid][] = $taskrecord;
            $taskids[] = (int)$taskrecord->id;
        }
        $taskids = array_values(array_unique(array_filter($taskids)));

        if (!empty($taskids)) {
            [$subinsql, $subparams] = $DB->get_in_or_equal($taskids, SQL_PARAMS_NAMED);
            $subtasksql = "SELECT * FROM {dutydesk_subtask}
                            WHERE taskid {$subinsql}
                         ORDER BY sortorder ASC, id ASC";
            $subtaskrecords = $DB->get_records_sql($subtasksql, $subparams);
            foreach ($subtaskrecords as $subtask) {
                $subtasksbytask[$subtask->taskid][] = $subtask;
            }
        }

        return [$tasksbyposition, $subtasksbytask];
    }

    /**
     * Build grouped dashboard positions.
     *
     * @param context $context
     * @param int $userid
     * @param int $page
     * @param int $perpage
     * @param array $positions
     * @param array $positionroles
     * @param array $ownpositionids
     * @param array $departments
     * @param array $deputiesbypostion
     * @param array $users
     * @param array $tasksbyposition
     * @param array $subtasksbytask
     * @return array
     */
    private static function build_position_groups(
        context $context,
        int $userid,
        int $page,
        int $perpage,
        array $positions,
        array $positionroles,
        array $ownpositionids,
        array $departments,
        array $deputiesbypostion,
        array $users,
        array $tasksbyposition,
        array $subtasksbytask
    ): array {
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $positiongroups = [];
        $nodeputystring = get_string('nodeputy', 'local_dutydesk');
        $notassignedstring = get_string('notassigned', 'local_dutydesk');
        $cannavigatetodepartment = \local_dutydesk_show_departments_tab($userid);

        foreach ($positions as $position) {
            if (!empty($position->archived)) {
                continue;
            }
            $positiondata = self::build_position_data(
                $context,
                $userid,
                $page,
                $perpage,
                $position,
                $positionroles,
                $ownpositionids,
                $departments,
                $deputiesbypostion,
                $users,
                $tasksbyposition,
                $subtasksbytask,
                $canviewfullnames,
                $nodeputystring,
                $notassignedstring,
                $cannavigatetodepartment
            );
            $groupkey = $positiondata['groupkey'];
            if (!isset($positiongroups[$groupkey])) {
                $positiongroups[$groupkey] = $positiondata['group'];
            }
            $positiongroups[$groupkey]['positions'][] = $positiondata['position'];

            if ($positiondata['position']['isown']) {
                $positiongroups[$groupkey]['sortpriority'] = 0;
            } else if ($positiongroups[$groupkey]['sortpriority'] > 1 && $positiondata['ismanagerdepartment']) {
                $positiongroups[$groupkey]['sortpriority'] = 1;
            }
        }

        return $positiongroups;
    }

    /**
     * Build one position entry and its group metadata.
     *
     * @param context $context
     * @param int $userid
     * @param int $page
     * @param int $perpage
     * @param object $position
     * @param array $positionroles
     * @param array $ownpositionids
     * @param array $departments
     * @param array $deputiesbypostion
     * @param array $users
     * @param array $tasksbyposition
     * @param array $subtasksbytask
     * @param bool $canviewfullnames
     * @param string $nodeputystring
     * @param string $notassignedstring
     * @param bool $cannavigatetodepartment
     * @return array
     */
    private static function build_position_data(
        context $context,
        int $userid,
        int $page,
        int $perpage,
        object $position,
        array $positionroles,
        array $ownpositionids,
        array $departments,
        array $deputiesbypostion,
        array $users,
        array $tasksbyposition,
        array $subtasksbytask,
        bool $canviewfullnames,
        string $nodeputystring,
        string $notassignedstring,
        bool $cannavigatetodepartment
    ): array {
        $istopicarea = \local_dutydesk_position_is_topic_area($position);
        $positionid = (int)$position->id;
        $roles = $positionroles[$positionid] ?? [];
        $isownposition = in_array($positionid, $ownpositionids, true);
        $departmentid = !empty($position->departmentid) ? (int)$position->departmentid : 0;
        $departmentname = $departments[$departmentid]->name ?? get_string('nodepartment', 'local_dutydesk');
        $departmentplain = format_string($departmentname);
        $departmentdisplay = self::build_department_display($departmentid, $departmentplain, $cannavigatetodepartment);
        $groupkey = $departmentid ?: 'nodepartment';

        $rolebadges = self::build_role_badges($roles, $departmentid, $userid);
        $primaryuserdisplay = self::build_primary_user_display(
            $position,
            $users,
            $canviewfullnames,
            $notassignedstring
        );
        [$deputyuserdisplay, $hasdeputy] = self::build_deputy_user_display(
            $positionid,
            $deputiesbypostion,
            $users,
            $canviewfullnames,
            $nodeputystring
        );

        $positiontitle = format_string($position->title);
        $positiondisplay = self::build_position_display($positionid, $positiontitle);
        [$tasks, $positionworkloadtotal] = self::build_tasks(
            $context,
            $userid,
            $positionid,
            $position,
            $positiontitle,
            $positiondisplay,
            $departmentplain,
            $departmentdisplay,
            $primaryuserdisplay,
            $deputyuserdisplay,
            $tasksbyposition[$positionid] ?? [],
            $subtasksbytask
        );

        $taskcount = count($tasks);
        $previewtasks = $taskcount > 0 ? array_slice($tasks, 0, self::TASK_PREVIEW_LIMIT) : [];
        $overflowtasks = $taskcount > self::TASK_PREVIEW_LIMIT ? array_slice($tasks, self::TASK_PREVIEW_LIMIT) : [];
        $hasoverflow = !empty($overflowtasks);
        $positionworkloadtotal = (int)$positionworkloadtotal;
        $workloadgaugevalue = max(0, min(100, $positionworkloadtotal));
        $workloadgaugetone = self::get_workload_tone($positionworkloadtotal);
        $topicdescription = self::build_topic_description($context, $position, $istopicarea);
        $tileclass = \local_dutydesk_get_position_tile_pattern_class($positionid, $positiontitle);

        return [
            'groupkey' => $groupkey,
            'group' => [
                'id' => $departmentid,
                'name' => $departmentdisplay,
                'isnodepartment' => $departmentid === 0,
                'positions' => [],
                'sortname' => core_text::strtolower($departmentplain),
                'sortpriority' => 2,
            ],
            'ismanagerdepartment' => \local_dutydesk_user_manages_department($departmentid, $userid),
            'position' => [
                'id' => $positionid,
                'title' => $positiontitle,
                'titledisplay' => $positiondisplay,
                'sortpriority' => $isownposition ? 0 : ($istopicarea ? 1 : 2),
                'sortname' => core_text::strtolower($positiontitle),
                'istopicarea' => $istopicarea,
                'positiontypelabel' => $istopicarea
                    ? get_string('positiontype_topicarea', 'local_dutydesk')
                    : get_string('positiontype_position', 'local_dutydesk'),
                'topicdescription' => $topicdescription,
                'hastopicdescription' => $topicdescription !== '',
                'isvacant' => !$istopicarea && !empty($position->isvacant),
                'canmarkvacant' => !$istopicarea && \local_dutydesk_user_manages_department($departmentid, $userid),
                'vacanttoggleurl' => (new moodle_url('/local/dutydesk/index.php', [
                    'page' => $page,
                    'perpage' => $perpage,
                ]))->out(false),
                'vacantlabel' => get_string('positionvacantlabel', 'local_dutydesk'),
                'vacanthelp' => get_string('positionvacanthelp', 'local_dutydesk'),
                'sesskey' => sesskey(),
                'roles' => $rolebadges,
                'hasroles' => !empty($rolebadges),
                'primaryuser' => $primaryuserdisplay,
                'hasdeputy' => $hasdeputy,
                'deputyuser' => $deputyuserdisplay,
                'tasks' => $previewtasks,
                'overflowtasks' => $overflowtasks,
                'hasoverflow' => $hasoverflow,
                'overflowcount' => count($overflowtasks),
                'hastasks' => !empty($tasks),
                'taskcount' => $taskcount,
                'collapseheight' => 360,
                'department' => $departmentplain,
                'departmentdisplay' => $departmentdisplay,
                'isown' => $isownposition,
                'tileclass' => $tileclass,
                'workloadtotal' => $positionworkloadtotal,
                'workloadtotaldisplay' => $positionworkloadtotal . '%',
                'workloadgaugevalue' => $workloadgaugevalue,
                'workloadgaugetone' => $workloadgaugetone,
            ],
        ];
    }

    /**
     * Build department display HTML.
     *
     * @param int $departmentid
     * @param string $departmentplain
     * @param bool $cannavigate
     * @return string
     */
    private static function build_department_display(int $departmentid, string $departmentplain, bool $cannavigate): string {
        if ($departmentid <= 0 || !$cannavigate) {
            return $departmentplain;
        }

        $departmenturl = new moodle_url('/local/dutydesk/departments.php', [
            'focus' => $departmentid,
        ]);
        $departmenturl->set_anchor('department-' . $departmentid);
        return html_writer::link($departmenturl, $departmentplain);
    }

    /**
     * Build role badges for a position.
     *
     * @param array $roles
     * @param int $departmentid
     * @param int $userid
     * @return array
     */
    private static function build_role_badges(array $roles, int $departmentid, int $userid): array {
        $rolebadges = [];
        if (!empty($roles['primary'])) {
            $rolebadges[] = ['label' => get_string('mypositions_role_primary', 'local_dutydesk')];
        }
        if (!empty($roles['deputy'])) {
            $rolebadges[] = ['label' => get_string('mypositions_role_deputy', 'local_dutydesk')];
        }
        if (\local_dutydesk_user_manages_department($departmentid, $userid)) {
            $rolebadges[] = ['label' => get_string('mypositions_role_manager', 'local_dutydesk')];
        }

        return $rolebadges;
    }

    /**
     * Build primary user display HTML.
     *
     * @param object $position
     * @param array $users
     * @param bool $canviewfullnames
     * @param string $fallback
     * @return string
     */
    private static function build_primary_user_display(
        object $position,
        array $users,
        bool $canviewfullnames,
        string $fallback
    ): string {
        if (empty($position->primaryuserid) || !isset($users[$position->primaryuserid])) {
            return $fallback;
        }

        $primaryuserrecord = $users[$position->primaryuserid];
        $primaryname = fullname($primaryuserrecord, $canviewfullnames);
        $profileurl = new moodle_url('/user/profile.php', ['id' => $primaryuserrecord->id]);
        return html_writer::link($profileurl, $primaryname);
    }

    /**
     * Build deputy display HTML.
     *
     * @param int $positionid
     * @param array $deputiesbypostion
     * @param array $users
     * @param bool $canviewfullnames
     * @param string $fallback
     * @return array
     */
    private static function build_deputy_user_display(
        int $positionid,
        array $deputiesbypostion,
        array $users,
        bool $canviewfullnames,
        string $fallback
    ): array {
        $deputy = $deputiesbypostion[$positionid] ?? null;
        if (!$deputy || empty($deputy->userid) || !isset($users[$deputy->userid])) {
            return [$fallback, false];
        }

        $deputyrecord = $users[$deputy->userid];
        $deputyname = fullname($deputyrecord, $canviewfullnames);
        $profileurl = new moodle_url('/user/profile.php', ['id' => $deputyrecord->id]);
        return [html_writer::link($profileurl, $deputyname), true];
    }

    /**
     * Build position title display HTML.
     *
     * @param int $positionid
     * @param string $positiontitle
     * @return string
     */
    private static function build_position_display(int $positionid, string $positiontitle): string {
        if ($positionid <= 0) {
            return $positiontitle;
        }

        $positionurl = new moodle_url('/local/dutydesk/positions.php', [
            'focus' => $positionid,
            'positionid' => $positionid,
        ]);
        $positionurl->set_anchor('position-' . $positionid);
        return html_writer::link($positionurl, $positiontitle);
    }

    /**
     * Build task entries for one position.
     *
     * @param context $context
     * @param int $userid
     * @param int $positionid
     * @param object $position
     * @param string $positiontitle
     * @param string $positiondisplay
     * @param string $departmentplain
     * @param string $departmentdisplay
     * @param string $primaryuserdisplay
     * @param string $deputyuserdisplay
     * @param array $positiontasks
     * @param array $subtasksbytask
     * @return array
     */
    private static function build_tasks(
        context $context,
        int $userid,
        int $positionid,
        object $position,
        string $positiontitle,
        string $positiondisplay,
        string $departmentplain,
        string $departmentdisplay,
        string $primaryuserdisplay,
        string $deputyuserdisplay,
        array $positiontasks,
        array $subtasksbytask
    ): array {
        $tasks = [];
        $positionworkloadtotal = 0;
        foreach ($positiontasks as $task) {
            $taskdescription = self::render_stored_text(
                (string)($task->description ?? ''),
                property_exists($task, 'descriptionformat') ? (int)$task->descriptionformat : FORMAT_HTML,
                $context,
                'taskdescription',
                (int)$task->id
            );
            $taskdocuments = self::build_documents($context, 'taskdocuments', (int)$task->id);
            $subtasks = self::build_subtasks($context, $userid, $task, $subtasksbytask[$task->id] ?? []);
            $canedittask = \local_dutydesk_user_can_edit_task((int)$task->id, $userid);
            $workloadvalue = property_exists($task, 'workloadpercent') && $task->workloadpercent !== null
                ? (int)$task->workloadpercent
                : null;
            if ($workloadvalue !== null) {
                $positionworkloadtotal += $workloadvalue;
            }

            $tasks[] = [
                'id' => (int)$task->id,
                'title' => format_string($task->title),
                'timestamp' => !empty($task->timestamp) ? userdate($task->timestamp) : '',
                'hasdescription' => $taskdescription !== '',
                'description' => $taskdescription,
                'hasdocuments' => !empty($taskdocuments),
                'documents' => $taskdocuments,
                'assigneduser' => $primaryuserdisplay,
                'deputyuser' => $deputyuserdisplay,
                'assignedposition' => $positiontitle,
                'assignedpositiondisplay' => $positiondisplay,
                'department' => $departmentplain,
                'departmentdisplay' => $departmentdisplay,
                'hassubtasks' => !empty($subtasks),
                'subtaskcount' => count($subtasks),
                'subtasks' => $subtasks,
                'panelid' => 'assigned-task-panel-' . $task->id,
                'toggleid' => 'assigned-task-toggle-' . $task->id,
                'canedit' => $canedittask,
                'editurl' => $canedittask ? (new moodle_url('/local/dutydesk/tasks.php', ['id' => $task->id]))->out(false) : null,
                'hasworkload' => $workloadvalue !== null,
                'workloaddisplay' => \local_dutydesk_format_workload_display($workloadvalue),
                'isvacant' => !empty($position->isvacant),
                'vacanttasklabel' => get_string('taskvacantbadge', 'local_dutydesk'),
            ];
        }

        return [$tasks, $positionworkloadtotal];
    }

    /**
     * Build subtasks for a task.
     *
     * @param context $context
     * @param int $userid
     * @param object $task
     * @param array $tasksubtasks
     * @return array
     */
    private static function build_subtasks(context $context, int $userid, object $task, array $tasksubtasks): array {
        if (!empty($tasksubtasks)) {
            usort($tasksubtasks, static function ($a, $b) {
                $aorder = property_exists($a, 'sortorder') && $a->sortorder !== null
                    ? (int)$a->sortorder
                    : PHP_INT_MAX;
                $border = property_exists($b, 'sortorder') && $b->sortorder !== null
                    ? (int)$b->sortorder
                    : PHP_INT_MAX;
                if ($aorder === $border) {
                    return $a->id <=> $b->id;
                }
                return $aorder <=> $border;
            });
        }

        $subtasks = [];
        foreach ($tasksubtasks as $subtask) {
            $subtaskdescription = self::render_stored_text(
                (string)($subtask->description ?? ''),
                property_exists($subtask, 'descriptionformat') ? (int)$subtask->descriptionformat : FORMAT_HTML,
                $context,
                'subtaskdescription',
                (int)$subtask->id
            );
            $subtaskdocuments = self::build_documents($context, 'subtaskdocuments', (int)$subtask->id);
            $subtaskcanedit = \local_dutydesk_user_can_edit_subtask((int)$subtask->id, $userid);

            $subtasks[] = [
                'id' => (int)$subtask->id,
                'title' => format_string($subtask->title),
                'hasdescription' => $subtaskdescription !== '',
                'description' => $subtaskdescription,
                'hasdocuments' => !empty($subtaskdocuments),
                'documents' => $subtaskdocuments,
                'panelid' => 'assigned-subtask-panel-' . $subtask->id,
                'toggleid' => 'assigned-subtask-toggle-' . $subtask->id,
                'documentnames' => array_column($subtaskdocuments, 'name'),
                'canedit' => $subtaskcanedit,
                'editurl' => $subtaskcanedit
                    ? (new moodle_url('/local/dutydesk/subtask.php', [
                        'taskid' => $task->id,
                        'id' => $subtask->id,
                    ]))->out(false)
                    : null,
            ];
        }

        return $subtasks;
    }

    /**
     * Build file entries for a plugin file area.
     *
     * @param context $context
     * @param string $filearea
     * @param int $itemid
     * @return array
     */
    private static function build_documents(context $context, string $filearea, int $itemid): array {
        $documents = [];
        $files = get_file_storage()->get_area_files(
            $context->id,
            'local_dutydesk',
            $filearea,
            $itemid,
            'filename',
            false
        );
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

        return $documents;
    }

    /**
     * Render stored rich text for list display.
     *
     * @param string $text
     * @param int $format
     * @param context $context
     * @param string $filearea
     * @param int $itemid
     * @return string
     */
    private static function render_stored_text(
        string $text,
        int $format,
        context $context,
        string $filearea,
        int $itemid
    ): string {
        return \local_dutydesk_format_list_text($text, $format, $context, $filearea, $itemid);
    }

    /**
     * Get workload gauge tone.
     *
     * @param int $positionworkloadtotal
     * @return string
     */
    private static function get_workload_tone(int $positionworkloadtotal): string {
        if ($positionworkloadtotal >= 80) {
            return 'high';
        }
        if ($positionworkloadtotal >= 50) {
            return 'mid';
        }

        return 'low';
    }

    /**
     * Build topic area teaser text.
     *
     * @param context $context
     * @param object $position
     * @param bool $istopicarea
     * @return string
     */
    private static function build_topic_description(context $context, object $position, bool $istopicarea): string {
        if (!$istopicarea || trim((string)($position->description ?? '')) === '') {
            return '';
        }

        $topicdescription = trim((string)html_to_text(
            \local_dutydesk_format_list_text((string)$position->description, FORMAT_HTML, $context),
            0
        ));
        if (core_text::strlen($topicdescription) > 180) {
            $topicdescription = core_text::substr($topicdescription, 0, 180) . '...';
        }

        return $topicdescription;
    }

    /**
     * Sort position groups and positions inside each group.
     *
     * @param array $positiongroups
     * @return void
     */
    private static function sort_position_groups(array &$positiongroups): void {
        foreach ($positiongroups as &$positiongroup) {
            usort($positiongroup['positions'], static function ($a, $b) {
                $prioritycompare = ((int)($a['sortpriority'] ?? 2)) <=> ((int)($b['sortpriority'] ?? 2));
                if ($prioritycompare !== 0) {
                    return $prioritycompare;
                }

                return strcasecmp((string)($a['sortname'] ?? ''), (string)($b['sortname'] ?? ''));
            });
        }
        unset($positiongroup);

        uasort($positiongroups, static function ($a, $b) {
            $prioritycompare = ((int)($a['sortpriority'] ?? 2)) <=> ((int)($b['sortpriority'] ?? 2));
            if ($prioritycompare !== 0) {
                return $prioritycompare;
            }

            return strcasecmp((string)($a['sortname'] ?? ''), (string)($b['sortname'] ?? ''));
        });
    }

    /**
     * Build final template context.
     *
     * @param array $paginatedgroups
     * @param bool $haspositions
     * @return array
     */
    private static function build_template_context(array $paginatedgroups, bool $haspositions): array {
        return [
            'haspositions' => $haspositions,
            'departments' => array_map(static function ($group) {
                $group['haspositions'] = !empty($group['positions']);
                $own = array_values(array_filter($group['positions'], static function ($position) {
                    return !empty($position['isown']) || !empty($position['istopicarea']);
                }));
                $managed = array_values(array_filter($group['positions'], static function ($position) {
                    return empty($position['isown']) && empty($position['istopicarea']);
                }));

                $group['ownpositions'] = $own;
                $group['hasownpositions'] = !empty($own);
                $group['hasmanagedpositions'] = !empty($managed);
                $group['managedpositions'] = $managed;
                unset($group['sortname']);
                unset($group['sortpriority']);
                return $group;
            }, $paginatedgroups),
            'nodepartments' => !$haspositions,
            'noassignedpositions' => get_string('noassignedpositions', 'local_dutydesk'),
        ];
    }
}
