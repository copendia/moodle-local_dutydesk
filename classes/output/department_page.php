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

namespace local_dutydesk\output;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


use html_writer;
use moodle_url;

/**
 * View helpers for the departments page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class department_page {
    /**
     * Render the new department button.
     *
     * @param int $perpage
     * @return void
     */
    public static function render_create_button(int $perpage): void {
        $toggleurl = new moodle_url('/local/dutydesk/departments.php', [
            'showform' => 1,
            'perpage' => $perpage,
        ]);
        $modalurl = new moodle_url('/local/dutydesk/departments.php', [
            'showform' => 1,
            'perpage' => $perpage,
            'ajax' => 1,
            'modaledit' => 1,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($toggleurl, get_string('newdepartmentbutton', 'local_dutydesk'), [
            'class' => 'btn btn-primary mb-3',
            'data-action' => 'new-department',
            'data-modal-url' => $modalurl->out(false),
            'data-modal-title' => get_string('newdepartmentbutton', 'local_dutydesk'),
        ]);
    }

    /**
     * Render a department form block.
     *
     * @param string $renderedform
     * @return void
     */
    public static function render_form(string $renderedform): void {
        echo html_writer::div($renderedform, 'local-dutydesk-department-form mb-4');
    }

    /**
     * Render the department form inside an embedded modal page.
     *
     * @param string $renderedform
     * @return void
     */
    public static function render_modal_form(string $renderedform): void {
        global $OUTPUT;

        echo $OUTPUT->header();
        echo html_writer::start_div('local-dutydesk-task-modal-editor');
        echo html_writer::start_div('local-dutydesk-task-modal-card');
        echo html_writer::div(
            html_writer::div($renderedform, 'local-dutydesk-department-form local-dutydesk-department-form-modal mb-0'),
            'local-dutydesk-task-modal-section local-dutydesk-task-modal-form'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo $OUTPUT->footer();
        die;
    }
}
