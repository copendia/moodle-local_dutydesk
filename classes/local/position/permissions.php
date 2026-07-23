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

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


use context;
use required_capability_exception;

/**
 * Permission checks for positions.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions {
    /**
     * Require permission to manage a concrete position.
     *
     * @param int $positionid
     * @param bool $caneditpositions
     * @param bool $canmanageall
     * @param array $manageablepositionids
     * @param context $context
     * @return void
     */
    public static function require_manage_position(
        int $positionid,
        bool $caneditpositions,
        bool $canmanageall,
        array $manageablepositionids,
        context $context
    ): void {
        if (!$caneditpositions) {
            throw new required_capability_exception($context, 'local/dutydesk:managepositions', 'nopermissions', 'local_dutydesk');
        }

        if (!$canmanageall && !in_array($positionid, $manageablepositionids, true)) {
            throw new required_capability_exception($context, 'local/dutydesk:managepositions', 'nopermissions', 'local_dutydesk');
        }
    }
}
