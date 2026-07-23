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


$pluginroot = dirname(__DIR__, 2);
require_once($pluginroot . '/lib.php');

use html_writer;
use moodle_url;

/**
 * View helpers for the positions page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class position_page {
    /**
     * Handles render_modal_form.
     *
     * @return mixed
     */
    public static function render_modal_form(string $renderedform): void {
        global $OUTPUT;

        echo $OUTPUT->header();
        echo html_writer::start_div('local-dutydesk-task-modal-editor');
        echo html_writer::start_div('local-dutydesk-task-modal-card');
        echo html_writer::div(
            html_writer::div($renderedform, 'local-dutydesk-position-form local-dutydesk-position-form-modal mb-0'),
            'local-dutydesk-task-modal-section local-dutydesk-task-modal-form'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo $OUTPUT->footer();
        die;
    }

    /**
     * Handles render_create_buttons.
     *
     * @return mixed
     */
    public static function render_create_buttons(int $perpage, string $view): void {
        $toggleurl = new moodle_url('/local/dutydesk/positions.php', [
            'showform' => 1,
            'perpage' => $perpage,
            'view' => $view,
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
        ]);
        $togglemodalurl = new moodle_url('/local/dutydesk/positions.php', [
            'showform' => 1,
            'perpage' => $perpage,
            'view' => $view,
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_POSITION,
            'ajax' => 1,
            'modaledit' => 1,
            'sesskey' => sesskey(),
        ]);
        $topicareaurl = new moodle_url('/local/dutydesk/positions.php', [
            'showform' => 1,
            'perpage' => $perpage,
            'view' => $view,
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA,
            'topicareasonly' => 1,
        ]);
        $topicareamodalurl = new moodle_url('/local/dutydesk/positions.php', [
            'showform' => 1,
            'perpage' => $perpage,
            'view' => $view,
            'positiontype' => LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA,
            'topicareasonly' => 1,
            'ajax' => 1,
            'modaledit' => 1,
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($toggleurl, get_string('newpositionbutton', 'local_dutydesk'), [
            'class' => 'btn btn-primary mb-3 mr-2',
            'data-action' => 'new-position',
            'data-modal-url' => $togglemodalurl->out(false),
            'data-modal-title' => get_string('newpositionbutton', 'local_dutydesk'),
        ]);
        echo html_writer::link($topicareaurl, get_string('newtopicareabutton', 'local_dutydesk'), [
            'class' => 'btn btn-outline-primary mb-3',
            'data-action' => 'new-position',
            'data-modal-url' => $topicareamodalurl->out(false),
            'data-modal-title' => get_string('newtopicareabutton', 'local_dutydesk'),
        ]);
    }

    /**
     * Handles build_template_data.
     *
     * @return mixed
     */
    public static function build_template_data(
        array $positionsdata,
        string $searchvalue,
        moodle_url $searchbaseurl,
        int $perpage,
        int $page,
        string $view,
        int $positionfilterid,
        bool $showall,
        bool $toggleused,
        bool $topicareasonly,
        bool $canusepositionvisibilitytoggle,
        bool $isarchivedview,
        bool $hassearch
    ): array {
        $positiontoggleurl = new moodle_url('/local/dutydesk/positions.php', [
            'perpage' => $perpage,
            'page' => 0,
            'view' => $view,
            'toggleused' => 1,
        ]);
        if ($hassearch) {
            $positiontoggleurl->param('search', $searchvalue);
        }
        if ($positionfilterid > 0 && !$topicareasonly) {
            $positiontoggleurl->param('positionid', $positionfilterid);
        }
        if (!$showall) {
            $positiontoggleurl->param('showall', 1);
        }

        $topicareatoggleurl = new moodle_url('/local/dutydesk/positions.php', [
            'perpage' => $perpage,
            'page' => 0,
            'view' => $view,
            'toggleused' => $toggleused ? 1 : 0,
        ]);
        if ($hassearch) {
            $topicareatoggleurl->param('search', $searchvalue);
        }
        if (!$topicareasonly) {
            $topicareatoggleurl->param('topicareasonly', 1);
        } else if ($positionfilterid > 0) {
            $topicareatoggleurl->param('positionid', $positionfilterid);
        }

        return [
            'sesskey' => sesskey(),
            'displaysearch' => true,
            'cansearch' => true,
            'searchplaceholder' => get_string('searchpositionsplaceholder', 'local_dutydesk'),
            'searchvalue' => $searchvalue,
            'searchaction' => $searchbaseurl->out(false),
            'searchhiddenparams' => [
                ['name' => 'perpage', 'value' => $perpage],
                ['name' => 'page', 'value' => $page],
                ['name' => 'view', 'value' => $view],
                ['name' => 'positionid', 'value' => $positionfilterid],
                ['name' => 'showall', 'value' => $showall ? 1 : 0],
                ['name' => 'toggleused', 'value' => $toggleused ? 1 : 0],
                ['name' => 'topicareasonly', 'value' => $topicareasonly ? 1 : 0],
            ],
            'displaypositionvisibilitytoggle' => $canusepositionvisibilitytoggle,
            'showallpositionschecked' => $showall,
            'positionvisibilitytoggleurl' => $positiontoggleurl->out(false),
            'positionvisibilitylabel' => get_string($showall ? 'showownpositions' : 'showallpositions', 'local_dutydesk'),
            'displaytopicareatoggle' => !$isarchivedview,
            'topicareasonlychecked' => $topicareasonly,
            'topicareatoggleurl' => $topicareatoggleurl->out(false),
            'topicareatogglelabel' => get_string('showtopicareasonly', 'local_dutydesk'),
            'hassearch' => $hassearch,
            'positions' => $positionsdata,
        ];
    }
}
