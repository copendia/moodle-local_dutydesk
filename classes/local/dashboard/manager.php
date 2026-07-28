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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_dutydesk\local\dashboard;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


use context;
use moodle_exception;
use moodle_url;
use required_capability_exception;

/**
 * Handles dashboard actions.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Toggle the vacancy state of a position from the dashboard.
     *
     * @param context $context
     * @param int $userid
     * @param int $page
     * @param int $perpage
     * @param int $positionid
     * @param bool $vacant
     * @return void
     */
    public static function handle_vacancy_toggle(
        context $context,
        int $userid,
        int $page,
        int $perpage,
        int $positionid,
        bool $vacant
    ): void {
        global $DB;

        if ($positionid <= 0 || !confirm_sesskey()) {
            return;
        }

        $positionrecord = $DB->get_record(
            'local_dutydesk_position',
            ['id' => $positionid],
            'id, departmentid, positiontype',
            IGNORE_MISSING
        );
        if (!$positionrecord) {
            throw new moodle_exception('invalidrecord', 'error');
        }
        if (\local_dutydesk_position_is_topic_area($positionrecord)) {
            throw new moodle_exception('invalidrecord', 'error');
        }
        if (!\local_dutydesk_user_manages_department((int)$positionrecord->departmentid, $userid)) {
            throw new required_capability_exception(
                $context,
                'local/dutydesk:managepositions',
                'nopermissions',
                'local_dutydesk'
            );
        }

        $DB->set_field('local_dutydesk_position', 'isvacant', $vacant ? 1 : 0, ['id' => $positionid]);
        redirect(new moodle_url('/local/dutydesk/index.php', [
            'page' => $page,
            'perpage' => $perpage,
        ]), get_string('updated', 'local_dutydesk'));
    }
}
