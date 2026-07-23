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

use local_dutydesk\local\department\presenter;
use local_dutydesk\local\department\repository;

/**
 * Tests for department list read models and presentation data.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class department_repository_presenter_test extends \advanced_testcase {
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
     * Tests that admin pagination returns sorted department records and correct page metadata.
     */
    public function test_get_paginated_departments_returns_admin_page(): void {
        $this->create_department('Alpha');
        $this->create_department('Bravo');
        $this->create_department('Charlie');

        $result = repository::get_paginated_departments(true, [], 0, 2);

        $this->assertSame(3, $result->totaldepartments);
        $this->assertSame(0, $result->page);
        $this->assertSame(['Alpha', 'Bravo'], $this->get_department_names($result->records));
    }

    /**
     * Tests that requesting a page beyond the result set moves back to the last valid page.
     */
    public function test_get_paginated_departments_rewinds_overflow_page(): void {
        $this->create_department('Alpha');
        $this->create_department('Bravo');
        $this->create_department('Charlie');

        $result = repository::get_paginated_departments(true, [], 99, 2);

        $this->assertSame(1, $result->page);
        $this->assertSame(['Charlie'], $this->get_department_names($result->records));
    }

    /**
     * Tests that non-admin department lists are limited to managed department IDs.
     */
    public function test_get_paginated_departments_limits_to_managed_departments(): void {
        $manageddepartmentid = $this->create_department('Managed department');
        $this->create_department('Hidden department');

        $result = repository::get_paginated_departments(false, [$manageddepartmentid], 0, 10);

        $this->assertSame(1, $result->totaldepartments);
        $this->assertCount(1, $result->records);
        $records = array_values($result->records);
        $this->assertSame($manageddepartmentid, (int)$records[0]->id);
    }

    /**
     * Tests that repository helper queries group positions and managers by department ID.
     */
    public function test_repository_groups_positions_and_managers_by_department(): void {
        global $DB;

        $departmentid = $this->create_department('Grouped department');
        $otherdepartmentid = $this->create_department('Other department');
        $positionid = $this->create_position($departmentid, 'Grouped position');
        $this->create_position($otherdepartmentid, 'Other position');
        $manageruser = $this->getDataGenerator()->create_user();
        $DB->insert_record('dutydesk_department_manager', (object) [
            'departmentid' => $departmentid,
            'userid' => $manageruser->id,
            'assignedby' => $manageruser->id,
            'timecreated' => time(),
        ]);

        $positions = repository::get_positions_by_department([$departmentid]);
        $managers = repository::get_managers_by_department([$departmentid]);

        $this->assertSame($positionid, (int)$positions[$departmentid][0]->id);
        $this->assertSame((int)$manageruser->id, (int)$managers[$departmentid][0]->id);
    }

    /**
     * Tests that presenter data exposes edit/delete permissions and linked child records.
     */
    public function test_presenter_builds_department_template_data(): void {
        global $DB;

        $context = \context_system::instance();
        $departmentid = $this->create_department('Presented department', 'Department description');
        $positionid = $this->create_position($departmentid, 'Presented position');
        $manageruser = $this->getDataGenerator()->create_user([
            'firstname' => 'Dana',
            'lastname' => 'Manager',
        ]);
        $DB->insert_record('dutydesk_department_manager', (object) [
            'departmentid' => $departmentid,
            'userid' => $manageruser->id,
            'assignedby' => $manageruser->id,
            'timecreated' => time(),
        ]);

        $department = $DB->get_record('dutydesk_department', ['id' => $departmentid], '*', MUST_EXIST);
        $data = presenter::build(
            [$departmentid => $department],
            $context,
            [$departmentid => [(object) ['id' => $positionid, 'title' => 'Presented position']]],
            [$departmentid => [$manageruser]],
            false,
            [$departmentid]
        );

        $this->assertCount(1, $data);
        $this->assertSame('Presented department', $data[0]['name']);
        $this->assertTrue($data[0]['canmanage']);
        $this->assertFalse($data[0]['candelete']);
        $this->assertTrue($data[0]['haspositions']);
        $this->assertSame('Presented position', $data[0]['positions'][0]['title']);
        $this->assertStringContainsString('position-' . $positionid, $data[0]['positions'][0]['url']);
        $this->assertTrue($data[0]['hasmanagers']);
        $this->assertStringContainsString('Dana', $data[0]['managers'][0]['name']);
    }

    /**
     * Handles create_department.
     *
     * @return mixed
     */
    private function create_department(string $name, string $description = ''): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_department', (object) [
            'name' => $name,
            'description' => $description,
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles create_position.
     *
     * @return mixed
     */
    private function create_position(int $departmentid, string $title): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_position', (object) [
            'title' => $title,
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'departmentid' => $departmentid,
            'description' => '',
            'primaryuserid' => null,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ]);
    }

    /**
     * Return department names from a Moodle record list in display order.
     *
     * @param array $records
     * @return string[]
     */
    private function get_department_names(array $records): array {
        return array_values(array_map(static function ($department) {
            return $department->name;
        }, $records));
    }
}
