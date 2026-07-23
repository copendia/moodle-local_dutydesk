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

use local_dutydesk\local\task\manager;

/**
 * Tests for task persistence operations.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class task_manager_test extends \advanced_testcase {
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
     * Tests that deleting a task removes the task and writes a deletion history entry.
     */
    public function test_delete_task_removes_task_and_logs_history(): void {
        global $DB;

        $taskid = $this->create_task('Task to delete');

        manager::delete_task($taskid);

        $this->assertFalse($DB->record_exists('dutydesk_task', ['id' => $taskid]));
        $this->assertTrue($DB->record_exists('dutydesk_task_history', [
            'taskid' => $taskid,
            'action' => 'deleted',
        ]));
    }

    /**
     * Tests that deleting an invalid task id is ignored.
     */
    public function test_delete_task_ignores_invalid_id(): void {
        global $DB;

        $taskcount = $DB->count_records('dutydesk_task');
        $historycount = $DB->count_records('dutydesk_task_history');

        manager::delete_task(0);

        $this->assertSame($taskcount, $DB->count_records('dutydesk_task'));
        $this->assertSame($historycount, $DB->count_records('dutydesk_task_history'));
    }

    /**
     * Handles create_task.
     *
     * @return mixed
     */
    private function create_task(string $title): int {
        global $DB;

        return (int)$DB->insert_record('dutydesk_task', (object) [
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
