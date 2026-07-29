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

namespace local_dutydesk\local\task_import;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

/**
 * Loads Moodle core's bundled spreadsheet library for import/export operations.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class spreadsheet_loader {
    /**
     * Load Moodle core's PhpSpreadsheet autoloader.
     *
     * DutyDesk does not bundle Composer dependencies. Moodle 4.5 ships the
     * spreadsheet library in core, and the plugin declares that runtime
     * requirement in version.php and db/environment.xml.
     *
     * @return void
     */
    public static function load(): void {
        global $CFG;

        $autoload = $CFG->libdir . '/phpspreadsheet/vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_readable($autoload)) {
            throw new \coding_exception('Moodle core PhpSpreadsheet library is not available.');
        }

        require_once($autoload);
    }
}
