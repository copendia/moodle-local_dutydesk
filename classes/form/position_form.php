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
 * position_form implementation.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class position_form extends \moodleform {
    /**
     * Return common configuration for the user selector.
     *
     * @return array
     */
    private static function user_selector_options(): array {
        return [
            'multiple' => false,
            'ajax' => 'core_user/form_user_selector',
            'placeholder' => get_string('usersearchplaceholder', 'local_dutydesk'),
            'noselectionstring' => get_string('usersearchnoselection', 'local_dutydesk'),
            'valuehtmlcallback' => static function ($userid) {
                global $OUTPUT;

                if (empty($userid)) {
                    return '';
                }

                $context = \context_system::instance();
                $fields = \core_user\fields::for_name()->with_identity($context, false);
                $record = \core_user::get_user($userid, 'id ' . $fields->get_sql()->selects, IGNORE_MISSING);

                if (!$record) {
                    return '';
                }

                $user = (object) [
                    'id' => $record->id,
                    'fullname' => fullname($record, has_capability('moodle/site:viewfullnames', $context)),
                    'extrafields' => [],
                ];

                foreach ($fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $extrafield) {
                    if (!empty($record->$extrafield)) {
                        $user->extrafields[] = (object) [
                            'name' => $extrafield,
                            'value' => s($record->$extrafield),
                        ];
                    }
                }

                return $OUTPUT->render_from_template('core_user/form_user_selector_suggestion', $user);
            },
        ];
    }

    /**
     * Handles definition.
     *
     * @return mixed
     */
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $departmentoptions = $this->_customdata['departmentoptions'] ?? [];
        $canmanageall = $this->_customdata['canmanageall'] ?? false;
        $taskoptions = $this->_customdata['taskoptions'] ?? [];
        $taskcategoryoptions = $this->_customdata['taskcategoryoptions'] ?? [];
        $taskcategorydepartments = $this->_customdata['taskcategorydepartments'] ?? [];
        $showtaskassignment = $this->_customdata['showtaskassignment'] ?? true;
        $defaultpositiontype = $this->_customdata['defaultpositiontype'] ?? 'position';
        $defaultpositiontype = $defaultpositiontype === 'topicarea' ? 'topicarea' : 'position';

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'positiontype', $defaultpositiontype);
        $mform->setType('positiontype', PARAM_ALPHA);

        $mform->addElement('text', 'title', get_string('name', 'local_dutydesk'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        if (empty($departmentoptions)) {
            $departmentoptions = $DB->get_records_menu('local_dutydesk_department', null, 'name ASC', 'id, name');
        }
        $departmentoptions = array_map(static function ($name) {
            return \format_string($name);
        }, $departmentoptions);
        $departmentoptions = ['' => get_string('choosedots', 'moodle')] + $departmentoptions;

        $mform->addElement('select', 'departmentid', get_string('departments', 'local_dutydesk'), $departmentoptions);
        $mform->addRule('departmentid', null, 'required', null, 'client');
        $mform->setType('departmentid', PARAM_INT);
        if (!$canmanageall && count($departmentoptions) === 2) {
            $keys = array_keys($departmentoptions);
            $default = end($keys);
            if ((int)$default > 0) {
                $mform->setDefault('departmentid', (int)$default);
            }
        }

        $mform->addElement('textarea', 'description', get_string('description', 'local_dutydesk'));
        $mform->setType('description', PARAM_TEXT);

        $userselectoroptions = self::user_selector_options();
        $mform->addElement(
            'autocomplete',
            'primaryuserid',
            get_string('positionprimaryuser', 'local_dutydesk'),
            [],
            $userselectoroptions
        );
        $mform->setType('primaryuserid', PARAM_INT);
        $mform->hideIf('primaryuserid', 'positiontype', 'eq', 'topicarea');

        $mform->addElement(
            'autocomplete',
            'deputyuserid',
            get_string('positiondeputyuser', 'local_dutydesk'),
            [],
            $userselectoroptions
        );
        $mform->setType('deputyuserid', PARAM_INT);
        $mform->hideIf('deputyuserid', 'positiontype', 'eq', 'topicarea');

        $mform->addElement(
            'advcheckbox',
            'isvacant',
            get_string('positionvacantlabel', 'local_dutydesk'),
            get_string('positionvacanthelp', 'local_dutydesk')
        );
        $mform->setType('isvacant', PARAM_BOOL);
        $mform->setDefault('isvacant', 0);
        $mform->hideIf('isvacant', 'positiontype', 'eq', 'topicarea');

        $taskselectoroptions = [
            'multiple' => true,
            'noselectionstring' => get_string('positiontasksnoselection', 'local_dutydesk'),
            'placeholder' => get_string('positiontasksplaceholder', 'local_dutydesk'),
        ];

        if ($showtaskassignment && !empty($taskcategoryoptions)) {
            $chips = [];
            $chips[] = \html_writer::tag(
                'button',
                get_string('taskcategoryfilter_all', 'local_dutydesk'),
                [
                    'type' => 'button',
                    'class' => 'local-dutydesk-filter-chip local-dutydesk-filter-chip--active',
                    'data-taskcat-chip' => 'all',
                    'aria-pressed' => 'true',
                ]
            );

            foreach ($taskcategoryoptions as $categoryid => $categoryname) {
                if ((int)$categoryid <= 0 || trim((string)$categoryname) === '') {
                    continue;
                }
                $chips[] = \html_writer::tag(
                    'button',
                    \format_string($categoryname),
                    [
                        'type' => 'button',
                        'class' => 'local-dutydesk-filter-chip',
                        'data-taskcat-chip' => (string)$categoryid,
                        'data-taskcat-department' => (string)($taskcategorydepartments[$categoryid] ?? 0),
                        'aria-pressed' => 'false',
                    ]
                );
            }

            if (!empty($chips)) {
                $mform->addElement(
                    'static',
                    'taskcategoryfilterchips',
                    get_string('taskcategoryfilter', 'local_dutydesk'),
                    \html_writer::tag(
                        'div',
                        implode('', $chips),
                        [
                            'class' => 'local-dutydesk-filter-chip-group local-dutydesk-position-task-category-chips',
                            'data-region' => 'position-task-category-chips',
                        ]
                    )
                );
            }
        }

        if ($showtaskassignment) {
            $mform->addElement(
                'autocomplete',
                'taskids',
                get_string('positiontasks', 'local_dutydesk'),
                $taskoptions,
                $taskselectoroptions
            );
            $mform->setType('taskids', PARAM_RAW);
        }

        $this->add_action_buttons(true);
    }
}
