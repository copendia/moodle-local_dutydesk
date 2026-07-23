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
 * department_form implementation.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class department_form extends \moodleform {
    /**
     * Handles manager_selector_options.
     *
     * @return mixed
     */
    private static function manager_selector_options(): array {
        return [
            'multiple' => false,
            'ajax' => 'core_user/form_user_selector',
            'noselectionstring' => get_string('nodepartmentmanagers', 'local_dutydesk'),
            'placeholder' => get_string('usersearchplaceholder', 'local_dutydesk'),
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

                $user = (object)[
                    'id' => $record->id,
                    'fullname' => fullname($record, has_capability('moodle/site:viewfullnames', $context)),
                    'extrafields' => [],
                ];

                foreach ($fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $extrafield) {
                    if (!empty($record->$extrafield)) {
                        $user->extrafields[] = (object)[
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
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $canassignmanagers = $this->_customdata['canassignmanagers'] ?? false;
        $categoryoptions = $this->_customdata['categoryoptions'] ?? [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'local_dutydesk'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'local_dutydesk'));
        $mform->setType('description', PARAM_TEXT);

        if ($canassignmanagers) {
            $managerselectoroptions = self::manager_selector_options();
            $mform->addElement(
                'autocomplete',
                'managerids',
                get_string('departmentmanagers', 'local_dutydesk'),
                [],
                $managerselectoroptions
            );
            $mform->setType('managerids', PARAM_INT);
        }

        if (!empty($categoryoptions)) {
            $mform->addElement(
                'autocomplete',
                'categoryids',
                get_string('departmentcategories', 'local_dutydesk'),
                $categoryoptions,
                [
                    'multiple' => true,
                    'noselectionstring' => get_string('departmentcategoriesnone', 'local_dutydesk'),
                    'placeholder' => get_string('departmentcategoriesplaceholder', 'local_dutydesk'),
                ]
            );
            $mform->setType('categoryids', PARAM_RAW);
        } else {
            $mform->addElement(
                'static',
                'categoryidsinfo',
                get_string('departmentcategories', 'local_dutydesk'),
                get_string('departmentcategoriesnoneavailable', 'local_dutydesk')
            );
        }

        $this->add_action_buttons(true);
    }
}
