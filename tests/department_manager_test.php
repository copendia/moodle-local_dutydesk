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

use local_dutydesk\local\department\manager;

/**
 * Tests for department persistence operations.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class department_manager_test extends \advanced_testcase {
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
     * Tests that creating a department stores exactly one manager and assigns only allowed categories.
     */
    public function test_create_department_persists_manager_and_allowed_categories(): void {
        global $DB, $USER;

        $manageruser = $this->getDataGenerator()->create_user();
        $categoryid = $this->create_category('Assignable category');
        $otherdepartmentid = $this->create_department_record('Other department');
        $othercategoryid = $this->create_category('Other category', $otherdepartmentid);

        $departmentid = manager::create_department((object) [
            'name' => 'Created department',
            'description' => 'Created description',
            'timestamp' => time(),
        ], [$manageruser->id], [$categoryid, $othercategoryid], $USER->id);

        $department = $DB->get_record('local_dutydesk_department', ['id' => $departmentid], '*', MUST_EXIST);
        $this->assertSame('Created department', $department->name);

        $assignment = $DB->get_record('local_dutydesk_deptmgr', ['departmentid' => $departmentid], '*', MUST_EXIST);
        $this->assertSame((int)$manageruser->id, (int)$assignment->userid);
        $this->assertSame((int)$USER->id, (int)$assignment->assignedby);

        $this->assertSame($departmentid, (int)$DB->get_field('local_dutydesk_category', 'departmentid', ['id' => $categoryid]));
        $this->assertSame($otherdepartmentid, (int)$DB->get_field('local_dutydesk_category', 'departmentid', ['id' => $othercategoryid]));
    }

    /**
     * Tests that updating with null manager IDs keeps managers and synchronises category assignments.
     */
    public function test_update_department_preserves_managers_and_syncs_categories(): void {
        global $DB, $USER;

        $manageruser = $this->getDataGenerator()->create_user();
        $oldcategoryid = $this->create_category('Old category');
        $newcategoryid = $this->create_category('New category');
        $departmentid = manager::create_department((object) [
            'name' => 'Original department',
            'description' => '',
            'timestamp' => time(),
        ], [$manageruser->id], [$oldcategoryid], $USER->id);

        manager::update_department((object) [
            'id' => $departmentid,
            'name' => 'Updated department',
            'description' => 'Updated description',
            'timestamp' => time(),
        ], null, [$newcategoryid], $USER->id);

        $department = $DB->get_record('local_dutydesk_department', ['id' => $departmentid], '*', MUST_EXIST);
        $this->assertSame('Updated department', $department->name);

        $assignment = $DB->get_record('local_dutydesk_deptmgr', ['departmentid' => $departmentid], '*', MUST_EXIST);
        $this->assertSame((int)$manageruser->id, (int)$assignment->userid);

        $this->assertNull($DB->get_field('local_dutydesk_category', 'departmentid', ['id' => $oldcategoryid]));
        $this->assertSame($departmentid, (int)$DB->get_field('local_dutydesk_category', 'departmentid', ['id' => $newcategoryid]));
    }

    /**
     * Tests that deleting a department removes the department and its manager assignments.
     */
    public function test_delete_department_removes_department_and_manager_assignments(): void {
        global $DB, $USER;

        $manageruser = $this->getDataGenerator()->create_user();
        $departmentid = manager::create_department((object) [
            'name' => 'Deleted department',
            'description' => '',
            'timestamp' => time(),
        ], [$manageruser->id], [], $USER->id);

        manager::delete_department($departmentid);

        $this->assertFalse($DB->record_exists('local_dutydesk_department', ['id' => $departmentid]));
        $this->assertFalse($DB->record_exists('local_dutydesk_deptmgr', ['departmentid' => $departmentid]));
    }

    /**
     * Tests that category options include unassigned and own categories but exclude categories from other departments.
     */
    public function test_get_category_options_returns_unassigned_and_own_categories(): void {
        $departmentid = $this->create_department_record('Department A');
        $otherdepartmentid = $this->create_department_record('Department B');
        $unassignedcategoryid = $this->create_category('Unassigned category');
        $owncategoryid = $this->create_category('Own category', $departmentid);
        $othercategoryid = $this->create_category('Other category', $otherdepartmentid);

        $options = manager::get_category_options($departmentid);

        $this->assertArrayHasKey($unassignedcategoryid, $options);
        $this->assertArrayHasKey($owncategoryid, $options);
        $this->assertArrayNotHasKey($othercategoryid, $options);
    }

    /**
     * Handles create_department_record.
     *
     * @return mixed
     */
    private function create_department_record(string $name): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_department', (object) [
            'name' => $name,
            'description' => '',
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles create_category.
     *
     * @return mixed
     */
    private function create_category(string $name, ?int $departmentid = null): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_category', (object) [
            'name' => $name,
            'departmentid' => $departmentid,
        ]);
    }
}
