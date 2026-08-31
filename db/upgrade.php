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

/**
 * Upgrade steps for local_dutydesk.
 *
 * @param int $oldversion
 * @return bool
 * @package local_dutydesk
 */
function xmldb_local_dutydesk_upgrade(int $oldversion): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $gettablename = static function (string $oldname, string $newname) use ($dbman): ?string {
        if ($dbman->table_exists(new xmldb_table($oldname))) {
            return $oldname;
        }
        if ($dbman->table_exists(new xmldb_table($newname))) {
            return $newname;
        }
        return null;
    };

    $gettable = static function (string $oldname, string $newname) use ($gettablename): ?xmldb_table {
        $tablename = $gettablename($oldname, $newname);
        return $tablename === null ? null : new xmldb_table($tablename);
    };

    if ($oldversion < 2026072801) {
        $table = $gettable('dutydesk_subtask', 'local_dutydesk_subtask');
        $oldfield = new xmldb_field('order', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'title');

        if ($table !== null && $dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'sortorder');
        }

        upgrade_plugin_savepoint(true, 2026072801, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072802) {
        $tablename = $gettablename('dutydesk_task', 'local_dutydesk_task');
        $table = $tablename === null ? null : new xmldb_table($tablename);
        $field = new xmldb_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        if ($tablename !== null) {
            $DB->set_field($tablename, 'descriptionformat', FORMAT_HTML);
        }

        upgrade_plugin_savepoint(true, 2026072802, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072803) {
        $tablename = $gettablename('dutydesk_subtask', 'local_dutydesk_subtask');
        $table = $tablename === null ? null : new xmldb_table($tablename);
        $description = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'title');
        $descriptionformat = new xmldb_field(
            'descriptionformat',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            FORMAT_HTML,
            'description'
        );

        if ($table !== null) {
            if (!$dbman->field_exists($table, $description)) {
                $dbman->add_field($table, $description);
            }
            if (!$dbman->field_exists($table, $descriptionformat)) {
                $dbman->add_field($table, $descriptionformat);
            }
        }

        if ($tablename !== null) {
            $DB->set_field($tablename, 'descriptionformat', FORMAT_HTML);
        }

        upgrade_plugin_savepoint(true, 2026072803, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072804) {
        $positiontablename = $gettablename('dutydesk_position', 'local_dutydesk_position');
        $positiontable = $positiontablename === null ? null : new xmldb_table($positiontablename);
        $descriptionfield = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'departmentid');
        if ($positiontable !== null && !$dbman->field_exists($positiontable, $descriptionfield)) {
            $dbman->add_field($positiontable, $descriptionfield);
        }

        $primaryfield = new xmldb_field('primaryuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'description');

        if ($positiontable !== null && !$dbman->field_exists($positiontable, $primaryfield)) {
            $dbman->add_field($positiontable, $primaryfield);
        }

        $assignmenttablename = $gettablename('dutydesk_taskassignment', 'local_dutydesk_taskassign');
        $assignmenttable = $assignmenttablename === null ? null : new xmldb_table($assignmenttablename);
        $userfield = new xmldb_field('userid');
        if (
            $assignmenttable !== null
            && $positiontablename !== null
            && $dbman->field_exists($assignmenttable, $userfield)
        ) {
            $assignments = $DB->get_records_sql(
                "SELECT positionid, userid FROM {{$assignmenttablename}} WHERE userid IS NOT NULL"
            );
            $updatedpositions = [];
            if (!empty($assignments)) {
                foreach ($assignments as $assignment) {
                    $positionid = (int)($assignment->positionid ?? 0);
                    $userid = (int)($assignment->userid ?? 0);
                    if ($positionid <= 0 || $userid <= 0) {
                        continue;
                    }
                    if (isset($updatedpositions[$positionid])) {
                        continue;
                    }
                    $existingprimary = $DB->get_field($positiontablename, 'primaryuserid', ['id' => $positionid]);
                    if (empty($existingprimary)) {
                        $DB->set_field($positiontablename, 'primaryuserid', $userid, ['id' => $positionid]);
                        $updatedpositions[$positionid] = true;
                    }
                }
            }
            $dbman->drop_field($assignmenttable, $userfield);
        }

        $olddeputytable = new xmldb_table('dutydesk_taskvertretung');
        if ($dbman->table_exists($olddeputytable)) {
            $dbman->rename_table($olddeputytable, 'dutydesk_position_deputy');
        }

        $deputytablename = $gettablename('dutydesk_position_deputy', 'local_dutydesk_posdeputy');
        $deputytable = $deputytablename === null ? null : new xmldb_table($deputytablename);
        if ($deputytable !== null) {
            $taskfield = new xmldb_field('taskid');
            if ($dbman->field_exists($deputytable, $taskfield)) {
                $dbman->drop_field($deputytable, $taskfield);
            }

            $timecreated = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'assignedby');
            if (!$dbman->field_exists($deputytable, $timecreated)) {
                $dbman->add_field($deputytable, $timecreated);
            }

            $existingdeputies = $DB->get_records($deputytablename);
            $seenpositions = [];
            $now = time();
            foreach ($existingdeputies as $deputy) {
                $positionid = (int)($deputy->positionid ?? 0);
                $userid = (int)($deputy->userid ?? 0);

                if ($positionid <= 0 || $userid <= 0) {
                    $DB->delete_records($deputytablename, ['id' => $deputy->id]);
                    continue;
                }

                if (isset($seenpositions[$positionid])) {
                    $DB->delete_records($deputytablename, ['id' => $deputy->id]);
                    continue;
                }

                $seenpositions[$positionid] = true;

                if (empty($deputy->timecreated)) {
                    $DB->set_field($deputytablename, 'timecreated', $now, ['id' => $deputy->id]);
                }
            }

            $uniqueindex = new xmldb_index('uniqposition', XMLDB_INDEX_UNIQUE, ['positionid']);
            if (!$dbman->index_exists($deputytable, $uniqueindex)) {
                $dbman->add_index($deputytable, $uniqueindex);
            }
        }

        upgrade_plugin_savepoint(true, 2026072804, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072805) {
        upgrade_plugin_savepoint(true, 2026072805, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072806) {
        // Register new capabilities.
        update_capabilities('local_dutydesk');

        // Ensure new capabilities are installed with default permissions.
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        if ($managerrole) {
            assign_capability('local/dutydesk:viewall', CAP_ALLOW, $managerrole->id, context_system::instance(), true);
            assign_capability('local/dutydesk:manageall', CAP_ALLOW, $managerrole->id, context_system::instance(), true);
            assign_capability('local/dutydesk:managepositions', CAP_ALLOW, $managerrole->id, context_system::instance(), true);
            assign_capability('local/dutydesk:viewown', CAP_ALLOW, $managerrole->id, context_system::instance(), true);
            assign_capability('local/dutydesk:manageown', CAP_ALLOW, $managerrole->id, context_system::instance(), true);
        }

        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        if ($userrole) {
            assign_capability('local/dutydesk:viewown', CAP_ALLOW, $userrole->id, context_system::instance(), true);
            assign_capability('local/dutydesk:manageown', CAP_ALLOW, $userrole->id, context_system::instance(), true);
        }

        upgrade_plugin_savepoint(true, 2026072806, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072807) {
        $table = $gettable('dutydesk_department_manager', 'local_dutydesk_deptmgr');
        $table = $table ?? new xmldb_table('dutydesk_department_manager');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('departmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('assignedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('deptuseruniq', XMLDB_KEY_UNIQUE, ['departmentid', 'userid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072807, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072808) {
        $table = $gettable('dutydesk_task_history', 'local_dutydesk_taskhist');
        $table = $table ?? new xmldb_table('dutydesk_task_history');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('taskid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('details', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('taskidx', XMLDB_INDEX_NOTUNIQUE, ['taskid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072808, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072809) {
        $table = $gettable('dutydesk_taskassignment', 'local_dutydesk_taskassign');
        $field = new xmldb_field('workloadpercent', XMLDB_TYPE_INTEGER, '3', null, null, null, null, 'positionid');

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026072809, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072810) {
        upgrade_plugin_savepoint(true, 2026072810, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072811) {
        $tablename = $gettablename('dutydesk_position', 'local_dutydesk_position');
        $table = $tablename === null ? null : new xmldb_table($tablename);
        $field = new xmldb_field('archivedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'archived');

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        if ($tablename !== null) {
            $DB->execute(
                "UPDATE {{$tablename}}
                    SET archivedtime = timestamp
                  WHERE archived = 1
                    AND (archivedtime IS NULL OR archivedtime = 0)"
            );
        }

        upgrade_plugin_savepoint(true, 2026072811, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072812) {
        update_capabilities('local_dutydesk');
        upgrade_plugin_savepoint(true, 2026072812, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072813) {
        $table = $gettable('dutydesk_position', 'local_dutydesk_position');
        $field = new xmldb_field('isvacant', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'archivedtime');

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026072813, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072814) {
        $table = $gettable('dutydesk_position', 'local_dutydesk_position');
        $field = new xmldb_field(
            'positiontype',
            XMLDB_TYPE_CHAR,
            '20',
            null,
            XMLDB_NOTNULL,
            null,
            'position',
            'title'
        );

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026072814, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072815) {
        $table = $gettable('dutydesk_category', 'local_dutydesk_category');
        $field = new xmldb_field('departmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name');

        if ($table !== null && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026072815, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072816) {
        $olddeputytable = new xmldb_table('dutydesk_position_deputyassigment');
        $newdeputytable = new xmldb_table('dutydesk_position_deputy');
        $currentdeputytable = new xmldb_table('local_dutydesk_posdeputy');

        if (
            $dbman->table_exists($olddeputytable)
            && !$dbman->table_exists($newdeputytable)
            && !$dbman->table_exists($currentdeputytable)
        ) {
            $dbman->rename_table($olddeputytable, 'dutydesk_position_deputy');
        }

        upgrade_plugin_savepoint(true, 2026072816, 'local', 'dutydesk');
    }

    if ($oldversion < 2026072817) {
        $renames = [
            'dutydesk_department' => 'local_dutydesk_department',
            'dutydesk_department_manager' => 'local_dutydesk_deptmgr',
            'dutydesk_position' => 'local_dutydesk_position',
            'dutydesk_userinfo' => 'local_dutydesk_userinfo',
            'dutydesk_task' => 'local_dutydesk_task',
            'dutydesk_subtask' => 'local_dutydesk_subtask',
            'dutydesk_taskassignment' => 'local_dutydesk_taskassign',
            'dutydesk_position_deputy' => 'local_dutydesk_posdeputy',
            'dutydesk_category' => 'local_dutydesk_category',
            'dutydesk_comment' => 'local_dutydesk_comment',
            'dutydesk_import' => 'local_dutydesk_import',
            'dutydesk_task_history' => 'local_dutydesk_taskhist',
        ];

        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            $newtable = new xmldb_table($newname);

            if ($dbman->table_exists($oldtable) && !$dbman->table_exists($newtable)) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        upgrade_plugin_savepoint(true, 2026072817, 'local', 'dutydesk');
    }

    return true;
}
