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

/**
 * DutyDesk local plugin.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
$israwajax = !empty($_GET['ajax']) || !empty($_POST['ajax']);
$israwmodaledit = !empty($_GET['modaledit']) || !empty($_POST['modaledit']);
if ($israwajax && !$israwmodaledit) {
    define('AJAX_SCRIPT', true);
}
// phpcs:enable moodle.Files.MoodleInternal.MoodleInternalGlobalState

// phpcs:disable moodle.Files.RequireLogin.Missing
require('../../config.php');
require_once(__DIR__ . '/classes/local/task/controller.php');

\local_dutydesk\local\task\controller::execute();
// phpcs:enable moodle.Files.RequireLogin.Missing
