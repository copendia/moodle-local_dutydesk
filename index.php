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

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/classes/local/dashboard/manager.php');
require_once(__DIR__ . '/classes/local/dashboard/presenter.php');
require_once(__DIR__ . '/classes/output/dashboard_page.php');
require_once(__DIR__ . '/lib.php');
require_login();

$context = context_system::instance();
\local_dutydesk\output\dashboard_page::setup($context);

$userid = (int)$USER->id;
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', LOCAL_DUTYDESK_DEFAULT_PERPAGE, PARAM_INT);
$perpage = local_dutydesk_normalize_perpage($perpage);
$togglevacantid = optional_param('togglevacantid', 0, PARAM_INT);
$vacant = optional_param('vacant', 0, PARAM_BOOL);

require_capability('local/dutydesk:viewmydepartment', $context);

\local_dutydesk\local\dashboard\manager::handle_vacancy_toggle(
    $context,
    $userid,
    $page,
    $perpage,
    $togglevacantid,
    $vacant
);

$dashboard = \local_dutydesk\local\dashboard\presenter::build($context, $userid, $page, $perpage);

\local_dutydesk\output\dashboard_page::render(
    $dashboard['templatecontext'],
    $dashboard['haspositions'],
    $dashboard['totalgroups'],
    $dashboard['page'],
    $dashboard['perpage'],
    $dashboard['listbaseurl']
);
