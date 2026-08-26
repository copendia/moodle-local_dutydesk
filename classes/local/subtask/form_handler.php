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
use moodle_url;
use stdClass;

/**
 * Builds and prepares subtask forms.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_handler {
    /**
     * Create the subtask form.
     *
     * @param moodle_url $formaction
     * @param int $taskid
     * @param context $context
     * @param array $editoroptions
     * @param array $filemanageroptions
     * @return \local_dutydesk\form\subtask_form
     */
    public static function create_form(
        moodle_url $formaction,
        int $taskid,
        context $context,
        array $editoroptions,
        array $filemanageroptions
    ): \local_dutydesk\form\subtask_form {
        return new \local_dutydesk\form\subtask_form($formaction->out(false), [
            'taskid' => $taskid,
            'context' => $context,
            'editoroptions' => $editoroptions,
            'filemanageroptions' => $filemanageroptions,
        ]);
    }

    /**
     * Prepare existing subtask data for editor and filemanager elements.
     *
     * @param \local_dutydesk\form\subtask_form $form
     * @param stdClass|null $subtask
     * @param context $context
     * @param array $editoroptions
     * @param array $filemanageroptions
     * @return void
     */
    public static function set_existing_data(
        \local_dutydesk\form\subtask_form $form,
        ?stdClass $subtask,
        context $context,
        array $editoroptions,
        array $filemanageroptions
    ): void {
        if (!$subtask) {
            return;
        }

        if (!isset($subtask->description)) {
            $subtask->description = '';
        }
        if (!isset($subtask->descriptionformat)) {
            $subtask->descriptionformat = FORMAT_HTML;
        }

        $subtask = file_prepare_standard_editor(
            $subtask,
            'description',
            $editoroptions,
            $context,
            'local_dutydesk',
            'subtaskdescription',
            $subtask->id
        );
        $subtask = file_prepare_standard_filemanager(
            $subtask,
            'documents',
            $filemanageroptions,
            $context,
            'local_dutydesk',
            'subtaskdocuments',
            $subtask->id
        );
        $form->set_data($subtask);
    }
}
