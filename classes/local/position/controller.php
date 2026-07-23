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

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/classes/local/position/form_handler.php');
require_once($pluginroot . '/classes/local/position/permissions.php');
require_once($pluginroot . '/classes/local/position/repository.php');
require_once($pluginroot . '/classes/local/position/manager.php');
require_once($pluginroot . '/classes/local/position/visibility.php');
require_once($pluginroot . '/classes/local/position/presenter.php');
require_once($pluginroot . '/classes/output/position_page.php');
require_once($pluginroot . '/lib.php');

use context;
use core_text;
use html_writer;
use local_dutydesk\output\position_page;
use moodle_url;

/**
 * Controller for the positions page.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Handles execute.
     *
     * @return mixed
     */
    public static function execute(): void {
        global $DB, $OUTPUT, $PAGE, $USER;

        $context = \context_system::instance();
        self::setup_page($context);

        $canmanageall = has_capability('local/dutydesk:manageall', $context);
        $canmanagepositions = has_capability('local/dutydesk:managepositions', $context);
        $canviewallpositions = has_capability('local/dutydesk:viewall', $context);
        $canviewownpositions = has_capability('local/dutydesk:viewown', $context);
        $manageddepartmentids = \local_dutydesk_get_managed_department_ids($USER->id);
        $candepartmentleadmanage = \local_dutydesk_user_can_manage_departments($USER->id) && !empty($manageddepartmentids);
        if (!$canmanageall && !$canmanagepositions && !$canviewallpositions && !$canviewownpositions && !$candepartmentleadmanage) {
            require_capability('local/dutydesk:viewown', $context);
        }
        $caneditpositions = $canmanageall || $canmanagepositions || $candepartmentleadmanage;

        $id = optional_param('id', 0, PARAM_INT);
        $delete = optional_param('delete', 0, PARAM_BOOL);
        $archive = optional_param('archive', 0, PARAM_INT);
        $restore = optional_param('restore', 0, PARAM_INT);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $perpage = \local_dutydesk_normalize_perpage(optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT));
        $focus = optional_param('focus', 0, PARAM_INT);
        $view = optional_param('view', 'active', PARAM_ALPHA);
        $view = in_array($view, ['active', 'archived'], true) ? $view : 'active';
        $canviewarchivedpositions = \local_dutydesk_show_archived_positions_tab();
        if ($view === 'archived' && !$canviewarchivedpositions) {
            $view = 'active';
        }
        $isarchivedview = $view === 'archived';
        $archivedflag = $isarchivedview ? 1 : 0;
        $showform = optional_param('showform', 0, PARAM_BOOL);
        if ($isarchivedview) {
            $showform = 0;
        }
        $isajax = optional_param('ajax', 0, PARAM_BOOL);
        $ismodaledit = optional_param('modaledit', 0, PARAM_BOOL);
        $requestedpositiontype = \local_dutydesk_normalize_position_type(
            optional_param('positiontype', LOCAL_DUTYDESK_POSITION_TYPE_POSITION, PARAM_ALPHA)
        );
        $positionfilterid = max(0, optional_param('positionid', 0, PARAM_INT));
        $toggleused = optional_param('toggleused', 0, PARAM_BOOL);
        $topicareasonly = !$isarchivedview && optional_param('topicareasonly', 0, PARAM_BOOL);
        $searchvalue = trim((string)optional_param('search', '', PARAM_RAW_TRIMMED));
        $hassearch = $searchvalue !== '';
        $searchlikevalue = $hassearch ? '%' . $DB->sql_like_escape(core_text::strtolower($searchvalue)) . '%' : '';
        $ispost = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        if ($isajax) {
            require_sesskey();
            $PAGE->set_pagelayout('embedded');
        }
        if ($isajax && $ismodaledit) {
            $PAGE->requires->js_call_amd('local_dutydesk/position_modal', 'initEmbedded');
        }

        $manageablepositionids = \local_dutydesk_get_manageable_position_ids($USER->id);
        $ownpositionids = visibility::normalize_ids(\local_dutydesk_get_user_position_ids($USER->id));
        $viewablepositionids = visibility::normalize_ids(
            visibility::get_viewable_position_ids($ownpositionids, $USER->id)
        );
        $canusepositionvisibilitytoggle = !$isarchivedview && ($caneditpositions || $canviewallpositions);
        $defaultshowall = !$isarchivedview && !$toggleused && $positionfilterid <= 0 ? 1 : 0;
        $showall = $isarchivedview ? 1 : optional_param('showall', $defaultshowall, PARAM_BOOL);
        if (!$isarchivedview && $canusepositionvisibilitytoggle && $showall) {
            $positionfilterid = 0;
        }

        $allvisiblepositionids = visibility::normalize_ids($caneditpositions ? $manageablepositionids : $viewablepositionids);
        if ($topicareasonly) {
            $allvisiblepositionids = visibility::filter_topic_area_ids($allvisiblepositionids);
            $ownpositionids = visibility::filter_topic_area_ids($ownpositionids);
        }
        $canusepositionvisibilitytoggle = !$isarchivedview
            && (count($allvisiblepositionids) > count($ownpositionids) || $positionfilterid > 0);

        $listpositionids = visibility::resolve_list_position_ids(
            $isarchivedview,
            $showall,
            $topicareasonly,
            $positionfilterid,
            $toggleused,
            $canmanageall,
            $canmanagepositions,
            $canviewallpositions,
            $manageddepartmentids,
            $allvisiblepositionids,
            $ownpositionids
        );
        $queryallpositions = !$topicareasonly
            && ($isarchivedview || $showall)
            && $positionfilterid <= 0
            && ($canmanageall || ($isarchivedview && ($canmanagepositions || $canviewallpositions)));

        if ($focus > 0) {
            $focuspage = \local_dutydesk_calculate_position_page($focus, $perpage, $queryallpositions, $listpositionids);
            if ($focuspage !== null) {
                $page = $focuspage;
            }
        }

        $listbaseparams = self::build_list_base_params(
            $perpage,
            $view,
            $hassearch,
            $searchvalue,
            $showall,
            $isarchivedview,
            $toggleused,
            $topicareasonly,
            $positionfilterid
        );
        $listbaseurl = new moodle_url('/local/dutydesk/positions.php', $listbaseparams);
        $currentpageurl = new moodle_url('/local/dutydesk/positions.php', $listbaseparams + ['page' => $page]);
        if ($focus > 0) {
            $currentpageurl->param('focus', $focus);
        }

        self::handle_position_actions(
            $ispost,
            $delete,
            $archive,
            $restore,
            $id,
            $caneditpositions,
            $canmanageall,
            $manageablepositionids,
            $context,
            $currentpageurl
        );

        $renderedform = '';
        $needsform = $ispost || $showform || $id > 0 || ($isajax && $ismodaledit);
        if ($needsform && $caneditpositions && !$isarchivedview) {
            $renderedform = form_handler::process(
                $id,
                $isajax,
                $ismodaledit,
                $view,
                $perpage,
                $page,
                $positionfilterid,
                $requestedpositiontype,
                $hassearch,
                $searchvalue,
                $canmanageall,
                $manageddepartmentids,
                $manageablepositionids,
                $caneditpositions,
                $context,
                $currentpageurl,
                $showform
            );
        } else if (!$caneditpositions || $isarchivedview) {
            $showform = 0;
        }

        if ($isajax && $ismodaledit && $caneditpositions && !$isarchivedview) {
            position_page::render_modal_form($renderedform);
        }

        if (!$isajax) {
            echo $OUTPUT->header();
            echo $OUTPUT->render_from_template('local_dutydesk/navigation_tabs', [
                'ispositions' => !$isarchivedview,
                'ispositionsarchived' => $isarchivedview,
                'showdepartments' => \local_dutydesk_show_departments_tab(),
                'showpositionsarchived' => $canviewarchivedpositions,
            ]);

            if ($caneditpositions && !$isarchivedview) {
                if (!$showform) {
                    position_page::render_create_buttons($perpage, $view);
                } else {
                    echo html_writer::div($renderedform, 'local-dutydesk-position-form mb-4');
                    if (empty($id)) {
                        echo $OUTPUT->footer();
                        die;
                    }
                }
            }
        }

        $positionresult = repository::get_paginated_positions(
            $queryallpositions,
            $listpositionids,
            $archivedflag,
            $hassearch,
            $searchlikevalue,
            $page,
            $perpage
        );
        $records = $positionresult->records;
        $totalpositions = $positionresult->totalpositions;
        $page = $positionresult->page;

        $presentedpositions = presenter::build($records, $context, [
            'view' => $view,
            'page' => $page,
            'perpage' => $perpage,
            'positionfilterid' => $positionfilterid,
            'hassearch' => $hassearch,
            'searchvalue' => $searchvalue,
            'caneditpositions' => $caneditpositions,
            'canmanageall' => $canmanageall,
            'manageablepositionids' => $manageablepositionids,
            'showform' => $showform,
            'id' => $id,
        ]);

        $paginationhtml = $totalpositions > 0 ? $OUTPUT->paging_bar($totalpositions, $page, $perpage, $listbaseurl) : '';
        $searchbaseurl = new moodle_url('/local/dutydesk/positions.php');
        $templatedata = position_page::build_template_data(
            $presentedpositions->positions,
            $searchvalue,
            $searchbaseurl,
            $perpage,
            $page,
            $view,
            $positionfilterid,
            $showall,
            $toggleused,
            $topicareasonly,
            $canusepositionvisibilitytoggle,
            $isarchivedview,
            $hassearch
        );

        ob_start();
        if ($presentedpositions->previewhtml !== '') {
            echo $presentedpositions->previewhtml;
        }
        if ($paginationhtml !== '') {
            echo $paginationhtml;
        }
        echo $OUTPUT->render_from_template('local_dutydesk/position_list', $templatedata);
        if ($paginationhtml !== '') {
            echo $paginationhtml;
        }
        $resultsinnerhtml = ob_get_clean();

        if ($isajax) {
            header('Content-Type: text/html; charset=utf-8');
            echo $resultsinnerhtml;
            die;
        }

        echo html_writer::tag('div', $resultsinnerhtml, [
            'class' => 'local-dutydesk-position-results',
            'data-region' => 'task-results',
            'data-search-endpoint' => $searchbaseurl->out(false),
            'data-sesskey' => sesskey(),
        ]);
        echo $OUTPUT->footer();
    }

    /**
     * Handles setup_page.
     *
     * @return mixed
     */
    private static function setup_page(context $context): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_url(new moodle_url('/local/dutydesk/positions.php'));
        $PAGE->set_title(get_string('positions', 'local_dutydesk'));
        $PAGE->set_heading(get_string('positions', 'local_dutydesk'));
        $PAGE->add_body_class('limitedwidth');
        $PAGE->add_body_class('local-dutydesk-hide-required-note');
        $PAGE->requires->js_call_amd('local_dutydesk/subtasks_toggle', 'init');
        $PAGE->requires->js_call_amd('local_dutydesk/task_filter', 'init');
        $PAGE->requires->js_call_amd('local_dutydesk/position_modal', 'initParent');
    }

    /**
     * Handles build_list_base_params.
     *
     * @return mixed
     */
    private static function build_list_base_params(
        int $perpage,
        string $view,
        bool $hassearch,
        string $searchvalue,
        bool $showall,
        bool $isarchivedview,
        bool $toggleused,
        bool $topicareasonly,
        int $positionfilterid
    ): array {
        $params = ['perpage' => $perpage, 'view' => $view];
        if ($hassearch) {
            $params['search'] = $searchvalue;
        }
        if ($showall && !$isarchivedview) {
            $params['showall'] = 1;
        }
        if ($toggleused && !$isarchivedview) {
            $params['toggleused'] = 1;
        }
        if ($topicareasonly) {
            $params['topicareasonly'] = 1;
        }
        if ($positionfilterid > 0) {
            $params['positionid'] = $positionfilterid;
        }
        return $params;
    }

    /**
     * Handles handle_position_actions.
     *
     * @return mixed
     */
    private static function handle_position_actions(
        bool $ispost,
        bool $delete,
        int $archive,
        int $restore,
        int $id,
        bool $caneditpositions,
        bool $canmanageall,
        array $manageablepositionids,
        context $context,
        moodle_url $currentpageurl
    ): void {
        if ($ispost && $delete && $id && confirm_sesskey()) {
            permissions::require_manage_position($id, $caneditpositions, $canmanageall, $manageablepositionids, $context);
            manager::delete_position($id);
            redirect($currentpageurl, get_string('deleted', 'local_dutydesk'));
        }
        if ($ispost && $archive && confirm_sesskey()) {
            permissions::require_manage_position($archive, $caneditpositions, $canmanageall, $manageablepositionids, $context);
            manager::archive_position($archive);
            redirect($currentpageurl, get_string('archiveposition', 'local_dutydesk'));
        }
        if ($ispost && $restore && confirm_sesskey()) {
            permissions::require_manage_position($restore, $caneditpositions, $canmanageall, $manageablepositionids, $context);
            manager::restore_position($restore);
            redirect($currentpageurl, get_string('restoreposition', 'local_dutydesk'));
        }
    }
}
