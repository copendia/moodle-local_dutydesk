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

/**
 * DutyDesk local plugin.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dutydesk\local\task_import;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/lib.php');

use local_dutydesk\output\task_import_page;
use moodle_url;

/**
 * Controller for the task import page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Execute the task import page.
     *
     * @return void
     */
    public static function execute(): void {
        global $DB, $OUTPUT;

        $context = \context_system::instance();
        require_capability('local/dutydesk:manageall', $context);
        self::setup_page($context);

        $action = optional_param('action', '', PARAM_ALPHA);
        $token = optional_param('token', '', PARAM_ALPHANUMEXT);
        $template = optional_param('template', '', PARAM_ALPHA);

        session_store::initialise();
        template_manager::download($template);
        self::handle_session_action($action, $token);

        $departmentoptions = importer::get_department_options();
        $form = form_factory::create($departmentoptions);
        $preview = null;

        if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/dutydesk/tasks.php'));
        } else if ($data = $form->get_data()) {
            $departmentid = (int)($data->departmentid ?? 0);
            if (!$DB->record_exists('local_dutydesk_department', ['id' => $departmentid])) {
                throw new \moodle_exception('invaliddepartment', 'local_dutydesk');
            }

            $preview = self::build_preview($form, $departmentid, $departmentoptions);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
            'istasks' => true,
            'showdepartments' => \local_dutydesk_show_departments_tab(),
            'showpositionsarchived' => \local_dutydesk_show_archived_positions_tab(),
        ]);

        echo $OUTPUT->heading(get_string('taskimport', 'local_dutydesk'), 3);
        $form->display();

        if ($preview !== null) {
            task_import_page::render_preview_modal($preview);
        }

        echo $OUTPUT->footer();
    }

    /**
     * Configure the Moodle page.
     *
     * @param \context $context
     * @return void
     */
    private static function setup_page(\context $context): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/dutydesk/task_import.php'));
        $PAGE->set_title(get_string('taskimport', 'local_dutydesk'));
        $PAGE->set_heading(get_string('taskimport', 'local_dutydesk'));
        $PAGE->add_body_class('limitedwidth');
    }

    /**
     * Handle confirm/cancel actions for pending imports.
     *
     * @param string $action
     * @param string $token
     * @return void
     */
    private static function handle_session_action(string $action, string $token): void {
        if ($action === 'cancel' && confirm_sesskey()) {
            session_store::remove($token);
            redirect(new moodle_url('/local/dutydesk/task_import.php'));
        }

        if ($action !== 'confirm' || !confirm_sesskey()) {
            return;
        }

        $pendingimport = session_store::get($token);
        if (empty($pendingimport['items'])) {
            throw new \moodle_exception('invalidsesskey');
        }
        $departmentid = (int)($pendingimport['departmentid'] ?? 0);
        $result = importer::commit($pendingimport['items'], $departmentid);
        session_store::remove($token);
        redirect(
            new moodle_url('/local/dutydesk/tasks.php'),
            get_string('taskimportsuccess', 'local_dutydesk', (object)$result)
        );
    }

    /**
     * Parse uploaded file and store pending preview in the session.
     *
     * @param \local_dutydesk\form\task_import_form $form
     * @param int $departmentid
     * @param array $departmentoptions
     * @return array
     */
    private static function build_preview(
        \local_dutydesk\form\task_import_form $form,
        int $departmentid,
        array $departmentoptions
    ): array {
        $filepath = $form->save_temp_file('importfile');
        if (!$filepath) {
            throw new \moodle_exception('taskimportnofile', 'local_dutydesk');
        }

        $filename = (string)$form->get_new_filename('importfile');
        $extension = \core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            throw new \moodle_exception('taskimportinvalidfiletype', 'local_dutydesk');
        }

        $items = importer::parse_rows(importer::read_rows($filepath, $filename));
        if (empty($items)) {
            throw new \moodle_exception('taskimportempty', 'local_dutydesk');
        }

        $warnings = importer::find_warnings($items);
        $token = session_store::add($items, $warnings, $departmentid, $departmentoptions[$departmentid]);

        return session_store::get_preview($token);
    }
}
