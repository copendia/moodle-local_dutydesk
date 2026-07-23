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
 * View helpers for the task import page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_import_page {
    /**
     * Render the import preview confirmation modal.
     *
     * @param array $preview
     * @return void
     */
    public static function render_preview_modal(array $preview): void {
        $confirmurl = new moodle_url('/local/dutydesk/task_import.php', [
            'action' => 'confirm',
            'token' => $preview['token'],
            'sesskey' => sesskey(),
        ]);
        $cancelurl = new moodle_url('/local/dutydesk/task_import.php', [
            'action' => 'cancel',
            'token' => $preview['token'],
            'sesskey' => sesskey(),
        ]);

        echo html_writer::start_div('modal-backdrop fade show');
        echo html_writer::end_div();
        echo html_writer::start_div('modal fade show local-dutydesk-import-modal', [
            'style' => 'display:block;',
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-modal' => 'true',
        ]);
        echo html_writer::start_div('modal-dialog modal-lg', ['role' => 'document']);
        echo html_writer::start_div('modal-content');
        echo html_writer::div(
            html_writer::tag('h5', get_string('taskimportpreview', 'local_dutydesk'), ['class' => 'modal-title']),
            'modal-header'
        );
        echo html_writer::div(
            html_writer::tag('p', get_string('taskimportsummary', 'local_dutydesk', count($preview['items']))) .
            html_writer::tag('p', get_string('taskimportsummarydepartment', 'local_dutydesk', $preview['departmentname'])) .
            self::build_warning_html($preview['warnings']),
            'modal-body'
        );
        echo html_writer::div(
            html_writer::link($cancelurl, get_string('cancel'), ['class' => 'btn btn-secondary mr-2']) .
            html_writer::link($confirmurl, get_string('taskimportconfirm', 'local_dutydesk'), ['class' => 'btn btn-primary']),
            'modal-footer'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    /**
     * Build warning HTML for the preview modal.
     *
     * @param array $warnings
     * @return string
     */
    private static function build_warning_html(array $warnings): string {
        $warningitems = [];
        foreach (array_slice($warnings, 0, 10) as $warning) {
            $warningitems[] = html_writer::tag(
                'li',
                get_string('taskimportwarningitem', 'local_dutydesk', (object)[
                    'row' => $warning['row'],
                    'title' => format_string($warning['title']),
                    'match' => format_string($warning['match']),
                ])
            );
        }

        if (empty($warningitems)) {
            return html_writer::div(get_string('taskimportnowarnings', 'local_dutydesk'), 'alert alert-success');
        }

        return html_writer::div(
            html_writer::tag('p', get_string('taskimportwarningsintro', 'local_dutydesk')) .
            html_writer::tag('ul', implode('', $warningitems)) .
            (count($warnings) > 10
                ? html_writer::tag('p', get_string('taskimportwarningsmore', 'local_dutydesk', count($warnings) - 10))
                : ''),
            'alert alert-warning'
        );
    }
}
