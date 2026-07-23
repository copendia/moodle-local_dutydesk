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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


require_once(dirname(__DIR__, 3) . '/lib.php');

/**
 * Read operations for duty desk positions.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /**
     * Fetch paginated positions for the current list view.
     *
     * @param bool $queryallpositions
     * @param array $listpositionids
     * @param int $archivedflag
     * @param bool $hassearch
     * @param string $searchlikevalue
     * @param int $page
     * @param int $perpage
     * @return \stdClass
     */
    public static function get_paginated_positions(
        bool $queryallpositions,
        array $listpositionids,
        int $archivedflag,
        bool $hassearch,
        string $searchlikevalue,
        int $page,
        int $perpage
    ): \stdClass {
        global $DB;

        $offset = $page * $perpage;
        $records = [];
        $positionsdatasql = '';
        $positionsdataparams = [];
        $archivedcondition = 'COALESCE(archived, 0) = :archived';
        $archivedparams = ['archived' => $archivedflag];
        $totalpositions = 0;

        if ($queryallpositions) {
            if ($hassearch) {
                $searchjoins = " LEFT JOIN {dutydesk_department} d ON d.id = p.departmentid
                                 LEFT JOIN {user} primaryuser ON primaryuser.id = p.primaryuserid
                                 LEFT JOIN {dutydesk_position_deputy} deputy ON deputy.positionid = p.id
                                 LEFT JOIN {user} deputyuser ON deputyuser.id = deputy.userid";
                [$searchsql, $searchparams] = \local_dutydesk_build_position_search_condition(
                    $searchlikevalue,
                    'p',
                    'd',
                    'primaryuser',
                    'deputyuser',
                    'searchp'
                );
                $params = array_merge($archivedparams, $searchparams);
                $where = "{$archivedcondition} AND {$searchsql}";
                $countsql = "SELECT COUNT(DISTINCT p.id)
                               FROM {dutydesk_position} p
                               {$searchjoins}
                              WHERE {$where}";
                $totalpositions = (int)$DB->count_records_sql($countsql, $params);
                if ($totalpositions > 0) {
                    $positionsdatasql = "SELECT DISTINCT p.*
                                           FROM {dutydesk_position} p
                                           {$searchjoins}
                                          WHERE {$where}
                                       ORDER BY p.title ASC, p.id ASC";
                    $positionsdataparams = $params;
                    $records = $DB->get_records_sql($positionsdatasql, $positionsdataparams, $offset, $perpage);
                }
            } else {
                $totalpositions = $DB->count_records_select('dutydesk_position', $archivedcondition, $archivedparams);
                if ($totalpositions > 0) {
                    $records = $DB->get_records_select(
                        'dutydesk_position',
                        $archivedcondition,
                        $archivedparams,
                        'title ASC, id ASC',
                        '*',
                        $offset,
                        $perpage
                    );
                }
            }
        } else if (!empty($listpositionids)) {
            [$insql, $params] = $DB->get_in_or_equal($listpositionids, SQL_PARAMS_NAMED);
            $params = array_merge($params, $archivedparams);
            if ($hassearch) {
                $searchjoins = " LEFT JOIN {dutydesk_department} d ON d.id = p.departmentid
                                 LEFT JOIN {user} primaryuser ON primaryuser.id = p.primaryuserid
                                 LEFT JOIN {dutydesk_position_deputy} deputy ON deputy.positionid = p.id
                                 LEFT JOIN {user} deputyuser ON deputyuser.id = deputy.userid";
                [$searchsql, $searchparams] = \local_dutydesk_build_position_search_condition(
                    $searchlikevalue,
                    'p',
                    'd',
                    'primaryuser',
                    'deputyuser',
                    'searchp'
                );
                $params = array_merge($params, $searchparams);
                $where = "p.id {$insql} AND {$archivedcondition} AND {$searchsql}";
                $countsql = "SELECT COUNT(DISTINCT p.id)
                               FROM {dutydesk_position} p
                               {$searchjoins}
                              WHERE {$where}";
                $totalpositions = (int)$DB->count_records_sql($countsql, $params);
                if ($totalpositions > 0) {
                    $positionsdatasql = "SELECT DISTINCT p.*
                                           FROM {dutydesk_position} p
                                           {$searchjoins}
                                          WHERE {$where}
                                       ORDER BY p.title ASC, p.id ASC";
                    $positionsdataparams = $params;
                    $records = $DB->get_records_sql($positionsdatasql, $positionsdataparams, $offset, $perpage);
                }
            } else {
                $where = "id {$insql} AND {$archivedcondition}";
                $totalpositions = $DB->count_records_select('dutydesk_position', $where, $params);
                if ($totalpositions > 0) {
                    $records = $DB->get_records_select(
                        'dutydesk_position',
                        $where,
                        $params,
                        'title ASC, id ASC',
                        '*',
                        $offset,
                        $perpage
                    );
                }
            }
        }

        if ($totalpositions > 0 && $offset >= $totalpositions) {
            $page = (int)floor(($totalpositions - 1) / $perpage);
            $offset = $page * $perpage;
            if ($positionsdatasql !== '') {
                $records = $DB->get_records_sql($positionsdatasql, $positionsdataparams, $offset, $perpage);
            } else if ($queryallpositions) {
                $records = $DB->get_records_select(
                    'dutydesk_position',
                    $archivedcondition,
                    $archivedparams,
                    'title ASC, id ASC',
                    '*',
                    $offset,
                    $perpage
                );
            } else if (!empty($listpositionids)) {
                [$insql, $params] = $DB->get_in_or_equal($listpositionids, SQL_PARAMS_NAMED);
                $params = array_merge($params, $archivedparams);
                $where = "id {$insql} AND {$archivedcondition}";
                $records = $DB->get_records_select(
                    'dutydesk_position',
                    $where,
                    $params,
                    'title ASC, id ASC',
                    '*',
                    $offset,
                    $perpage
                );
            }
        }

        return (object) [
            'records' => $records,
            'totalpositions' => $totalpositions,
            'page' => $page,
            'offset' => $offset,
        ];
    }
}
