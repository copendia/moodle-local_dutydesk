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

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once(__DIR__ . '/lib.php');

$taskid = required_param('taskid', PARAM_INT);
require_sesskey();

require_login();

$context = context_system::instance();
$PAGE->set_context($context);

if ($taskid <= 0) {
    throw new invalid_parameter_exception('Invalid task id');
}

$task = $DB->get_record('dutydesk_task', ['id' => $taskid], 'id, title', MUST_EXIST);

if (!local_dutydesk_user_can_view_task_history($taskid)) {
    throw new required_capability_exception($context, 'local/dutydesk:managepositions', 'nopermissions', 'local_dutydesk');
}

$canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
$historyrecords = $DB->get_records_sql(
    "SELECT h.*, u.firstname, u.lastname, u.middlename, u.alternatename,
            u.firstnamephonetic, u.lastnamephonetic
       FROM {dutydesk_task_history} h
  LEFT JOIN {user} u ON u.id = h.userid
      WHERE h.taskid = :taskid
   ORDER BY h.timecreated DESC",
    ['taskid' => $taskid],
    0,
    50
);

$entries = [];
foreach ($historyrecords as $entry) {
    $userdisplay = get_string('taskhistory_systemuser', 'local_dutydesk');
    if (!empty($entry->userid)) {
        $userdisplay = fullname($entry, $canviewfullnames);
    }

    $detailshtml = '';
    if (!empty($entry->details)) {
        $detailshtml = nl2br(s($entry->details));
    }

    $entries[] = [
        'action' => get_string('taskhistory_action_' . $entry->action, 'local_dutydesk'),
        'details' => $detailshtml,
        'user' => $userdisplay,
        'time' => userdate($entry->timecreated),
    ];
}

$bodyhtml = $OUTPUT->render_from_template('local_dutydesk/task_history_modal', [
    'hasentries' => !empty($entries),
    'entries' => $entries,
]);

$response = [
    'modaltitle' => get_string('taskhistorymodalheading', 'local_dutydesk', format_string($task->title)),
    'bodyhtml' => $bodyhtml,
];

header('Content-Type: application/json');
echo json_encode($response);
die();
