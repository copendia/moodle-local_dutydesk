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

namespace local_dutydesk;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use local_dutydesk\local\position\manager;

/**
 * Tests for position persistence operations.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class position_manager_test extends \advanced_testcase {
    /**
     * Handles setUp.
     *
     * @return mixed
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Tests that creating a position also stores its deputy and task assignments.
     */
    public function test_create_position_persists_deputy_and_task_assignments(): void {
        global $DB;

        $departmentid = $this->create_department('Department A');
        $primaryuser = $this->getDataGenerator()->create_user();
        $deputyuser = $this->getDataGenerator()->create_user();
        $taskone = $this->create_task('Task one');
        $tasktwo = $this->create_task('Task two');

        $positionid = manager::create_position((object) [
            'title' => 'Position A',
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'departmentid' => $departmentid,
            'description' => 'Position description',
            'primaryuserid' => $primaryuser->id,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ], $deputyuser->id, [$taskone, $tasktwo]);

        $position = $DB->get_record('local_dutydesk_position', ['id' => $positionid], '*', MUST_EXIST);
        $this->assertSame('Position A', $position->title);
        $this->assertSame((int)$departmentid, (int)$position->departmentid);
        $this->assertSame((int)$primaryuser->id, (int)$position->primaryuserid);

        $deputy = $DB->get_record('local_dutydesk_posdeputy', ['positionid' => $positionid], '*', MUST_EXIST);
        $this->assertSame((int)$deputyuser->id, (int)$deputy->userid);

        $assignedtaskids = $DB->get_fieldset_select(
            'local_dutydesk_taskassign',
            'taskid',
            'positionid = ?',
            [$positionid]
        );
        sort($assignedtaskids);
        $expected = [$taskone, $tasktwo];
        sort($expected);
        $this->assertSame($expected, array_map('intval', $assignedtaskids));
    }

    /**
     * Tests that updating a position replaces the deputy and synchronises task assignments.
     */
    public function test_update_position_replaces_deputy_and_task_assignments(): void {
        global $DB;

        $departmentid = $this->create_department('Department B');
        $olddeputy = $this->getDataGenerator()->create_user();
        $newdeputy = $this->getDataGenerator()->create_user();
        $removedtask = $this->create_task('Removed task');
        $kepttask = $this->create_task('Kept task');
        $addedtask = $this->create_task('Added task');

        $positionid = manager::create_position((object) [
            'title' => 'Original position',
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'departmentid' => $departmentid,
            'description' => '',
            'primaryuserid' => null,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ], $olddeputy->id, [$removedtask, $kepttask]);

        manager::update_position((object) [
            'id' => $positionid,
            'title' => 'Updated position',
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'departmentid' => $departmentid,
            'description' => 'Updated description',
            'primaryuserid' => null,
            'archived' => 0,
            'isvacant' => 1,
            'timestamp' => time(),
        ], $newdeputy->id, [$kepttask, $addedtask]);

        $position = $DB->get_record('local_dutydesk_position', ['id' => $positionid], '*', MUST_EXIST);
        $this->assertSame('Updated position', $position->title);
        $this->assertSame(1, (int)$position->isvacant);

        $deputy = $DB->get_record('local_dutydesk_posdeputy', ['positionid' => $positionid], '*', MUST_EXIST);
        $this->assertSame((int)$newdeputy->id, (int)$deputy->userid);

        $assignedtaskids = $DB->get_fieldset_select(
            'local_dutydesk_taskassign',
            'taskid',
            'positionid = ?',
            [$positionid]
        );
        sort($assignedtaskids);
        $expected = [$kepttask, $addedtask];
        sort($expected);
        $this->assertSame($expected, array_map('intval', $assignedtaskids));
    }

    /**
     * Tests that active positions cannot be deleted before they are archived.
     */
    public function test_delete_position_requires_archive_first(): void {
        $departmentid = $this->create_department('Department C');
        $positionid = $this->create_position($departmentid, 'Active position');

        $this->expectException(\moodle_exception::class);
        manager::delete_position($positionid);
    }

    /**
     * Tests that deleting an archived position removes dependent deputy and task assignment records.
     */
    public function test_delete_archived_position_removes_related_records(): void {
        global $DB;

        $departmentid = $this->create_department('Department D');
        $deputyuser = $this->getDataGenerator()->create_user();
        $taskid = $this->create_task('Assigned task');
        $positionid = manager::create_position((object) [
            'title' => 'Archived position',
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'departmentid' => $departmentid,
            'description' => '',
            'primaryuserid' => null,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ], $deputyuser->id, [$taskid]);

        manager::archive_position($positionid);
        manager::delete_position($positionid);

        $this->assertFalse($DB->record_exists('local_dutydesk_position', ['id' => $positionid]));
        $this->assertFalse($DB->record_exists('local_dutydesk_posdeputy', ['positionid' => $positionid]));
        $this->assertFalse($DB->record_exists('local_dutydesk_taskassign', ['positionid' => $positionid]));
    }

    /**
     * Tests that archiving and restoring a position updates the archived flags correctly.
     */
    public function test_archive_and_restore_position_updates_flags(): void {
        global $DB;

        $departmentid = $this->create_department('Department E');
        $positionid = $this->create_position($departmentid, 'Position to archive');

        manager::archive_position($positionid);
        $archived = $DB->get_record('local_dutydesk_position', ['id' => $positionid], '*', MUST_EXIST);
        $this->assertSame(1, (int)$archived->archived);
        $this->assertNotEmpty($archived->archivedtime);

        manager::restore_position($positionid);
        $restored = $DB->get_record('local_dutydesk_position', ['id' => $positionid], '*', MUST_EXIST);
        $this->assertSame(0, (int)$restored->archived);
        $this->assertEmpty($restored->archivedtime);
    }

    /**
     * Handles create_department.
     *
     * @return mixed
     */
    private function create_department(string $name): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_department', (object) [
            'name' => $name,
            'description' => '',
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles create_position.
     *
     * @return mixed
     */
    private function create_position(
        int $departmentid,
        string $title,
        string $positiontype = LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
        ?int $primaryuserid = null
    ): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_position', (object) [
            'title' => $title,
            'positiontype' => $positiontype,
            'departmentid' => $departmentid,
            'description' => '',
            'primaryuserid' => $primaryuserid,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles create_task.
     *
     * @return mixed
     */
    private function create_task(string $title): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_task', (object) [
            'title' => $title,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'categoryid' => null,
            'weight' => 0,
            'active' => 1,
            'timestamp' => time(),
        ]);
    }
}
