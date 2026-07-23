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

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

use local_dutydesk\local\task_import\session_store;

/**
 * Tests for pending task import session storage.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class task_import_session_store_test extends \advanced_testcase {
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
     * Tests that a pending import is stored with all data needed for the preview modal.
     */
    public function test_add_stores_pending_import_and_returns_preview(): void {
        $items = [
            [
                'row' => 2,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
        ];
        $warnings = [
            [
                'row' => 2,
                'title' => 'Posteingang fachlich bewerten',
                'match' => 'Posteingang fachlich bewerten',
            ],
        ];

        $token = session_store::add($items, $warnings, 7, 'Abteilung 7');
        $preview = session_store::get_preview($token);

        $this->assertNotEmpty($token);
        $this->assertSame($token, $preview['token']);
        $this->assertSame($items, $preview['items']);
        $this->assertSame($warnings, $preview['warnings']);
        $this->assertSame(7, $preview['departmentid']);
        $this->assertSame('Abteilung 7', $preview['departmentname']);
        $this->assertArrayHasKey('timecreated', $preview);
    }

    /**
     * Tests that a pending import can be removed after cancel or confirm.
     */
    public function test_remove_deletes_pending_import(): void {
        $token = session_store::add([], [], 3, 'Abteilung 3');

        session_store::remove($token);

        $this->assertNull(session_store::get($token));
        $this->assertSame([], session_store::get_preview($token));
    }
}
