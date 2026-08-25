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

namespace local_dutydesk\local\task_import;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

use local_dutydesk\form\task_import_form;
use moodle_url;

/**
 * Factory for the task import form.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_factory {
    /**
     * Create the import form with department and template URLs.
     *
     * @param array $departmentoptions
     * @return task_import_form
     */
    public static function create(array $departmentoptions): task_import_form {
        $departmentsurl = new moodle_url('/local/dutydesk/departments.php');

        return new task_import_form(null, [
            'departmentoptions' => $departmentoptions,
            'departmentsurl' => $departmentsurl->out(false),
            'csvtemplateurl' => self::template_url('csv'),
            'xlsxtemplateurl' => self::template_url('xlsx'),
        ]);
    }

    /**
     * Build a download URL for an import template.
     *
     * @param string $template
     * @return string
     */
    private static function template_url(string $template): string {
        return (new moodle_url('/local/dutydesk/task_import.php', [
            'template' => $template,
            'sesskey' => sesskey(),
        ]))->out(false);
    }
}
