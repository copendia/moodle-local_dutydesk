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

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Persistence operations for duty desk positions.
 *
 * Permission checks stay in the calling page for now. This service only keeps
 * the data modifications in one place.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Delete a position and its direct assignments.
     *
     * @param int $positionid
     * @return void
     */
    public static function delete_position(int $positionid): void {
        global $DB;

        if ($positionid <= 0) {
            return;
        }

        $position = $DB->get_record('local_dutydesk_position', ['id' => $positionid], 'id, archived');
        if (!$position) {
            return;
        }
        if (empty($position->archived)) {
            throw new \moodle_exception('positiondeleterequiresarchive', 'local_dutydesk');
        }

        $DB->delete_records('local_dutydesk_position', ['id' => $positionid]);
        $DB->delete_records('local_dutydesk_posdeputy', ['positionid' => $positionid]);
        $DB->delete_records('local_dutydesk_taskassign', ['positionid' => $positionid]);
    }

    /**
     * Archive a position.
     *
     * @param int $positionid
     * @return void
     */
    public static function archive_position(int $positionid): void {
        self::set_archived($positionid, true);
    }

    /**
     * Restore an archived position.
     *
     * @param int $positionid
     * @return void
     */
    public static function restore_position(int $positionid): void {
        self::set_archived($positionid, false);
    }

    /**
     * Create a position and persist its related assignments.
     *
     * @param \stdClass $position
     * @param int $deputyuserid
     * @param array $taskids
     * @return int
     */
    public static function create_position(\stdClass $position, int $deputyuserid, array $taskids): int {
        global $DB;

        $positionid = (int)$DB->insert_record('local_dutydesk_position', $position);
        self::save_deputy($positionid, $deputyuserid);
        \local_dutydesk_sync_position_tasks($positionid, $taskids);

        return $positionid;
    }

    /**
     * Update a position and persist its related assignments.
     *
     * @param \stdClass $position
     * @param int $deputyuserid
     * @param array $taskids
     * @return void
     */
    public static function update_position(\stdClass $position, int $deputyuserid, array $taskids): void {
        global $DB;

        $DB->update_record('local_dutydesk_position', $position);
        self::save_deputy((int)$position->id, $deputyuserid);
        \local_dutydesk_sync_position_tasks((int)$position->id, $taskids);
    }

    /**
     * Persist the deputy assignment for a given position.
     *
     * @param int $positionid
     * @param int $userid
     * @return void
     */
    public static function save_deputy(int $positionid, int $userid): void {
        global $DB, $USER;

        if ($positionid <= 0) {
            return;
        }

        $existing = $DB->get_record('local_dutydesk_posdeputy', ['positionid' => $positionid]);

        if ($userid > 0) {
            $record = (object) [
                'positionid' => $positionid,
                'userid' => $userid,
                'assignedby' => $USER->id,
                'timecreated' => time(),
            ];

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('local_dutydesk_posdeputy', $record);
            } else {
                $DB->insert_record('local_dutydesk_posdeputy', $record);
            }
        } else if ($existing) {
            $DB->delete_records('local_dutydesk_posdeputy', ['id' => $existing->id]);
        }
    }

    /**
     * Update the archived state for a position.
     *
     * @param int $positionid
     * @param bool $archived
     * @return void
     */
    private static function set_archived(int $positionid, bool $archived): void {
        global $DB;

        if ($positionid <= 0) {
            return;
        }

        $DB->update_record('local_dutydesk_position', (object) [
            'id' => $positionid,
            'archived' => $archived ? 1 : 0,
            'archivedtime' => $archived ? time() : null,
        ]);
    }
}
