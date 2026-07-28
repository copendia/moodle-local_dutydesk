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
        $collection->add_database_table('local_dutydesk_deptmgr', [
            'userid' => 'privacy:metadata:local_dutydesk_deptmgr:userid',
            'assignedby' => 'privacy:metadata:local_dutydesk_deptmgr:assignedby',
            'timecreated' => 'privacy:metadata:local_dutydesk_deptmgr:timecreated',
        ], 'privacy:metadata:local_dutydesk_deptmgr');

        $collection->add_database_table('local_dutydesk_position', [
            'primaryuserid' => 'privacy:metadata:local_dutydesk_position:primaryuserid',
            'archivedtime' => 'privacy:metadata:local_dutydesk_position:archivedtime',
        ], 'privacy:metadata:local_dutydesk_position');

        $collection->add_database_table('local_dutydesk_userinfo', [
            'userid' => 'privacy:metadata:local_dutydesk_userinfo:userid',
            'dutydeskrole' => 'privacy:metadata:local_dutydesk_userinfo:dutydeskrole',
        ], 'privacy:metadata:local_dutydesk_userinfo');

        $collection->add_database_table('local_dutydesk_taskassign', [
            'assignedby' => 'privacy:metadata:local_dutydesk_taskassign:assignedby',
            'timestamp' => 'privacy:metadata:local_dutydesk_taskassign:timestamp',
        ], 'privacy:metadata:local_dutydesk_taskassign');

        $collection->add_database_table('local_dutydesk_posdeputy', [
            'userid' => 'privacy:metadata:local_dutydesk_posdeputy:userid',
            'assignedby' => 'privacy:metadata:local_dutydesk_posdeputy:assignedby',
            'timecreated' => 'privacy:metadata:local_dutydesk_posdeputy:timecreated',
        ], 'privacy:metadata:local_dutydesk_posdeputy');

        $collection->add_database_table('local_dutydesk_comment', [
            'userid' => 'privacy:metadata:local_dutydesk_comment:userid',
            'content' => 'privacy:metadata:local_dutydesk_comment:content',
            'created' => 'privacy:metadata:local_dutydesk_comment:created',
        ], 'privacy:metadata:local_dutydesk_comment');

        $collection->add_database_table('local_dutydesk_import', [
            'filename' => 'privacy:metadata:local_dutydesk_import:filename',
            'importedby' => 'privacy:metadata:local_dutydesk_import:importedby',
            'created' => 'privacy:metadata:local_dutydesk_import:created',
        ], 'privacy:metadata:local_dutydesk_import');

        $collection->add_database_table('local_dutydesk_taskhist', [
            'userid' => 'privacy:metadata:local_dutydesk_taskhist:userid',
            'action' => 'privacy:metadata:local_dutydesk_taskhist:action',
            'details' => 'privacy:metadata:local_dutydesk_taskhist:details',
            'timecreated' => 'privacy:metadata:local_dutydesk_taskhist:timecreated',
        ], 'privacy:metadata:local_dutydesk_taskhist');

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
                'departmentmanagerassignments' => array_values($DB->get_records(
                    'local_dutydesk_deptmgr',
                    ['userid' => $userid]
                )),
                'assigneddepartmentmanagers' => array_values($DB->get_records(
                    'local_dutydesk_deptmgr',
                    ['assignedby' => $userid]
                )),
                'positions' => array_values($DB->get_records('local_dutydesk_position', ['primaryuserid' => $userid])),
                'userinfo' => array_values($DB->get_records('local_dutydesk_userinfo', ['userid' => $userid])),
                'taskassignments' => array_values($DB->get_records('local_dutydesk_taskassign', ['assignedby' => $userid])),
                'deputyassignments' => array_values($DB->get_records(
                    'local_dutydesk_posdeputy',
                    ['userid' => $userid]
                )),
                'assigneddeputies' => array_values($DB->get_records(
                    'local_dutydesk_posdeputy',
                    ['assignedby' => $userid]
                )),
                'comments' => array_values($DB->get_records('local_dutydesk_comment', ['userid' => $userid])),
                'imports' => array_values($DB->get_records('local_dutydesk_import', ['importedby' => $userid])),
                'taskhistory' => array_values($DB->get_records('local_dutydesk_taskhist', ['userid' => $userid])),
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

        $DB->delete_records('local_dutydesk_deptmgr');
        $DB->set_field('local_dutydesk_position', 'primaryuserid', null);
        $DB->delete_records('local_dutydesk_userinfo');
        $DB->set_field('local_dutydesk_taskassign', 'assignedby', 0);
        $DB->delete_records('local_dutydesk_posdeputy');
        $DB->delete_records('local_dutydesk_comment');
        $DB->set_field('local_dutydesk_import', 'importedby', 0);
        $DB->set_field('local_dutydesk_taskhist', 'userid', null);
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

        return $DB->record_exists('local_dutydesk_deptmgr', ['userid' => $userid])
            || $DB->record_exists('local_dutydesk_deptmgr', ['assignedby' => $userid])
            || $DB->record_exists('local_dutydesk_position', ['primaryuserid' => $userid])
            || $DB->record_exists('local_dutydesk_userinfo', ['userid' => $userid])
            || $DB->record_exists('local_dutydesk_taskassign', ['assignedby' => $userid])
            || $DB->record_exists('local_dutydesk_posdeputy', ['userid' => $userid])
            || $DB->record_exists('local_dutydesk_posdeputy', ['assignedby' => $userid])
            || $DB->record_exists('local_dutydesk_comment', ['userid' => $userid])
            || $DB->record_exists('local_dutydesk_import', ['importedby' => $userid])
            || $DB->record_exists('local_dutydesk_taskhist', ['userid' => $userid]);
    }

    /**
     * Return SQL that lists all users referenced by DutyDesk.
     *
     * @return string
     */
    private static function get_userlist_sql(): string {
        return "SELECT userid
                  FROM {local_dutydesk_deptmgr}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {local_dutydesk_deptmgr}
                 WHERE assignedby > 0
                UNION
                SELECT primaryuserid AS userid
                  FROM {local_dutydesk_position}
                 WHERE primaryuserid > 0
                UNION
                SELECT userid
                  FROM {local_dutydesk_userinfo}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {local_dutydesk_taskassign}
                 WHERE assignedby > 0
                UNION
                SELECT userid
                  FROM {local_dutydesk_posdeputy}
                 WHERE userid > 0
                UNION
                SELECT assignedby AS userid
                  FROM {local_dutydesk_posdeputy}
                 WHERE assignedby > 0
                UNION
                SELECT userid
                  FROM {local_dutydesk_comment}
                 WHERE userid > 0
                UNION
                SELECT importedby AS userid
                  FROM {local_dutydesk_import}
                 WHERE importedby > 0
                UNION
                SELECT userid
                  FROM {local_dutydesk_taskhist}
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

        $DB->delete_records('local_dutydesk_deptmgr', ['userid' => $userid]);
        $DB->set_field('local_dutydesk_deptmgr', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->set_field('local_dutydesk_position', 'primaryuserid', null, ['primaryuserid' => $userid]);
        $DB->delete_records('local_dutydesk_userinfo', ['userid' => $userid]);
        $DB->set_field('local_dutydesk_taskassign', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->delete_records('local_dutydesk_posdeputy', ['userid' => $userid]);
        $DB->set_field('local_dutydesk_posdeputy', 'assignedby', 0, ['assignedby' => $userid]);
        $DB->delete_records('local_dutydesk_comment', ['userid' => $userid]);
        $DB->set_field('local_dutydesk_import', 'importedby', 0, ['importedby' => $userid]);
        $DB->set_field('local_dutydesk_taskhist', 'userid', null, ['userid' => $userid]);
    }
}
