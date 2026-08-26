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

namespace local_dutydesk\local\subtask;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;
use moodle_url;
use stdClass;

require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Renders subtask pages and embedded modal forms.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presenter {
    /**
     * Close parent modal from an embedded iframe and stop processing.
     *
     * @return void
     */
    public static function close_modal_and_exit(): void {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><body><script>'
            . 'if(window.parent&&window.parent!==window){'
            . 'window.parent.postMessage({type:"local_dutydesk_close_modal"}, window.location.origin);'
            . '}'
            . '</script></body></html>';
        die;
    }

    /**
     * Render the subtask page.
     *
     * @param \moodleform $form
     * @param stdClass $task
     * @param stdClass|null $subtask
     * @param context $context
     * @param bool $canmanageall
     * @param array $returnparams
     * @return void
     */
    public static function render(
        \moodleform $form,
        stdClass $task,
        ?stdClass $subtask,
        context $context,
        bool $canmanageall,
        array $returnparams
    ): void {
        global $OUTPUT;

        echo $OUTPUT->header();
        if (empty($returnparams['modal'])) {
            self::render_standard_page($form, $task, $subtask, $context, $canmanageall, $returnparams);
        } else {
            self::render_modal_page($form, $subtask, $canmanageall, $returnparams);
        }
        echo $OUTPUT->footer();
    }

    /**
     * Render the standard page variant.
     *
     * @param \moodleform $form
     * @param stdClass $task
     * @param stdClass|null $subtask
     * @param context $context
     * @param bool $canmanageall
     * @param array $returnparams
     * @return void
     */
    private static function render_standard_page(
        \moodleform $form,
        stdClass $task,
        ?stdClass $subtask,
        context $context,
        bool $canmanageall,
        array $returnparams
    ): void {
        global $OUTPUT;

        echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
            'istasks' => true,
            'showdepartments' => \local_dutydesk_show_departments_tab(),
            'showpositionsarchived' => \local_dutydesk_show_archived_positions_tab(),
        ]);
        echo self::render_back_link($returnparams);
        echo $OUTPUT->heading(format_string($task->title));
        $form->display();
        self::render_task_preview($task, $context, $canmanageall, $returnparams);

        if ($subtask && $canmanageall) {
            echo html_writer::div(self::render_delete_form($subtask, false), 'mt-3');
        }
    }

    /**
     * Render the embedded modal variant.
     *
     * @param \moodleform $form
     * @param stdClass|null $subtask
     * @param bool $canmanageall
     * @param array $returnparams
     * @return void
     */
    private static function render_modal_page(
        \moodleform $form,
        ?stdClass $subtask,
        bool $canmanageall,
        array $returnparams
    ): void {
        ob_start();
        $form->display();
        $subtaskformhtml = ob_get_clean();

        echo html_writer::start_div('local-dutydesk-task-modal-editor');
        echo html_writer::start_div('local-dutydesk-task-modal-card');
        echo html_writer::start_div('local-dutydesk-task-modal-section local-dutydesk-task-modal-form');
        echo $subtaskformhtml;
        echo html_writer::end_div();

        if ($subtask && $canmanageall) {
            echo html_writer::div(
                self::render_delete_form($subtask, true),
                'local-dutydesk-task-modal-section'
            );
        }

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    /**
     * Render link back to the task list.
     *
     * @param array $returnparams
     * @return string
     */
    private static function render_back_link(array $returnparams): string {
        $backurl = self::get_task_return_url($returnparams);
        $closelabel = get_string('returntotask', 'local_dutydesk');

        return html_writer::div(
            html_writer::link($backurl, $closelabel, [
                'class' => 'btn btn-outline-secondary btn-sm float-end d-inline-flex align-items-center gap-1',
                'title' => $closelabel,
            ]),
            'clearfix mb-2 text-end'
        );
    }

    /**
     * Render task preview below the standard form.
     *
     * @param stdClass $task
     * @param context $context
     * @param bool $canmanageall
     * @param array $returnparams
     * @return void
     */
    private static function render_task_preview(
        stdClass $task,
        context $context,
        bool $canmanageall,
        array $returnparams
    ): void {
        global $OUTPUT;

        $taskpreview = \local_dutydesk_build_task_display(
            $task,
            $context,
            true,
            $canmanageall,
            (int)$returnparams['page'],
            (int)$returnparams['perpage']
        );
        if (!$taskpreview) {
            return;
        }

        echo html_writer::div(
            $OUTPUT->render_from_template('local_dutydesk/task_list', [
                'displaysearch' => false,
                'tasks' => [$taskpreview],
            ]),
            'local-dutydesk-task-edit-preview mt-4'
        );
    }

    /**
     * Render the delete form.
     *
     * @param stdClass $subtask
     * @param bool $ismodal
     * @return string
     */
    private static function render_delete_form(stdClass $subtask, bool $ismodal): string {
        $deleteurl = new moodle_url('/local/dutydesk/subtask.php', [
            'id' => $subtask->id,
            'taskid' => $subtask->taskid,
            'delete' => 1,
            'modal' => $ismodal ? 1 : 0,
        ]);

        return html_writer::tag(
            'form',
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::tag('button', get_string('delete'), [
                'type' => 'submit',
                'class' => 'btn btn-outline-danger btn-sm',
            ]),
            [
                'method' => 'post',
                'action' => $deleteurl->out(false),
                'onsubmit' => "return confirm('" . get_string('confirmdelete', 'local_dutydesk') . "');",
                'class' => 'd-inline',
            ]
        );
    }

    /**
     * Build the task return URL.
     *
     * @param array $returnparams
     * @return moodle_url
     */
    public static function get_task_return_url(array $returnparams): moodle_url {
        $taskid = (int)$returnparams['taskid'];
        $focus = (int)$returnparams['focus'];
        $url = new moodle_url('/local/dutydesk/tasks.php', [
            'page' => 0,
            'perpage' => (int)$returnparams['perpage'],
            'focus' => $focus > 0 ? $focus : $taskid,
            'forcefirst' => 1,
        ]);
        $url->set_anchor('task-' . $taskid);

        return $url;
    }
}
