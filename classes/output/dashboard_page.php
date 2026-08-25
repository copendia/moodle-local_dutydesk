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


use context;
use moodle_url;

/**
 * Output helper for the assigned positions dashboard.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_page {
    /**
     * Configure the dashboard page.
     *
     * @param context $context
     * @return void
     */
    public static function setup(context $context): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/dutydesk/index.php'));
        $PAGE->set_title(get_string('assignedpositions', 'local_dutydesk'));
        $PAGE->set_heading(get_string('assignedpositionsheading', 'local_dutydesk'));
        $PAGE->add_body_class('limitedwidth');
        $PAGE->requires->js_call_amd('local_dutydesk/subtasks_toggle', 'init');
    }

    /**
     * Render the dashboard.
     *
     * @param array $templatecontext
     * @param bool $haspositions
     * @param int $totalgroups
     * @param int $page
     * @param int $perpage
     * @param moodle_url $listbaseurl
     * @return void
     */
    public static function render(
        array $templatecontext,
        bool $haspositions,
        int $totalgroups,
        int $page,
        int $perpage,
        moodle_url $listbaseurl
    ): void {
        global $OUTPUT;

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
            'isassignedpositions' => true,
            'showdepartments' => \local_dutydesk_show_departments_tab(),
            'showpositionsarchived' => \local_dutydesk_show_archived_positions_tab(),
        ]);
        if ($haspositions) {
            echo $OUTPUT->paging_bar($totalgroups, $page, $perpage, $listbaseurl);
        }
        echo $OUTPUT->render_from_template('local_dutydesk/assigned_position_list', $templatecontext);
        if ($haspositions) {
            echo $OUTPUT->paging_bar($totalgroups, $page, $perpage, $listbaseurl);
        }
        echo $OUTPUT->footer();
    }
}
