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

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

if (!defined('LOCAL_DUTYDESK_DEFAULT_PERPAGE')) {
    define('LOCAL_DUTYDESK_DEFAULT_PERPAGE', 6);
}
if (!defined('LOCAL_DUTYDESK_POSITION_TYPE_POSITION')) {
    define('LOCAL_DUTYDESK_POSITION_TYPE_POSITION', 'position');
}
if (!defined('LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA')) {
    define('LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA', 'topicarea');
}

/**
 * Normalize a stored or submitted position type.
 *
 * @param string|null $positiontype
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_normalize_position_type(?string $positiontype): string {
    $positiontype = trim((string)$positiontype);
    if ($positiontype === LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA) {
        return LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA;
    }

    return LOCAL_DUTYDESK_POSITION_TYPE_POSITION;
}

/**
 * Whether a position record represents a topic area.
 *
 * @param stdClass|array $position
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_position_is_topic_area($position): bool {
    $positiontype = is_array($position)
        ? ($position['positiontype'] ?? null)
        : ($position->positiontype ?? null);

    return local_dutydesk_normalize_position_type($positiontype) === LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA;
}

/**
 * Clamp the per-page value to a sensible range.
 *
 * @param int $perpage
 * @param int $max
 * @return int
 * @package local_dutydesk
 */
function local_dutydesk_normalize_perpage(int $perpage, int $max = 100): int {
    if ($perpage < 1) {
        return LOCAL_DUTYDESK_DEFAULT_PERPAGE;
    }

    if ($perpage > $max) {
        return $max;
    }

    return $perpage;
}

/**
 * Remove media loader scripts that are unsafe when rich text is embedded in lists.
 *
 * Moodle may inject media_videojs setup scripts while rendering formatted text.
 * In repeated list contexts these scripts can reference missing elements and
 * abort unrelated frontend initialization such as modals.
 *
 * @param string $html
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_strip_media_loader_scripts(string $html): string {
    $pattern = '/<script[^>]*>\s*require\(\s*[\'"]media_videojs\/loader[\'"]\s*,\s*function\s*'
        . '\(\s*loader\s*\)\s*{\s*loader\.setUp\([^)]*\);\s*}\s*\);\s*<\/script>\s*/is';

    return (string)preg_replace(
        $pattern,
        '',
        $html
    );
}

/**
 * Render stored rich text safely inside repeated list/card views.
 *
 * List views may render many task and position descriptions at once. Running the
 * full Moodle filter chain there can initialise media players repeatedly and
 * break unrelated AMD initialisation. File URLs are still rewritten, but filters
 * such as VideoJS are intentionally disabled for these summaries.
 *
 * @param string $text Raw database text.
 * @param int $format Moodle text format.
 * @param context $context Rendering context.
 * @param string|null $filearea Optional plugin file area.
 * @param int|null $itemid Optional item id for pluginfile URLs.
 * @return string Rendered HTML.
 * @package local_dutydesk
 */
function local_dutydesk_format_list_text(
    string $text,
    int $format,
    context $context,
    ?string $filearea = null,
    ?int $itemid = null
): string {
    global $CFG;

    $trimmed = trim($text);
    if ($trimmed === '') {
        return '';
    }

    if ($filearea !== null && $itemid !== null) {
        require_once($CFG->libdir . '/filelib.php');
        $trimmed = file_rewrite_pluginfile_urls(
            $trimmed,
            'pluginfile.php',
            $context->id,
            'local_dutydesk',
            $filearea,
            $itemid
        );
    }

    $trimmed = local_dutydesk_strip_media_loader_scripts($trimmed);

    return local_dutydesk_strip_media_loader_scripts((string)format_text(
        $trimmed,
        $format,
        [
            'context' => $context,
            'filter' => false,
        ]
    ));
}

/**
 * Whether department leads additionally need the Moodle manager role/capabilities.
 *
 * This legacy coupling remains available for future reactivation, but is
 * currently disabled so that the department manager assignment alone is enough.
 *
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_department_lead_requires_manager_role(): bool {
    return false;
}

/**
 * Add the DutyDesk dashboard entry to the global navigation when allowed.
 *
 * @param global_navigation $nav
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_extend_navigation(global_navigation $nav) {
    global $CFG;

    $context = context_system::instance();
    if (!has_capability('local/dutydesk:viewmydepartment', $context)) {
        return;
    }

    $label = get_string('mydepartment', 'local_dutydesk');
    $entry = $label . ' | /local/dutydesk/index.php';
    $currentmenu = $CFG->custommenuitems ?? '';

    if (strpos($currentmenu, $entry) !== false) {
        return;
    }

    $separator = $currentmenu !== '' && substr($currentmenu, -1) !== "\n" ? "\n" : '';
    $CFG->custommenuitems = $currentmenu . $separator . $entry;
}

/**
 * Return the position ids a user is responsible for (primary or deputy).
 *
 * @param int $userid
 * @return int[]
 * @package local_dutydesk
 */
function local_dutydesk_get_user_position_ids(int $userid): array {
    global $DB;

    $primary = $DB->get_fieldset_select(
        'local_dutydesk_position',
        'id',
        'primaryuserid = ? AND COALESCE(archived, 0) = 0',
        [$userid]
    ) ?? [];

    $deputy = $DB->get_fieldset_sql(
        "SELECT da.positionid
           FROM {local_dutydesk_posdeputy} da
           JOIN {local_dutydesk_position} p ON p.id = da.positionid
          WHERE da.userid = :userid
            AND COALESCE(p.archived, 0) = 0",
        ['userid' => $userid]
    ) ?? [];

    $ids = array_merge($primary, $deputy);
    $ids = array_map('intval', $ids);

    return array_values(array_unique($ids));
}

/**
 * Departments a user belongs to based on their positions or department role.
 *
 * @param int $userid
 * @return int[]
 * @package local_dutydesk
 */
function local_dutydesk_get_user_department_ids(int $userid): array {
    global $DB;

    $positionids = local_dutydesk_get_user_position_ids($userid);
    $manageddepartmentids = local_dutydesk_get_managed_department_ids($userid);
    if (empty($positionids)) {
        $manageddepartmentids = array_map('intval', $manageddepartmentids);
        return array_values(array_unique($manageddepartmentids));
    }

    [$insql, $params] = $DB->get_in_or_equal($positionids, SQL_PARAMS_NAMED);
    $departmentids = $DB->get_fieldset_sql(
        "SELECT DISTINCT departmentid
           FROM {local_dutydesk_position}
          WHERE id {$insql}
            AND departmentid IS NOT NULL
            AND COALESCE(archived, 0) = 0",
        $params
    ) ?? [];

    if (!empty($manageddepartmentids)) {
        $departmentids = array_merge($departmentids, $manageddepartmentids);
    }

    $departmentids = array_map('intval', $departmentids);
    $departmentids = array_filter($departmentids, static function ($value) {
        return $value > 0;
    });

    return array_values(array_unique($departmentids));
}

/**
 * Check whether a user is part of a department through any position.
 *
 * @param int $departmentid
 * @param int $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_in_department(int $departmentid, int $userid): bool {
    if ($departmentid <= 0) {
        return false;
    }
    $deptids = local_dutydesk_get_user_department_ids($userid);
    return in_array($departmentid, $deptids, true);
}

/**
 * Determine whether the department navigation tab should be shown.
 *
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_show_departments_tab(?int $userid = null): bool {
    global $USER;

    $userid = $userid ?? ($USER->id ?? 0);
    if (empty($userid)) {
        return false;
    }
    $context = context_system::instance();
    if (!empty(local_dutydesk_get_managed_department_ids($userid))) {
        return true;
    }
    return has_any_capability(
        ['local/dutydesk:managepositions', 'local/dutydesk:manageall', 'local/dutydesk:viewall'],
        $context,
        $userid
    );
}

/**
 * Determine whether the archived positions tab should be shown.
 *
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_show_archived_positions_tab(?int $userid = null): bool {
    global $USER;

    $userid = $userid ?? ($USER->id ?? 0);
    if (empty($userid)) {
        return false;
    }

    $context = context_system::instance();
    if (
        has_any_capability(
            ['local/dutydesk:managepositions', 'local/dutydesk:manageall', 'local/dutydesk:viewall'],
            $context,
            $userid
        )
    ) {
        return true;
    }

    return !empty(local_dutydesk_get_managed_department_ids($userid));
}

/**
 * Return department ids a user manages.
 *
 * @param int $userid
 * @return int[]
 * @package local_dutydesk
 */
function local_dutydesk_get_managed_department_ids(int $userid): array {
    global $DB;

    $ids = $DB->get_fieldset_select('local_dutydesk_deptmgr', 'departmentid', 'userid = ?', [$userid]) ?? [];
    $ids = array_map('intval', $ids);

    return array_values(array_unique($ids));
}

/**
 * Determine whether the user may use department-lead management features.
 *
 * @param int $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_manage_departments(int $userid): bool {
    $context = context_system::instance();

    if (has_any_capability(['local/dutydesk:manageall', 'local/dutydesk:managepositions'], $context, $userid)) {
        return true;
    }

    if (local_dutydesk_department_lead_requires_manager_role()) {
        return false;
    }

    return !empty(local_dutydesk_get_managed_department_ids($userid));
}

/**
 * Determine whether the user may access department-wide Dutydesk views.
 *
 * @param int $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_view_department_scope(int $userid): bool {
    $context = context_system::instance();

    if (
        has_any_capability(
            ['local/dutydesk:viewall', 'local/dutydesk:manageall', 'local/dutydesk:managepositions'],
            $context,
            $userid
        )
    ) {
        return true;
    }

    if (local_dutydesk_department_lead_requires_manager_role()) {
        return false;
    }

    return !empty(local_dutydesk_get_managed_department_ids($userid));
}

/**
 * Determine if a user manages a specific department.
 *
 * @param int $departmentid
 * @param int $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_manages_department(int $departmentid, int $userid): bool {
    if ($departmentid <= 0) {
        return false;
    }

    $context = context_system::instance();
    if (has_capability('local/dutydesk:manageall', $context, $userid)) {
        return true;
    }

    $managed = local_dutydesk_get_managed_department_ids($userid);
    return in_array($departmentid, $managed, true);
}

/**
 * Positions a user can manage (own positions plus those in managed departments).
 *
 * @param int $userid
 * @return int[]
 * @package local_dutydesk
 */
function local_dutydesk_get_manageable_position_ids(int $userid): array {
    global $DB;

    $context = context_system::instance();
    if (has_capability('local/dutydesk:manageall', $context, $userid)) {
        $all = $DB->get_fieldset_select('local_dutydesk_position', 'id', '1 = 1', []);
        return array_map('intval', $all ?? []);
    }

    $positionids = local_dutydesk_get_user_position_ids($userid);

    if (local_dutydesk_user_can_manage_departments($userid)) {
        $manageddepartments = local_dutydesk_get_managed_department_ids($userid);
        if (!empty($manageddepartments)) {
            [$insql, $params] = $DB->get_in_or_equal($manageddepartments, SQL_PARAMS_NAMED);
            $managedpositions = $DB->get_fieldset_sql(
                "SELECT id FROM {local_dutydesk_position} WHERE departmentid {$insql}",
                $params
            );
            if (!empty($managedpositions)) {
                $positionids = array_merge($positionids, array_map('intval', $managedpositions));
            }
        }
    }

    return array_values(array_unique(array_map('intval', $positionids)));
}

/**
 * Persist department manager assignments.
 *
 * @param int $departmentid
 * @param int[] $managerids
 * @param int $assignedby
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_set_department_managers(int $departmentid, array $managerids, int $assignedby): void {
    global $DB;

    $managerids = array_values(array_unique(array_map('intval', array_filter($managerids, static function ($value) {
        return $value > 0;
    }))));

    $existing = $DB->get_records('local_dutydesk_deptmgr', ['departmentid' => $departmentid], '', 'id, userid');
    $existingids = array_map(static function ($record) {
        return (int)$record->userid;
    }, $existing ?: []);

    $toadd = array_diff($managerids, $existingids);
    $toremove = array_diff($existingids, $managerids);

    foreach ($toadd as $userid) {
        $record = (object) [
            'departmentid' => $departmentid,
            'userid' => $userid,
            'assignedby' => $assignedby,
            'timecreated' => time(),
        ];
        $DB->insert_record('local_dutydesk_deptmgr', $record);
    }

    if (!empty($toremove)) {
        [$insql, $params] = $DB->get_in_or_equal($toremove, SQL_PARAMS_NAMED);
        $params['departmentid'] = $departmentid;
        $DB->delete_records_select('local_dutydesk_deptmgr', "departmentid = :departmentid AND userid {$insql}", $params);
    }
}

/**
 * Require that the user has at least one capability from the list.
 *
 * @param array $capabilities
 * @param context $context
 * @param int|null $userid
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_require_any_capability(array $capabilities, context $context, ?int $userid = null): void {
    global $USER;

    $userid = $userid ?? (int)$USER->id;
    foreach ($capabilities as $capability) {
        if (has_capability($capability, $context, $userid)) {
            return;
        }
    }

    // Throw exception using the first capability as reference for messaging.
    throw new required_capability_exception($context, reset($capabilities), 'nopermissions', 'local_dutydesk');
}

/**
 * Determine whether the given user can access a specific position.
 *
 * @param int $positionid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_access_position(int $positionid, ?int $userid = null): bool {
    global $USER;

    $userid = $userid ?? (int)$USER->id;
    $context = context_system::instance();

    if (has_any_capability(['local/dutydesk:viewall', 'local/dutydesk:manageall'], $context, $userid)) {
        return true;
    }

    if (!has_capability('local/dutydesk:viewown', $context, $userid)) {
        return false;
    }

    $positions = local_dutydesk_get_user_position_ids($userid);
    return in_array($positionid, $positions, true);
}

/**
 * Check whether the user can view the specified task.
 *
 * @param int $taskid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_view_task(int $taskid, ?int $userid = null): bool {
    global $USER, $DB;

    $userid = $userid ?? (int)$USER->id;
    $context = context_system::instance();
    $assignment = $DB->get_record_sql(
        "SELECT ta.positionid, p.departmentid
           FROM {local_dutydesk_taskassign} ta
           JOIN {local_dutydesk_position} p ON p.id = ta.positionid
          WHERE ta.taskid = :taskid",
        ['taskid' => $taskid]
    );

    if (has_any_capability(['local/dutydesk:viewall', 'local/dutydesk:manageall'], $context, $userid)) {
        return true;
    }

    if ($assignment && local_dutydesk_user_can_manage_departments($userid)) {
        if (local_dutydesk_user_manages_department((int)$assignment->departmentid, $userid)) {
            return true;
        }
    }

    if (!has_capability('local/dutydesk:viewown', $context, $userid)) {
        return false;
    }

    if (!$assignment) {
        return false;
    }

    if (local_dutydesk_user_in_department((int)$assignment->departmentid, $userid)) {
        return true;
    }

    $positions = local_dutydesk_get_user_position_ids($userid);
    if (empty($positions)) {
        return false;
    }

    return in_array((int)$assignment->positionid, $positions, true);
}

/**
 * Check whether the user can edit the specified task.
 *
 * @param int $taskid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_edit_task(int $taskid, ?int $userid = null): bool {
    global $USER;

    $userid = $userid ?? (int)$USER->id;
    $context = context_system::instance();

    if (has_capability('local/dutydesk:manageall', $context, $userid)) {
        return true;
    }

    if (local_dutydesk_user_can_manage_departments($userid)) {
        global $DB;
        $assignment = $DB->get_record_sql(
            "SELECT ta.positionid, p.departmentid
               FROM {local_dutydesk_taskassign} ta
               JOIN {local_dutydesk_position} p ON p.id = ta.positionid
              WHERE ta.taskid = :taskid",
            ['taskid' => $taskid]
        );
        if ($assignment && local_dutydesk_user_manages_department((int)$assignment->departmentid, $userid)) {
            return true;
        }
    }

    if (!has_capability('local/dutydesk:manageown', $context, $userid)) {
        return false;
    }

    return local_dutydesk_user_can_view_task($taskid, $userid);
}

/**
 * Check whether the user can edit the specified subtask.
 *
 * @param int $subtaskid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_edit_subtask(int $subtaskid, ?int $userid = null): bool {
    global $DB;

    $taskid = $DB->get_field('local_dutydesk_subtask', 'taskid', ['id' => $subtaskid]);
    if (!$taskid) {
        return false;
    }

    return local_dutydesk_user_can_edit_task((int)$taskid, $userid);
}

/**
 * Check whether the user may view task activity history.
 * Only department managers and admins are allowed.
 *
 * @param int $taskid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_view_task_history(int $taskid, ?int $userid = null): bool {
    global $DB, $USER;

    $userid = $userid ?? (int)$USER->id;
    $context = context_system::instance();

    if (has_capability('local/dutydesk:manageall', $context, $userid)) {
        return true;
    }

    if (!local_dutydesk_user_can_manage_departments($userid)) {
        return false;
    }

    $departmentid = (int)$DB->get_field_sql(
        "SELECT p.departmentid
           FROM {local_dutydesk_taskassign} ta
           JOIN {local_dutydesk_position} p ON p.id = ta.positionid
          WHERE ta.taskid = :taskid",
        ['taskid' => $taskid]
    );

    if ($departmentid <= 0) {
        return false;
    }

    return local_dutydesk_user_manages_department($departmentid, $userid);
}

/**
 * Determine whether a user may edit the workload percentage for a position.
 *
 * @param int|null $positionid
 * @param int|null $userid
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_user_can_edit_workload(?int $positionid, ?int $userid = null): bool {
    global $DB, $USER;

    $userid = $userid ?? (int)$USER->id;
    $context = context_system::instance();

    if (has_capability('local/dutydesk:manageall', $context, $userid)) {
        return true;
    }

    if (empty($positionid)) {
        return false;
    }

    $departmentid = $DB->get_field('local_dutydesk_position', 'departmentid', ['id' => $positionid]);
    if (!$departmentid) {
        return false;
    }

    return local_dutydesk_user_manages_department((int)$departmentid, $userid);
}

/**
 * Normalize a submitted workload percentage.
 *
 * @param mixed $value
 * @return int|null
 * @package local_dutydesk
 */
function local_dutydesk_normalize_workload_value($value): ?int {
    if ($value === '' || $value === null) {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }

    $intvalue = (int)round((float)$value);

    if ($intvalue < 0) {
        return 0;
    }
    if ($intvalue > 100) {
        return 100;
    }

    return $intvalue;
}

/**
 * Return a formatted string for workload percentages.
 *
 * @param int|null $value
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_format_workload_display(?int $value): string {
    if ($value === null) {
        return get_string('taskworkloadnotset', 'local_dutydesk');
    }

    return $value . '%';
}

/**
 * Build a SQL snippet used to match the global task search term.
 *
 * @param string $taskalias
 * @param string $posalias
 * @param string $deptalias
 * @param string $primaryalias
 * @param string $deputyalias
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_build_task_search_condition(
    string $searchvalue,
    string $taskalias = 't',
    string $posalias = 'p',
    string $deptalias = 'd',
    string $primaryalias = 'primaryuser',
    string $deputyalias = 'deputyuser',
    string $paramprefix = 'searchterm'
): array {
    global $DB;

    $targetfields = [
        "{$taskalias}.title",
        "{$taskalias}.description",
        "{$posalias}.title",
        "{$deptalias}.name",
        "{$primaryalias}.firstname",
        "{$primaryalias}.lastname",
        "{$deputyalias}.firstname",
        "{$deputyalias}.lastname",
    ];

    $conditions = [];
    $params = [];
    foreach ($targetfields as $index => $fieldname) {
        if (strpos($fieldname, '.') === false) {
            continue;
        }
        $paramname = $paramprefix . $index;
        $conditions[] = $DB->sql_like("LOWER({$fieldname})", ':' . $paramname, false);
        $params[$paramname] = $searchvalue;
    }

    if (empty($conditions)) {
        return ['1=1', []];
    }

    return ['(' . implode(' OR ', $conditions) . ')', $params];
}

/**
 * Build a SQL snippet used to match the position search term.
 *
 * @param string $searchvalue
 * @param string $posalias
 * @param string $deptalias
 * @param string $primaryalias
 * @param string $deputyalias
 * @param string $paramprefix
 * @return array
 * @package local_dutydesk
 */
function local_dutydesk_build_position_search_condition(
    string $searchvalue,
    string $posalias = 'p',
    string $deptalias = 'd',
    string $primaryalias = 'primaryuser',
    string $deputyalias = 'deputyuser',
    string $paramprefix = 'possearch'
): array {
    global $DB;

    $targetfields = [
        "{$posalias}.title",
        "{$posalias}.description",
        "{$deptalias}.name",
        "{$primaryalias}.firstname",
        "{$primaryalias}.lastname",
        "{$deputyalias}.firstname",
        "{$deputyalias}.lastname",
    ];

    $conditions = [];
    $params = [];
    foreach ($targetfields as $index => $fieldname) {
        if (strpos($fieldname, '.') === false) {
            continue;
        }
        $paramname = $paramprefix . $index;
        $conditions[] = $DB->sql_like("LOWER({$fieldname})", ':' . $paramname, false);
        $params[$paramname] = $searchvalue;
    }

    if (empty($conditions)) {
        return ['1=1', []];
    }

    return ['(' . implode(' OR ', $conditions) . ')', $params];
}

/**
 * Determine the page index for a position in the positions list.
 *
 * @param int $positionid
 * @param int $perpage
 * @param bool $canmanageall
 * @param int[] $manageablepositionids
 * @return int|null
 * @package local_dutydesk
 */
function local_dutydesk_calculate_position_page(
    int $positionid,
    int $perpage,
    bool $canmanageall,
    array $manageablepositionids
): ?int {
    global $DB;

    if ($positionid <= 0 || $perpage <= 0) {
        return null;
    }

    $position = $DB->get_record('local_dutydesk_position', ['id' => $positionid], 'id, title', IGNORE_MISSING);
    if (!$position) {
        return null;
    }

    $params = [
        'titlelt' => $position->title,
        'titleeq' => $position->title,
        'idlt' => $position->id,
    ];

    if ($canmanageall) {
        $count = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {local_dutydesk_position}
              WHERE title < :titlelt
                 OR (title = :titleeq AND id < :idlt)",
            $params
        );
        return (int)floor($count / $perpage);
    }

    if (empty($manageablepositionids) || !in_array($positionid, $manageablepositionids, true)) {
        return null;
    }

    [$insql, $insqlparams] = $DB->get_in_or_equal($manageablepositionids, SQL_PARAMS_NAMED);
    $params = array_merge($insqlparams, $params);
    $count = $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {local_dutydesk_position}
          WHERE id {$insql}
            AND (title < :titlelt OR (title = :titleeq AND id < :idlt))",
        $params
    );

    return (int)floor($count / $perpage);
}

/**
 * Determine the page index for a department in the department list.
 *
 * @param int $departmentid
 * @param int $perpage
 * @param bool $canmanageall
 * @param int[] $manageddepartmentids
 * @return int|null
 * @package local_dutydesk
 */
function local_dutydesk_calculate_department_page(
    int $departmentid,
    int $perpage,
    bool $canmanageall,
    array $manageddepartmentids
): ?int {
    global $DB;

    if ($departmentid <= 0 || $perpage <= 0) {
        return null;
    }

    $department = $DB->get_record('local_dutydesk_department', ['id' => $departmentid], 'id, name', IGNORE_MISSING);
    if (!$department) {
        return null;
    }

    $params = [
        'namelt' => $department->name,
        'nameeq' => $department->name,
        'idlt' => $department->id,
    ];

    if ($canmanageall) {
        $count = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {local_dutydesk_department}
              WHERE name < :namelt
                 OR (name = :nameeq AND id < :idlt)",
            $params
        );
        return (int)floor($count / $perpage);
    }

    if (empty($manageddepartmentids) || !in_array($departmentid, $manageddepartmentids, true)) {
        return null;
    }

    [$insql, $insqlparams] = $DB->get_in_or_equal($manageddepartmentids, SQL_PARAMS_NAMED);
    $params = array_merge($insqlparams, $params);
    $count = $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {local_dutydesk_department}
          WHERE id {$insql}
            AND (name < :namelt OR (name = :nameeq AND id < :idlt))",
        $params
    );

    return (int)floor($count / $perpage);
}

/**
 * Determine the page index for a task in the task list.
 *
 * @param int $taskid
 * @param int $perpage
 * @param bool $canviewalltasks
 * @param int[] $manageablepositionids
 * @param int[] $departmentids
 * @param int $positionid
 * @return int|null
 * @package local_dutydesk
 */
function local_dutydesk_calculate_task_page(
    int $taskid,
    int $perpage,
    bool $canviewalltasks,
    array $manageablepositionids,
    array $departmentids = [],
    int $positionid = 0
): ?int {
    global $DB;

    if ($taskid <= 0 || $perpage <= 0) {
        return null;
    }

    $task = $DB->get_record('local_dutydesk_task', ['id' => $taskid], 'id, title', IGNORE_MISSING);
    if (!$task) {
        return null;
    }

    $params = [
        'titlelt' => $task->title,
        'titleeq' => $task->title,
        'idlt' => $task->id,
    ];

    if ($canviewalltasks) {
        if ($positionid > 0) {
            $params['positionidfilter'] = $positionid;
            $count = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT t.id)
                   FROM {local_dutydesk_task} t
                   JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
                  WHERE ta.positionid = :positionidfilter
                    AND (t.title < :titlelt OR (t.title = :titleeq AND t.id < :idlt))",
                $params
            );
            return (int)floor($count / $perpage);
        }

        $count = $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {local_dutydesk_task}
              WHERE title < :titlelt
                 OR (title = :titleeq AND id < :idlt)",
            $params
        );
        return (int)floor($count / $perpage);
    }

    if (empty($manageablepositionids) && empty($departmentids)) {
        return null;
    }

    $conditions = [];
    if (!empty($manageablepositionids)) {
        [$positionsql, $positionparams] = $DB->get_in_or_equal($manageablepositionids, SQL_PARAMS_NAMED, 'ctp');
        $conditions[] = "ta.positionid {$positionsql}";
        $params = array_merge($params, $positionparams);
    }
    if (!empty($departmentids)) {
        [$deptsql, $deptparams] = $DB->get_in_or_equal($departmentids, SQL_PARAMS_NAMED, 'ctd');
        $conditions[] = "p.departmentid {$deptsql}";
        $params = array_merge($params, $deptparams);
    }
    $condition = implode(' OR ', array_map(static function ($snippet) {
        return "({$snippet})";
    }, $conditions));

    if ($positionid > 0) {
        $params['positionidfilter'] = $positionid;
    }
    $positionfiltersql = $positionid > 0 ? ' AND ta.positionid = :positionidfilter' : '';
    $count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT t.id)
           FROM {local_dutydesk_task} t
           JOIN {local_dutydesk_taskassign} ta ON ta.taskid = t.id
           JOIN {local_dutydesk_position} p ON p.id = ta.positionid
          WHERE {$condition}{$positionfiltersql}
            AND (t.title < :titlelt OR (t.title = :titleeq AND t.id < :idlt))",
        $params
    );

    return (int)floor($count / $perpage);
}

/**
 * Log a history entry for a task.
 *
 * @param int $taskid
 * @param string $action
 * @param string $details
 * @param int|null $userid
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_log_task_history(int $taskid, string $action, string $details = '', ?int $userid = null): void {
    global $DB, $USER;

    if ($taskid <= 0 || trim($action) === '') {
        return;
    }

    $userid = $userid ?? ($USER->id ?? 0);

    $record = (object) [
        'taskid' => $taskid,
        'userid' => $userid > 0 ? $userid : null,
        'action' => substr($action, 0, 50),
        'details' => $details,
        'timecreated' => time(),
    ];

    $DB->insert_record('local_dutydesk_taskhist', $record);
}

/**
 * Snapshot of task documents to compare changes.
 *
 * @param int $contextid
 * @param int $taskid
 * @return array
 * @package local_dutydesk
 */
function local_dutydesk_get_task_document_snapshot(int $contextid, int $taskid): array {
    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'local_dutydesk', 'taskdocuments', $taskid, 'filename', false);
    $snapshot = [];
    foreach ($files as $file) {
        $snapshot[$file->get_pathnamehash()] = $file->get_filename();
    }
    return $snapshot;
}

/**
 * Convert snapshot differences into human-readable description.
 *
 * @param array $before
 * @param array $after
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_describe_document_changes(array $before, array $after): string {
    $added = array_diff($after, $before);
    $removed = array_diff($before, $after);
    $parts = [];
    if (!empty($added)) {
        $parts[] = get_string('taskhistory_detail_documents_added', 'local_dutydesk', implode(', ', array_values($added)));
    }
    if (!empty($removed)) {
        $parts[] = get_string('taskhistory_detail_documents_removed', 'local_dutydesk', implode(', ', array_values($removed)));
    }
    return trim(implode(' ', $parts));
}

/**
 * Snapshot documents for a subtask.
 *
 * @param int $contextid
 * @param int $subtaskid
 * @return array
 * @package local_dutydesk
 */
function local_dutydesk_get_subtask_document_snapshot(int $contextid, int $subtaskid): array {
    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'local_dutydesk', 'subtaskdocuments', $subtaskid, 'filename', false);
    $snapshot = [];
    foreach ($files as $file) {
        $snapshot[$file->get_pathnamehash()] = $file->get_filename();
    }
    return $snapshot;
}

/**
 * Build the display payload for a task.
 *
 * @param stdClass $task
 * @param context $context
 * @param bool $canedit
 * @param bool $canmanagealltasks
 * @param int $page
 * @param int $perpage
 * @return array|null
 * @package local_dutydesk
 */
function local_dutydesk_build_task_display(
    stdClass $task,
    context $context,
    bool $canedit,
    bool $canmanagealltasks,
    int $page,
    int $perpage
): ?array {
    global $DB, $OUTPUT;

    $assignment = $DB->get_record_sql(
        "SELECT ta.id, ta.taskid, ta.positionid, ta.workloadpercent,
                p.title AS positiontitle, p.departmentid, p.primaryuserid, p.archived AS positionarchived,
                p.isvacant AS positionvacant,
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
          WHERE ta.taskid = :taskid",
        ['taskid' => $task->id]
    );
    $subtasks = $DB->get_records('local_dutydesk_subtask', ['taskid' => $task->id], 'sortorder ASC, id ASC');
    $fs = get_file_storage();

    $primaryuserdisplay = get_string('notassigned', 'local_dutydesk');
    $primaryuserplain = $primaryuserdisplay;
    $deputydefault = get_string('nodeputy', 'local_dutydesk');
    $deputyuserdisplay = $deputydefault;
    $deputyuserplain = '';
    $assignedpositionplain = get_string('notassigned', 'local_dutydesk');
    $assignedpositiondisplay = s($assignedpositionplain);
    $departmentplain = get_string('notassigned', 'local_dutydesk');
    $departmentdisplay = s($departmentplain);
    $workloadvalue = null;
    $workloaddisplay = get_string('taskworkloadnotset', 'local_dutydesk');
    $isvacanttask = $assignment && !empty($assignment->positionvacant);

    if ($assignment && !empty($assignment->primaryuserid)) {
        $primaryuser = (object)[
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

    if ($assignment && !empty($assignment->deputyuserid)) {
        $deputyuser = (object)[
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
                'view' => $viewparam,
            ]);
            $positionurl->set_anchor('position-' . $assignment->positionid);
            $assignedpositiondisplay = html_writer::link($positionurl, $assignedpositionplain);
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

    $taskdocuments = [];
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
        }
    }

    $subtaskdisplay = [];
    if (!empty($subtasks)) {
        usort($subtasks, static function ($a, $b) {
            $aorder = property_exists($a, 'sortorder') && $a->sortorder !== null ? (int)$a->sortorder : PHP_INT_MAX;
            $border = property_exists($b, 'sortorder') && $b->sortorder !== null ? (int)$b->sortorder : PHP_INT_MAX;
            if ($aorder === $border) {
                return $a->id <=> $b->id;
            }
            return $aorder <=> $border;
        });
        foreach ($subtasks as $subtask) {
            $subtasktitle = format_string($subtask->title);
            $editsubtaskurl = new moodle_url('/local/dutydesk/subtask.php', [
                'taskid' => $task->id,
                'id' => $subtask->id,
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
                }
            }
            $subtaskdisplay[] = [
                'id' => $subtask->id,
                'title' => $subtasktitle,
                'description' => $descriptionrendered !== '' ? $descriptionrendered : null,
                'hasdocuments' => !empty($documents),
                'documents' => $documents,
                'editurl' => $canedit ? $editsubtaskurl->out(false) : null,
                'canedit' => $canedit,
            ];
        }
    }

    $editurl = new moodle_url('/local/dutydesk/tasks.php', [
        'id' => $task->id,
        'page' => $page,
        'perpage' => $perpage,
    ]);
    $deleteurl = new moodle_url('/local/dutydesk/tasks.php', [
        'delete' => 1,
        'id' => $task->id,
        'page' => $page,
        'perpage' => $perpage,
    ]);
    $newsubtaskurl = new moodle_url('/local/dutydesk/subtask.php', [
        'taskid' => $task->id,
        'page' => $page,
        'perpage' => $perpage,
        'focus' => $task->id,
    ]);
    $candelete = $canmanagealltasks && $assignment && !empty($assignment->positionarchived);

    return [
        'id' => $task->id,
        'title' => format_string($task->title),
        'description' => $taskdescriptionrendered !== '' ? $taskdescriptionrendered : null,
        'hasdocuments' => !empty($taskdocuments),
        'documents' => $taskdocuments,
        'assigneduser' => $primaryuserdisplay,
        'deputyuser' => $deputyuserdisplay,
        'assignedposition' => $assignedpositionplain,
        'assignedpositiondisplay' => $assignedpositiondisplay,
        'department' => $departmentplain,
        'departmentdisplay' => $departmentdisplay,
        'hasworkload' => $workloadvalue !== null,
        'workload' => $workloadvalue,
        'workloaddisplay' => $workloaddisplay,
        'isvacanttask' => $isvacanttask,
        'vacanttasklabel' => get_string('taskvacantbadge', 'local_dutydesk'),
        'timestamp' => userdate($task->timestamp),
        'editurl' => $canedit ? $editurl->out(false) : null,
        'deleteurl' => $candelete ? $deleteurl->out(false) : null,
        'newsubtaskurl' => $canedit ? $newsubtaskurl->out(false) : null,
        'hassubtasks' => !empty($subtaskdisplay),
        'subtaskcount' => count($subtaskdisplay),
        'subtasks' => $subtaskdisplay,
        'canedit' => $canedit,
        'candelete' => $candelete,
        'cancreatesubtask' => $canedit,
        'cansortsubtasks' => false,
        'showactions' => ($canedit || $canmanagealltasks),
        'canviewhistory' => local_dutydesk_user_can_view_task_history((int)$task->id),
        'sesskey' => sesskey(),
        'searchtext' => '',
    ];
}

/**
 * Get formatted position name.
 *
 * @param int $positionid
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_get_position_name(int $positionid): string {
    global $DB;

    if ($positionid <= 0) {
        return '';
    }

    $title = $DB->get_field('local_dutydesk_position', 'title', ['id' => $positionid]);
    if (!$title) {
        return '';
    }

    return format_string($title);
}

/**
 * Create, update or remove a task assignment for a task/position pair.
 *
 * @param int $taskid
 * @param int $positionid
 * @param int|null $workloadpercent
 * @param bool $updateworkload
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_save_task_assignment(
    int $taskid,
    int $positionid = 0,
    ?int $workloadpercent = null,
    bool $updateworkload = false
): void {
    global $DB, $USER;

    $taskid = (int)$taskid;
    $positionid = (int)$positionid;
    $normalizedworkload = local_dutydesk_normalize_workload_value($workloadpercent);

    if ($taskid <= 0) {
        return;
    }

    $existing = $DB->get_record('local_dutydesk_taskassign', ['taskid' => $taskid]);
    $hasassignment = $positionid > 0;
    $originalpositionid = $existing ? (int)$existing->positionid : 0;
    $newpositionid = $originalpositionid;

    $context = context_system::instance();

    if (!has_capability('local/dutydesk:manageall', $context)) {
        if ($existing) {
            $positionid = (int)$existing->positionid;
            $hasassignment = $positionid > 0;
        } else {
            $hasassignment = false;
        }
    }

    if ($hasassignment) {
        $record = new stdClass();
        $record->taskid = $taskid;
        $record->positionid = $positionid;
        $record->assignedby = $USER->id;
        $record->timestamp = time();
        if ($updateworkload) {
            $record->workloadpercent = $normalizedworkload;
        }

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_dutydesk_taskassign', $record);
        } else {
            $DB->insert_record('local_dutydesk_taskassign', $record);
        }
        $newpositionid = $record->positionid;
    } else if ($existing) {
        $DB->delete_records('local_dutydesk_taskassign', ['taskid' => $taskid]);
        $newpositionid = 0;
    } else {
        $newpositionid = 0;
    }

    local_dutydesk_log_task_assignment_change($taskid, $originalpositionid, $newpositionid);
}

/**
 * Synchronise a list of tasks with a position assignment.
 *
 * @param int $positionid
 * @param array $taskids
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_sync_position_tasks(int $positionid, array $taskids): void {
    global $DB;

    $positionid = (int)$positionid;
    if ($positionid <= 0) {
        return;
    }

    $taskids = array_values(array_unique(array_filter(array_map('intval', $taskids))));
    $existing = $DB->get_records_menu('local_dutydesk_taskassign', ['positionid' => $positionid], '', 'taskid, taskid');
    $currenttaskids = array_map('intval', array_keys($existing));

    $taskstoassign = array_diff($taskids, $currenttaskids);
    $taskstounassign = array_diff($currenttaskids, $taskids);

    foreach ($taskstoassign as $taskid) {
        local_dutydesk_save_task_assignment($taskid, $positionid);
    }

    foreach ($taskstounassign as $taskid) {
        local_dutydesk_save_task_assignment($taskid, 0);
    }
}

/**
 * Log assignment changes for tasks.
 *
 * @param int $taskid
 * @param int $oldpositionid
 * @param int $newpositionid
 * @return void
 * @package local_dutydesk
 */
function local_dutydesk_log_task_assignment_change(int $taskid, int $oldpositionid, int $newpositionid): void {
    if ($oldpositionid === $newpositionid) {
        return;
    }

    $details = '';
    if ($newpositionid > 0 && $oldpositionid === 0) {
        $details = get_string(
            'taskhistory_detail_assignment_set',
            'local_dutydesk',
            local_dutydesk_get_position_name($newpositionid)
        );
    } else if ($newpositionid > 0 && $oldpositionid > 0) {
        $details = get_string('taskhistory_detail_assignment_changed', 'local_dutydesk', (object) [
            'old' => local_dutydesk_get_position_name($oldpositionid),
            'new' => local_dutydesk_get_position_name($newpositionid),
        ]);
    } else if ($newpositionid === 0 && $oldpositionid > 0) {
        $details = get_string(
            'taskhistory_detail_assignment_removed',
            'local_dutydesk',
            local_dutydesk_get_position_name($oldpositionid)
        );
    }

    if ($details !== '') {
        local_dutydesk_log_task_history($taskid, 'assignment', $details);
    }
}

/**
 * Return the deterministic tile pattern class for a position.
 *
 * The mapping is stable across pages so the same position keeps the same background.
 *
 * @param int $positionid
 * @param string $positiontitle
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_get_position_tile_pattern_class(int $positionid, string $positiontitle = ''): string {
    $patterns = [
        'local-dutydesk-position-tile-pattern-1',
        'local-dutydesk-position-tile-pattern-2',
        'local-dutydesk-position-tile-pattern-3',
        'local-dutydesk-position-tile-pattern-4',
        'local-dutydesk-position-tile-pattern-5',
        'local-dutydesk-position-tile-pattern-6',
    ];

    $key = $positionid . '|' . core_text::strtolower(trim($positiontitle));
    $offset = abs(crc32($key)) % count($patterns);
    return $patterns[$offset];
}

/**
 * Map a tile pattern class to the matching task header pattern class.
 *
 * @param string $tileclass
 * @return string
 * @package local_dutydesk
 */
function local_dutydesk_get_position_task_header_pattern_class(string $tileclass): string {
    if (preg_match('/pattern-(\d+)$/', $tileclass, $matches)) {
        return 'local-dutydesk-position-task-table-header--' . $matches[1];
    }
    return 'local-dutydesk-position-task-table-header--1';
}

/**
 * Serve files from the DutyDesk plugin file areas.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 * @package local_dutydesk
 */
function local_dutydesk_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    $allowedareas = [
        'taskdescription',
        'taskdocuments',
        'subtaskdescription',
        'subtaskdocuments',
    ];
    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args);
    if ($filepath === '/' || $filepath === '') {
        $filepath = '/';
    } else {
        $filepath .= '/';
    }

    // Enforce object-level access checks for file delivery.
    if ($filearea === 'taskdescription' || $filearea === 'taskdocuments') {
        if (!local_dutydesk_user_can_view_task($itemid)) {
            return false;
        }
    } else if ($filearea === 'subtaskdescription' || $filearea === 'subtaskdocuments') {
        $taskid = (int)$DB->get_field('local_dutydesk_subtask', 'taskid', ['id' => $itemid]);
        if ($taskid <= 0 || !local_dutydesk_user_can_view_task($taskid)) {
            return false;
        }
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_dutydesk', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
