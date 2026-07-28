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

use local_dutydesk\local\dashboard\manager;

/**
 * Tests for dashboard actions.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class dashboard_manager_test extends \advanced_testcase {
    /**
     * Handles setUp.
     *
     * @return mixed
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $_POST = [];
    }

    /**
     * Tests that vacancy toggles are ignored when an invalid sesskey was submitted.
     */
    public function test_handle_vacancy_toggle_ignores_invalid_sesskey(): void {
        global $DB, $USER;

        $departmentid = $this->create_department('Department A');
        $positionid = $this->create_position($departmentid, 'Position A', LOCAL_DUTYDESK_POSITION_TYPE_POSITION);
        $_POST['sesskey'] = 'invalid';

        manager::handle_vacancy_toggle(\context_system::instance(), (int)$USER->id, 0, 10, $positionid, true);

        $this->assertSame(0, (int)$DB->get_field('local_dutydesk_position', 'isvacant', ['id' => $positionid]));
    }

    /**
     * Tests that topic areas cannot be marked as vacant from the dashboard.
     */
    public function test_handle_vacancy_toggle_rejects_topic_area(): void {
        global $USER;

        $departmentid = $this->create_department('Department B');
        $topicareaid = $this->create_position($departmentid, 'Topic area', LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA);
        $_POST['sesskey'] = sesskey();

        $this->expectException(\moodle_exception::class);
        manager::handle_vacancy_toggle(\context_system::instance(), (int)$USER->id, 0, 10, $topicareaid, true);
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
    private function create_position(int $departmentid, string $title, string $positiontype): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_position', (object) [
            'title' => $title,
            'positiontype' => $positiontype,
            'departmentid' => $departmentid,
            'description' => '',
            'primaryuserid' => null,
            'archived' => 0,
            'isvacant' => 0,
            'timestamp' => time(),
        ]);
    }
}
