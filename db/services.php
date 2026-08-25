<?php
// This file is part of Moodle - https://moodle.org/
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

/**
 * External service declarations for DutyDesk.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_dutydesk_get_task_history' => [
        'classname' => 'local_dutydesk\external\get_task_history',
        'description' => 'Returns rendered task history modal content.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/dutydesk:viewown, local/dutydesk:managepositions, local/dutydesk:manageall',
    ],
];
