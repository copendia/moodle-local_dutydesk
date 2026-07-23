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

namespace local_dutydesk\local\position;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


$pluginroot = dirname(__DIR__, 3);
require_once($pluginroot . '/lib.php');

/**
 * Resolves visible position ids for list views.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class visibility {
    /**
     * Handles normalize_ids.
     *
     * @return mixed
     */
    public static function normalize_ids(array $ids): array {
        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Handles get_viewable_position_ids.
     *
     * @return mixed
     */
    public static function get_viewable_position_ids(array $ownpositionids, int $userid): array {
        global $DB;

        $viewablepositionids = $ownpositionids;
        $viewdepartmentids = \local_dutydesk_get_user_department_ids($userid);
        if (empty($viewdepartmentids)) {
            return $viewablepositionids;
        }

        [$deptinsql, $deptparams] = $DB->get_in_or_equal($viewdepartmentids, SQL_PARAMS_NAMED);
        $departmentpositionids = $DB->get_fieldset_sql(
            "SELECT id
               FROM {dutydesk_position}
              WHERE departmentid {$deptinsql}",
            $deptparams
        ) ?? [];
        if (!empty($departmentpositionids)) {
            $viewablepositionids = array_merge($viewablepositionids, array_map('intval', $departmentpositionids));
        }

        return self::normalize_ids($viewablepositionids);
    }

    /**
     * Handles filter_topic_area_ids.
     *
     * @return mixed
     */
    public static function filter_topic_area_ids(array $positionids): array {
        global $DB;

        if (empty($positionids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($positionids, SQL_PARAMS_NAMED);
        $topicareaids = $DB->get_fieldset_sql(
            "SELECT id
               FROM {dutydesk_position}
              WHERE id {$insql}
                AND positiontype = :topicareatype",
            $params + ['topicareatype' => LOCAL_DUTYDESK_POSITION_TYPE_TOPICAREA]
        ) ?? [];

        return self::normalize_ids($topicareaids);
    }

    /**
     * Handles resolve_list_position_ids.
     *
     * @return mixed
     */
    public static function resolve_list_position_ids(
        bool $isarchivedview,
        bool $showall,
        bool $topicareasonly,
        int $positionfilterid,
        bool $toggleused,
        bool $canmanageall,
        bool $canmanagepositions,
        bool $canviewallpositions,
        array $manageddepartmentids,
        array $allvisiblepositionids,
        array $ownpositionids
    ): array {
        global $DB;

        if ($isarchivedview) {
            if ($canmanageall || $canmanagepositions || $canviewallpositions) {
                return [];
            }
            if (empty($manageddepartmentids)) {
                return [];
            }
            [$archiveddeptsql, $archiveddeptparams] = $DB->get_in_or_equal($manageddepartmentids, SQL_PARAMS_NAMED);
            $archiveddepartmentpositionids = $DB->get_fieldset_sql(
                "SELECT id
                   FROM {dutydesk_position}
                  WHERE departmentid {$archiveddeptsql}",
                $archiveddeptparams
            ) ?? [];
            return self::normalize_ids($archiveddepartmentpositionids);
        }

        if ($showall || $topicareasonly) {
            return $allvisiblepositionids;
        }

        if ($positionfilterid > 0 && !$toggleused) {
            return in_array($positionfilterid, $allvisiblepositionids, true) ? [$positionfilterid] : [];
        }

        return $ownpositionids;
    }
}
