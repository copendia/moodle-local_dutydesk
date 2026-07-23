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
 * Privacy provider for DutyDesk.
 *
 * @package    local_dutydesk
 * @category   privacy
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dutydesk\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for DutyDesk.
 *
 * @package    local_dutydesk
 * @category   privacy
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about stored user data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('dutydesk_department_manager', [
            'userid' => 'privacy:metadata:dutydesk_department_manager:userid',
            'assignedby' => 'privacy:metadata:dutydesk_department_manager:assignedby',
            'timecreated' => 'privacy:metadata:dutydesk_department_manager:timecreated',
        ], 'privacy:metadata:dutydesk_department_manager');

        $collection->add_database_table('dutydesk_position', [
            'primaryuserid' => 'privacy:metadata:dutydesk_position:primaryuserid',
            'archivedtime' => 'privacy:metadata:dutydesk_position:archivedtime',
        ], 'privacy:metadata:dutydesk_position');

        $collection->add_database_table('dutydesk_userinfo', [
            'userid' => 'privacy:metadata:dutydesk_userinfo:userid',
            'dutydeskrole' => 'privacy:metadata:dutydesk_userinfo:dutydeskrole',
        ], 'privacy:metadata:dutydesk_userinfo');

        $collection->add_database_table('dutydesk_taskassignment', [
            'assignedby' => 'privacy:metadata:dutydesk_taskassignment:assignedby',
            'timestamp' => 'privacy:metadata:dutydesk_taskassignment:timestamp',
        ], 'privacy:metadata:dutydesk_taskassignment');

        $collection->add_database_table('dutydesk_position_deputy', [
            'userid' => 'privacy:metadata:dutydesk_position_deputy:userid',
            'assignedby' => 'privacy:metadata:dutydesk_position_deputy:assignedby',
            'timecreated' => 'privacy:metadata:dutydesk_position_deputy:timecreated',
        ], 'privacy:metadata:dutydesk_position_deputy');

        $collection->add_database_table('dutydesk_comment', [
            'userid' => 'privacy:metadata:dutydesk_comment:userid',
            'content' => 'privacy:metadata:dutydesk_comment:content',
            'created' => 'privacy:metadata:dutydesk_comment:created',
        ], 'privacy:metadata:dutydesk_comment');

        $collection->add_database_table('dutydesk_import', [
            'filename' => 'privacy:metadata:dutydesk_import:filename',
            'importedby' => 'privacy:metadata:dutydesk_import:importedby',
            'created' => 'privacy:metadata:dutydesk_import:created',
        ], 'privacy:metadata:dutydesk_import');

        $collection->add_database_table('dutydesk_task_history', [
            'userid' => 'privacy:metadata:dutydesk_task_history:userid',
            'action' => 'privacy:metadata:dutydesk_task_history:action',
            'details' => 'privacy:metadata:dutydesk_task_history:details',
            'timecreated' => 'privacy:metadata:dutydesk_task_history:timecreated',
        ], 'privacy:metadata:dutydesk_task_history');

        return $collection;
    }

    /**
     * Get contexts containing user data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        if (self::has_user_data($userid)) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Get users with data in the supplied context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userlist->add_from_sql('userid', self::get_userlist_sql(), []);
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $data = (object) [
                'departmentmanagerassignments' => array_values($DB->get_records('dutydesk_department_manager',
                    ['userid' => $userid])),
                'assigneddepartmentmanagers' => array_values($DB->get_records('dutydesk_department_manager',
                    ['assignedby' => $userid])),
                'positions' => array_values($DB->get_records('dutydesk_position', ['primaryuserid' => $userid])),
                'userinfo' => array_values($DB->get_records('dutydesk_userinfo', ['userid' => $userid])),
                'taskassignments' => array_values($DB->get_records('dutydesk_taskassignment', ['assignedby' => $userid])),
                'deputyassignments' => array_values($DB->get_records('dutydesk_position_deputy',
                    ['userid' => $userid])),
                'assigneddeputies' => array_values($DB->get_records('dutydesk_position_deputy',
                    ['assignedby' => $userid])),
                'comments' => array_values($DB->get_records('dutydesk_comment', ['userid' => $userid])),
                'imports' => array_values($DB->get_records('dutydesk_import', ['importedby' => $userid])),
                'taskhistory' => array_values($DB->get_records('dutydesk_task_history', ['userid' => $userid])),
            ];

            writer::with_context($context)->export_data([get_string('pluginname', 'local_dutydesk')], $data);
        }
    }

    /**
     * Delete all user data in the supplied context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('dutydesk_department_manager');
        $DB->set_field('dutydesk_position', 'primaryuserid', null);
        $DB->delete_records('dutydesk_userinfo');
        $DB->set_field('dutydesk_taskassignment', 'assignedby', 0);
        $DB->delete_records('dutydesk_position_deputy');
        $DB->delete_records('dutydesk_comment');
        $DB->set_field('dutydesk_import', 'importedby', 0);
        $DB->set_field('dutydesk_task_history', 'userid', null);
    }

    /**
     * Delete user data for approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                self::delete_user_records($userid);
            }
        }
    }

    /**
     * Delete data for an approved user list.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_records($userid);
        }
    }

    /**
     * Check whether the user has related DutyDesk records.
     *
     * @param int $userid
     * @return bool
     */
    private static function has_user_data(int $userid): bool {
        global $DB;

        return $DB->record_exists('dutydesk_department_manager', ['userid' => $userid])
            || $DB->record_exists('dutydesk_department_manager', ['assignedby' => $userid])
            || $DB->record_exists('dutydesk_position', ['primaryuserid' => $userid])
            || $DB->record_exists('dutydesk_userinfo', ['userid' => $userid])
            || $DB->record_exists('dutydesk_taskassignment', ['assignedby' => $userid])
            || $DB->record_exists('dutydesk_position_deputy', ['userid' => $userid])
            || $DB->record_exists('dutydesk_position_deputy', ['assignedby' => $userid])
            || $DB->record_exists('dutydesk_comment', ['userid' => $userid])
            || $DB->record_exists('dutydesk_import', ['importedby' => $userid])
            || $DB->record_exists('dutydesk_task_history', ['userid' => $userid]);
    }

    /**
     * Return SQL that lists all users referenced by DutyDesk.
     *
     * @return string
     */
    private static function get_userlist_sql(): string {
        return "SELECT userid
                  FROM {dutydesk_department_manager}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {dutydesk_department_manager}
                 WHERE assignedby > 0
                UNION
                SELECT primaryuserid AS userid
                  FROM {dutydesk_position}
                 WHERE primaryuserid > 0
                UNION
                SELECT userid
                  FROM {dutydesk_userinfo}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {dutydesk_taskassignment}
                 WHERE assignedby > 0
                UNION
                SELECT userid
                  FROM {dutydesk_position_deputy}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {dutydesk_position_deputy}
                 WHERE assignedby > 0
                UNION
                SELECT userid
                  FROM {dutydesk_comment}
                 WHERE userid > 0
                UNION
                SELECT importedby AS userid
                  FROM {dutydesk_import}
                 WHERE importedby > 0
                UNION
                SELECT userid
                  FROM {dutydesk_task_history}
                 WHERE userid > 0";
    }

    /**
     * Delete or anonymise user related records.
     *
     * @param int $userid
     * @return void
     */
    private static function delete_user_records(int $userid): void {
        global $DB;

        $DB->delete_records('dutydesk_department_manager', ['userid' => $userid]);
        $DB->set_field('dutydesk_department_manager', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->set_field('dutydesk_position', 'primaryuserid', null, ['primaryuserid' => $userid]);
        $DB->delete_records('dutydesk_userinfo', ['userid' => $userid]);
        $DB->set_field('dutydesk_taskassignment', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->delete_records('dutydesk_position_deputy', ['userid' => $userid]);
        $DB->set_field('dutydesk_position_deputy', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->delete_records('dutydesk_comment', ['userid' => $userid]);
        $DB->set_field('dutydesk_import', 'importedby', 0, ['importedby' => $userid]);
        $DB->set_field('dutydesk_task_history', 'userid', null, ['userid' => $userid]);
    }
}
