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

use local_dutydesk\local\dashboard\presenter;

/**
 * Tests for dashboard template data generation.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class dashboard_presenter_test extends \advanced_testcase {
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
     * Tests that own positions, topic areas and assigned tasks are prepared for the dashboard.
     */
    public function test_build_returns_grouped_positions_topic_areas_and_tasks(): void {
        $user = $this->getDataGenerator()->create_user();
        $departmentid = $this->create_department('Department A');
        $positionid = $this->create_position($departmentid, 'Own position', LOCAL_DUTYDESK_POSITION_TYPE_POSITION, $user->id);
        $topicareaid = $this->create_position($departmentid, 'Topic area', LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA);
        $taskid = $this->create_task('Assigned task', 'Task description');
        $this->assign_task($taskid, $positionid, 25);

        $data = presenter::build(\context_system::instance(), (int)$user->id, 0, 10);

        $this->assertTrue($data['haspositions']);
        $this->assertSame(1, $data['totalgroups']);
        $this->assertSame(0, $data['page']);
        $this->assertSame(10, $data['perpage']);

        $department = $data['templatecontext']['departments'][0];
        $this->assertSame('Department A', strip_tags($department['name']));
        $this->assertTrue($department['hasownpositions']);

        $ownpositions = $department['ownpositions'];
        $ownpositiontitles = array_column($ownpositions, 'title');
        $this->assertContains('Own position', $ownpositiontitles);
        $this->assertContains('Topic area', $ownpositiontitles);

        $ownposition = $this->find_position_by_title($ownpositions, 'Own position');
        $this->assertTrue($ownposition['isown']);
        $this->assertFalse($ownposition['istopicarea']);
        $this->assertSame(1, $ownposition['taskcount']);
        $this->assertSame(25, $ownposition['workloadtotal']);
        $this->assertSame('25%', $ownposition['workloadtotaldisplay']);
        $this->assertSame('Assigned task', $ownposition['tasks'][0]['title']);
        $this->assertSame('25%', $ownposition['tasks'][0]['workloaddisplay']);
        $this->assertTrue($ownposition['tasks'][0]['hasdescription']);

        $topicarea = $this->find_position_by_title($ownpositions, 'Topic area');
        $this->assertTrue($topicarea['istopicarea']);
    }

    /**
     * Tests that dashboard pagination is calculated over department groups.
     */
    public function test_build_paginates_department_groups(): void {
        $user = $this->getDataGenerator()->create_user();
        $firstdepartmentid = $this->create_department('Department A');
        $seconddepartmentid = $this->create_department('Department B');
        $this->create_position($firstdepartmentid, 'Position A', LOCAL_DUTYDESK_POSITION_TYPE_POSITION, $user->id);
        $this->create_position($seconddepartmentid, 'Position B', LOCAL_DUTYDESK_POSITION_TYPE_POSITION, $user->id);

        $data = presenter::build(\context_system::instance(), (int)$user->id, 0, 1);

        $this->assertTrue($data['haspositions']);
        $this->assertSame(2, $data['totalgroups']);
        $this->assertCount(1, $data['templatecontext']['departments']);
    }

    /**
     * Find one prepared position by title.
     *
     * @param array $positions
     * @param string $title
     * @return array
     */
    private function find_position_by_title(array $positions, string $title): array {
        foreach ($positions as $position) {
            if ($position['title'] === $title) {
                return $position;
            }
        }

        $this->fail('Expected dashboard position not found: ' . $title);
    }

    /**
     * Handles create_department.
     *
     * @return mixed
     */
    private function create_department(string $name): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_department', (object) [
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
        string $positiontype,
        ?int $primaryuserid = null
    ): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_position', (object) [
            'title' => $title,
            'positiontype' => $positiontype,
            'departmentid' => $departmentid,
            'description' => $positiontype === LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA ? 'Topic description' : '',
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
    private function create_task(string $title, string $description = ''): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_task', (object) [
            'title' => $title,
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'categoryid' => null,
            'weight' => 0,
            'active' => 1,
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles assign_task.
     *
     * @return mixed
     */
    private function assign_task(int $taskid, int $positionid, int $workloadpercent): int {
        global $DB, $USER;

        return (int)$DB->insert_record('dutydesk_taskassignment', (object) [
            'taskid' => $taskid,
            'positionid' => $positionid,
            'workloadpercent' => $workloadpercent,
            'assignedby' => $USER->id,
            'timestamp' => time(),
        ]);
    }
}
