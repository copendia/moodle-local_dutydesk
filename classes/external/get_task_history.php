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

namespace local_dutydesk\external;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once(dirname(__DIR__, 2) . '/lib.php');

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use required_capability_exception;

/**
 * External function for loading task history modal content.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_task_history extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'taskid' => new external_value(PARAM_INT, 'Task id'),
        ]);
    }

    /**
     * Return rendered task history modal content.
     *
     * @param int $taskid
     * @return array
     */
    public static function execute(int $taskid): array {
        global $DB, $OUTPUT;

        $params = self::validate_parameters(self::execute_parameters(), [
            'taskid' => $taskid,
        ]);
        $taskid = (int)$params['taskid'];
        if ($taskid <= 0) {
            throw new invalid_parameter_exception('Invalid task id');
        }

        require_login();

        $context = context_system::instance();
        self::validate_context($context);

        $task = $DB->get_record('local_dutydesk_task', ['id' => $taskid], 'id, title', MUST_EXIST);
        if (!local_dutydesk_user_can_view_task_history($taskid)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:managepositions',
                'nopermissions',
                'local_dutydesk'
            );
        }

        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $historyrecords = $DB->get_records_sql(
            "SELECT h.*, u.firstname, u.lastname, u.middlename, u.alternatename,
                    u.firstnamephonetic, u.lastnamephonetic
               FROM {local_dutydesk_taskhist} h
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

        return [
            'modaltitle' => get_string('taskhistorymodalheading', 'local_dutydesk', format_string($task->title)),
            'bodyhtml' => $bodyhtml,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'modaltitle' => new external_value(PARAM_TEXT, 'Modal title'),
            'bodyhtml' => new external_value(PARAM_RAW, 'Rendered modal body HTML'),
        ]);
    }
}
