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
 * DutyDesk local plugin.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dutydesk\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * subtask_form implementation.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subtask_form extends \moodleform {
    /**
     * Handles definition.
     *
     * @return mixed
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $taskid = $this->_customdata['taskid'] ?? 0;
        $context = $this->_customdata['context'] ?? \context_system::instance();
        $editoroptions = $this->_customdata['editoroptions'] ?? [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => $context,
            'subdirs' => 0,
            'return_types' => FILE_INTERNAL,
        ];
        $filemanageroptions = $this->_customdata['filemanageroptions'] ?? [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => $context,
            'subdirs' => 0,
            'return_types' => FILE_INTERNAL | FILE_CONTROLLED_LINK | FILE_REFERENCE,
        ];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'taskid', $taskid);
        $mform->setType('taskid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('subtasktitle', 'local_dutydesk'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'description_editor',
            get_string('subtaskdescription', 'local_dutydesk'),
            null,
            $editoroptions
        );
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement(
            'filemanager',
            'documents_filemanager',
            get_string('subtaskdocuments', 'local_dutydesk'),
            null,
            $filemanageroptions
        );

        $this->add_action_buttons(true);
    }

    /**
     * Handles validation.
     *
     * @return mixed
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['title'] ?? '') === '') {
            $errors['title'] = get_string('required');
        }

        return $errors;
    }
}
