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
use stdClass;

require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Persistence operations for subtasks.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Delete a subtask, its files and its task history entry.
     *
     * @param stdClass $subtask
     * @param context $context
     * @return void
     */
    public static function delete_subtask(stdClass $subtask, context $context): void {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_dutydesk', 'subtaskdescription', $subtask->id);
        $fs->delete_area_files($context->id, 'local_dutydesk', 'subtaskdocuments', $subtask->id);

        $detail = get_string(
            'taskhistory_detail_subtask_reference',
            'local_dutydesk',
            format_string($subtask->title ?? '')
        );
        \local_dutydesk_log_task_history((int)$subtask->taskid, 'subtask_deleted', $detail);
        $DB->delete_records('local_dutydesk_subtask', ['id' => $subtask->id]);
    }

    /**
     * Save a subtask form submission.
     *
     * @param stdClass $data
     * @param stdClass|null $subtask
     * @param int $taskid
     * @param context $context
     * @param array $editoroptions
     * @param array $filemanageroptions
     * @return array
     */
    public static function save_subtask(
        stdClass $data,
        ?stdClass $subtask,
        int $taskid,
        context $context,
        array $editoroptions,
        array $filemanageroptions
    ): array {
        global $DB;

        $record = new stdClass();
        $record->taskid = $taskid;
        $record->title = $data->title;
        $data->description_editor = $data->description_editor ?? ['text' => '', 'format' => FORMAT_HTML];

        if (!empty($data->id)) {
            $record->id = (int)$data->id;
            if ($subtask && property_exists($subtask, 'sortorder')) {
                $record->sortorder = $subtask->sortorder;
            }
            $existingdocuments = \local_dutydesk_get_subtask_document_snapshot($context->id, $record->id);
            $data = self::save_editor_files($data, $record->id, $context, $editoroptions);
            $record->description = $data->description;
            $record->descriptionformat = $data->descriptionformat;
            $DB->update_record('local_dutydesk_subtask', $record);
            self::save_document_files($data, $record->id, $context, $filemanageroptions);
            self::log_update_history($subtask, $record, $existingdocuments, $context);

            return [
                'id' => $record->id,
                'message' => get_string('subtaskupdated', 'local_dutydesk'),
            ];
        }

        $record->sortorder = repository::get_max_sortorder($taskid) + 1;
        $record->description = $data->description_editor['text'];
        $record->descriptionformat = $data->description_editor['format'];
        $subtaskid = $DB->insert_record('local_dutydesk_subtask', $record);
        $data->id = $subtaskid;
        $data = self::save_editor_files($data, $subtaskid, $context, $editoroptions);
        $DB->update_record('local_dutydesk_subtask', [
            'id' => $subtaskid,
            'description' => $data->description,
            'descriptionformat' => $data->descriptionformat,
        ]);
        self::save_document_files($data, $subtaskid, $context, $filemanageroptions);
        self::log_create_history($record, $subtaskid, $context);

        return [
            'id' => $subtaskid,
            'message' => get_string('subtaskadded', 'local_dutydesk'),
        ];
    }

    /**
     * Save editor draft files.
     *
     * @param stdClass $data
     * @param int $subtaskid
     * @param context $context
     * @param array $editoroptions
     * @return stdClass
     */
    private static function save_editor_files(
        stdClass $data,
        int $subtaskid,
        context $context,
        array $editoroptions
    ): stdClass {
        return file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $context,
            'local_dutydesk',
            'subtaskdescription',
            $subtaskid
        );
    }

    /**
     * Save filemanager draft files.
     *
     * @param stdClass $data
     * @param int $subtaskid
     * @param context $context
     * @param array $filemanageroptions
     * @return void
     */
    private static function save_document_files(
        stdClass $data,
        int $subtaskid,
        context $context,
        array $filemanageroptions
    ): void {
        file_save_draft_area_files(
            $data->documents_filemanager ?? 0,
            $context->id,
            'local_dutydesk',
            'subtaskdocuments',
            $subtaskid,
            $filemanageroptions
        );
    }

    /**
     * Log changed fields for an updated subtask.
     *
     * @param stdClass|null $subtask
     * @param stdClass $record
     * @param array $existingdocuments
     * @param context $context
     * @return void
     */
    private static function log_update_history(
        ?stdClass $subtask,
        stdClass $record,
        array $existingdocuments,
        context $context
    ): void {
        if (!$subtask) {
            return;
        }

        $changes = [];
        if (trim((string)$subtask->title) !== trim((string)$record->title)) {
            $changes[] = get_string('taskhistory_detail_subtask_title', 'local_dutydesk', (object)[
                'old' => format_string($subtask->title ?? ''),
                'new' => format_string($record->title ?? ''),
            ]);
        }
        if (trim((string)($subtask->description ?? '')) !== trim((string)($record->description ?? ''))) {
            $changes[] = get_string('taskhistory_detail_subtask_description', 'local_dutydesk');
        }

        $afterdocs = \local_dutydesk_get_subtask_document_snapshot($context->id, $record->id);
        $docdetails = \local_dutydesk_describe_document_changes($existingdocuments, $afterdocs);
        if ($docdetails !== '') {
            $changes[] = $docdetails;
        }
        if (!empty($changes)) {
            \local_dutydesk_log_task_history((int)$record->taskid, 'subtask_updated', implode("\n", $changes));
        }
    }

    /**
     * Log details for a created subtask.
     *
     * @param stdClass $record
     * @param int $subtaskid
     * @param context $context
     * @return void
     */
    private static function log_create_history(stdClass $record, int $subtaskid, context $context): void {
        $details = [
            get_string('taskhistory_detail_subtask_reference', 'local_dutydesk', format_string($record->title ?? '')),
        ];
        $afterdocs = \local_dutydesk_get_subtask_document_snapshot($context->id, $subtaskid);
        $docdetails = \local_dutydesk_describe_document_changes([], $afterdocs);
        if ($docdetails !== '') {
            $details[] = $docdetails;
        }
        \local_dutydesk_log_task_history((int)$record->taskid, 'subtask_created', implode("\n", $details));
    }
}
