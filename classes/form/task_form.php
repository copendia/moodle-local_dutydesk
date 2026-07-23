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
require_once("$CFG->libdir/filelib.php");
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/local/dutydesk/lib.php');

/**
 * task_form implementation.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_form extends \moodleform {
    /**
     * Handles definition.
     *
     * @return mixed
     */
    public function definition() {
        global $DB, $CFG, $USER;

        $mform = $this->_form;
        $mform->disable_form_change_checker();
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

        $userid = $this->_customdata['userid'] ?? $USER->id;
        $canmanageall = $this->_customdata['canmanageall'] ?? has_capability('local/dutydesk:manageall', $context);
        $canmanageworkload = $this->_customdata['canmanageworkload'] ?? false;

        $mform->addElement('text', 'title', get_string('name', 'local_dutydesk'));
        $mform->setType('title', PARAM_TEXT);
        $canedittitle = \local_dutydesk_user_can_manage_departments((int)$userid) || $canmanageall;
        if ($canedittitle) {
            $mform->addRule('title', null, 'required', null, 'client');
        } else {
            $mform->freeze('title');
        }

        if ($canmanageall) {
            $positionoptions = $DB->get_records_menu('dutydesk_position', null, 'title ASC', 'id, title');
        } else {
            $positionids = local_dutydesk_get_manageable_position_ids((int)$userid);
            if (!empty($positionids)) {
                $positionrecords = $DB->get_records_list('dutydesk_position', 'id', $positionids, 'title ASC', 'id, title');
                $positionoptions = [];
                foreach ($positionrecords as $record) {
                    $positionoptions[$record->id] = \format_string($record->title);
                }
            } else {
                $positionoptions = [];
            }
        }

        $positionoptions = ['' => get_string('choosedots', 'moodle')] + $positionoptions;
        $mform->addElement('select', 'positionid', get_string('position', 'local_dutydesk'), $positionoptions);
        $mform->setType('positionid', PARAM_INT);
        if (!$canmanageall) {
            $mform->freeze('positionid');
        }

        $mform->addElement('text', 'workloadpercent', get_string('taskworkloadpercent', 'local_dutydesk'));
        $mform->setType('workloadpercent', PARAM_INT);
        $mform->setDefault('workloadpercent', '');
        $mform->addHelpButton('workloadpercent', 'taskworkloadpercent', 'local_dutydesk');
        if (!$canmanageworkload) {
            $mform->freeze('workloadpercent');
        }

        $mform->addElement(
            'editor',
            'description_editor',
            get_string('description', 'local_dutydesk'),
            null,
            $editoroptions
        );
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement(
            'filemanager',
            'documents_filemanager',
            get_string('taskdocuments', 'local_dutydesk'),
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

        if (isset($data['workloadpercent']) && $data['workloadpercent'] !== '' && $data['workloadpercent'] !== null) {
            $value = local_dutydesk_normalize_workload_value($data['workloadpercent']);
            if ($value === null && $data['workloadpercent'] !== '') {
                $errors['workloadpercent'] = get_string('taskworkloadpercentinvalid', 'local_dutydesk');
            } else if ($value !== null && ($value < 0 || $value > 100)) {
                $errors['workloadpercent'] = get_string('taskworkloadpercentinvalid', 'local_dutydesk');
            }
        }

        return $errors;
    }
}
