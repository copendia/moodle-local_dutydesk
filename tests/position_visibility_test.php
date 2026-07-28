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

use local_dutydesk\local\position\visibility;

/**
 * Tests for position visibility resolution.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class position_visibility_test extends \advanced_testcase {
    /**
     * Handles setUp.
     *
     * @return mixed
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Tests that ID lists are cast to integers and duplicate values are removed.
     */
    public function test_normalize_ids_casts_unique_integer_values(): void {
        $this->assertSame([3, 2, 0, 5], visibility::normalize_ids(['3', 2, '2', 0, '5']));
    }

    /**
     * Tests that a user can see all positions and topic areas from their department.
     */
    public function test_get_viewable_position_ids_includes_positions_from_user_department(): void {
        $user = $this->getDataGenerator()->create_user();
        $departmentid = $this->create_department('Shared department');
        $otherdepartmentid = $this->create_department('Other department');

        $ownpositionid = $this->create_position($departmentid, 'Own position', LOCAL_DUTYDESK_POSITION_TYPE_POSITION, $user->id);
        $samedepartmentpositionid = $this->create_position($departmentid, 'Same department position');
        $topicareaid = $this->create_position($departmentid, 'Topic area', LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA);
        $this->create_position($otherdepartmentid, 'Other department position');

        $viewableids = visibility::get_viewable_position_ids([$ownpositionid], $user->id);
        sort($viewableids);

        $expected = [$ownpositionid, $samedepartmentpositionid, $topicareaid];
        sort($expected);
        $this->assertSame($expected, array_map('intval', $viewableids));
    }

    /**
     * Tests that topic area filtering removes regular positions from the given ID list.
     */
    public function test_filter_topic_area_ids_returns_only_topic_areas(): void {
        $departmentid = $this->create_department('Topic department');
        $positionid = $this->create_position($departmentid, 'Regular position');
        $topicareaid = $this->create_position($departmentid, 'Topic area', LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA);

        $this->assertSame([$topicareaid], visibility::filter_topic_area_ids([$positionid, $topicareaid]));
    }

    /**
     * Tests that archived department views are limited to positions in managed departments.
     */
    public function test_resolve_list_position_ids_returns_archived_department_positions_for_department_lead(): void {
        $departmentid = $this->create_department('Managed department');
        $otherdepartmentid = $this->create_department('Other department');
        $managedpositionid = $this->create_position($departmentid, 'Managed position');
        $this->create_position($otherdepartmentid, 'Other position');

        $resolvedids = visibility::resolve_list_position_ids(
            true,
            false,
            false,
            0,
            false,
            false,
            false,
            false,
            [$departmentid],
            [],
            []
        );

        $this->assertSame([$managedpositionid], $resolvedids);
    }

    /**
     * Tests that a concrete position filter is only used when the position is visible to the user.
     */
    public function test_resolve_list_position_ids_honours_position_filter_when_allowed(): void {
        $resolvedids = visibility::resolve_list_position_ids(
            false,
            false,
            false,
            42,
            false,
            false,
            false,
            false,
            [],
            [11, 42, 99],
            [11]
        );

        $this->assertSame([42], $resolvedids);
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
}
