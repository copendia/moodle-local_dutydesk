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
 * task_import_form implementation.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_import_form extends \moodleform {
    /**
     * Handles definition.
     *
     * @return mixed
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $departmentoptions = $this->_customdata['departmentoptions'] ?? [];
        $departmentsurl = $this->_customdata['departmentsurl'] ?? '';
        $csvtemplateurl = $this->_customdata['csvtemplateurl'] ?? '';
        $xlsxtemplateurl = $this->_customdata['xlsxtemplateurl'] ?? '';

        if (empty($departmentoptions)) {
            $mform->addElement(
                'static',
                'nodepartments',
                get_string('department', 'local_dutydesk'),
                get_string('taskimportnodepartments', 'local_dutydesk') . ' ' .
                    \html_writer::link($departmentsurl, get_string('taskimportmanagedepartments', 'local_dutydesk'))
            );
        } else {
            $departmentoptions = ['' => get_string('choosedots')] + $departmentoptions;
            $mform->addElement(
                'select',
                'departmentid',
                get_string('department', 'local_dutydesk'),
                $departmentoptions
            );
            $mform->addRule('departmentid', null, 'required', null, 'client');
            $mform->setType('departmentid', PARAM_INT);
            if ($departmentsurl !== '') {
                $mform->addElement(
                    'static',
                    'managedepartments',
                    '',
                    \html_writer::link($departmentsurl, get_string('taskimportmanagedepartments', 'local_dutydesk'))
                );
            }
        }

        $mform->addElement(
            'filepicker',
            'importfile',
            get_string('taskimportfile', 'local_dutydesk'),
            null,
            [
                'maxbytes' => $CFG->maxbytes,
                'maxfiles' => 1,
            ]
        );
        $mform->addRule('importfile', null, 'required', null, 'client');

        if ($csvtemplateurl !== '' && $xlsxtemplateurl !== '') {
            $mform->addElement(
                'static',
                'taskimporttemplates',
                get_string('taskimporttemplates', 'local_dutydesk'),
                \html_writer::link(
                    $xlsxtemplateurl,
                    get_string('taskimporttemplatexlsx', 'local_dutydesk'),
                    ['download' => 'GVPL_vorlage.xlsx']
                ) . '<br>' .
                \html_writer::link(
                    $csvtemplateurl,
                    get_string('taskimporttemplatecsv', 'local_dutydesk'),
                    ['download' => 'GVPL_vorlage.csv']
                )
            );
        }

        $this->add_action_buttons(true, get_string('taskimportcheck', 'local_dutydesk'));
    }
}
