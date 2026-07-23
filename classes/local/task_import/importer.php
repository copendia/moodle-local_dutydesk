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

namespace local_dutydesk\local\task_import;

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');
require_once(dirname(__DIR__, 3) . '/lib.php');

use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Parser and persistence service for task imports.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /**
     * Return department options for the import form.
     *
     * @return array
     */
    public static function get_department_options(): array {
        global $DB;

        $departmentrecords = $DB->get_records('dutydesk_department', null, 'name ASC', 'id, name');
        $departmentoptions = [];
        foreach ($departmentrecords as $department) {
            $departmentoptions[(int)$department->id] = format_string($department->name);
        }
        return $departmentoptions;
    }

    /**
     * Normalize text for comparison and header matching.
     *
     * @param string|null $value
     * @return string
     */
    public static function normalize(?string $value): string {
        $value = \core_text::strtolower(trim((string)$value));
        $value = preg_replace('/\s+/u', ' ', $value);
        return $value ?? '';
    }

    /**
     * Determine if two task titles are similar enough to warn the user.
     *
     * @param string $left
     * @param string $right
     * @return bool
     */
    public static function is_similar(string $left, string $right): bool {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if ($left === '' || $right === '') {
            return false;
        }
        if ($left === $right) {
            return true;
        }

        similar_text($left, $right, $percent);
        if ($percent >= 90) {
            return true;
        }

        $maxlength = max(\core_text::strlen($left), \core_text::strlen($right));
        $minlength = min(\core_text::strlen($left), \core_text::strlen($right));
        if ($maxlength < 16 || $minlength < 12) {
            return false;
        }

        $distance = levenshtein($left, $right);
        return $percent >= 75 && ($distance / $maxlength) <= 0.2;
    }

    /**
     * Extract rows from a spreadsheet file.
     *
     * @param string $filepath
     * @param string $filename
     * @return array
     */
    public static function read_rows(string $filepath, string $filename = ''): array {
        $extension = \core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $header = file_get_contents($filepath, false, null, 0, 4);

        if ($extension === 'xlsx' || $header === "PK\x03\x04") {
            $reader = new Xlsx();
        } else {
            $reader = new Csv();
            $firstline = (string)file_get_contents($filepath, false, null, 0, 4096);
            if (substr_count($firstline, ';') > substr_count($firstline, ',')) {
                $reader->setDelimiter(';');
            }
        }

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, true);
    }

    /**
     * Parse imported rows into category/task pairs.
     *
     * @param array $rows
     * @return array
     */
    public static function parse_rows(array $rows): array {
        $categorycol = null;
        $descriptioncol = null;
        $columns = [];
        $headerrow = 0;

        foreach ($rows as $rownum => $row) {
            foreach ($row as $column => $value) {
                $header = self::normalize((string)$value);
                if ($header === '') {
                    continue;
                }
                if ($categorycol === null && strpos($header, 'sachgebiet') !== false) {
                    $categorycol = $column;
                }
                if ($descriptioncol === null && strpos($header, 'beschreibung') !== false) {
                    $descriptioncol = $column;
                }
            }
            if ($categorycol !== null && $descriptioncol !== null) {
                $columns = array_keys($row);
                $headerrow = (int)$rownum;
                break;
            }
        }

        if ($categorycol === null || $descriptioncol === null || empty($columns)) {
            throw new \moodle_exception('taskimportmissingcolumns', 'local_dutydesk');
        }

        $categoryindex = array_search($categorycol, $columns, true);
        $nextcategorycol = $categoryindex !== false && isset($columns[$categoryindex + 1])
            ? $columns[$categoryindex + 1]
            : null;
        $currentcategory = '';
        $items = [];
        $seen = [];

        foreach ($rows as $rownum => $row) {
            if ((int)$rownum <= $headerrow) {
                continue;
            }
            if (self::is_empty_row($row)) {
                continue;
            }

            $category = trim((string)($row[$categorycol] ?? ''));
            $nextcategory = $nextcategorycol !== null ? trim((string)($row[$nextcategorycol] ?? '')) : '';
            $description = trim((string)($row[$descriptioncol] ?? ''));

            if ($nextcategory !== '' && (\core_text::strlen($category) <= 3 || $category === '')) {
                $category = $nextcategory;
            }
            if ($category !== '') {
                $currentcategory = $category;
            }
            if ($description === '' || $currentcategory === '') {
                continue;
            }

            $key = self::normalize($currentcategory . '|' . $description);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $items[] = [
                'row' => (int)$rownum,
                'category' => $currentcategory,
                'title' => $description,
            ];
        }

        return $items;
    }

    /**
     * Check whether a spreadsheet row contains no relevant values.
     *
     * @param array $row
     * @return bool
     */
    private static function is_empty_row(array $row): bool {
        foreach ($row as $value) {
            if (self::normalize((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Build duplicate/similarity warnings for parsed rows.
     *
     * @param array $items
     * @return array
     */
    public static function find_warnings(array $items): array {
        global $DB;

        $existingtasks = $DB->get_records('dutydesk_task', null, '', 'id, title');
        $warnings = [];

        foreach ($items as $index => $item) {
            foreach ($existingtasks as $task) {
                if (self::is_similar($item['title'], $task->title ?? '')) {
                    $warnings[] = [
                        'row' => $item['row'],
                        'title' => $item['title'],
                        'match' => format_string($task->title),
                    ];
                    break;
                }
            }

            for ($i = 0; $i < $index; $i++) {
                if (self::is_similar($item['title'], $items[$i]['title'])) {
                    $warnings[] = [
                        'row' => $item['row'],
                        'title' => $item['title'],
                        'match' => $items[$i]['title'],
                    ];
                    break;
                }
            }
        }

        return $warnings;
    }

    /**
     * Persist parsed import items.
     *
     * @param array $items
     * @param int $departmentid
     * @return array
     */
    public static function commit(array $items, int $departmentid): array {
        global $DB;

        if (!$DB->record_exists('dutydesk_department', ['id' => $departmentid])) {
            throw new \moodle_exception('invaliddepartment', 'local_dutydesk');
        }

        $categorymap = self::get_or_claim_category_map($departmentid);
        $createdcategories = 0;
        $createdtasks = 0;
        $now = time();

        foreach ($items as $item) {
            $categorykey = self::normalize($item['category']);
            if (!isset($categorymap[$categorykey])) {
                $categoryid = $DB->insert_record('dutydesk_category', [
                    'name' => $item['category'],
                    'departmentid' => $departmentid,
                ]);
                $categorymap[$categorykey] = (int)$categoryid;
                $createdcategories++;
            }

            $taskid = $DB->insert_record('dutydesk_task', [
                'title' => $item['title'],
                'description' => '',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $categorymap[$categorykey],
                'active' => 1,
                'timestamp' => $now,
            ]);
            \local_dutydesk_log_task_history((int)$taskid, 'created', format_string($item['title']));
            $createdtasks++;
        }

        return [
            'tasks' => $createdtasks,
            'categories' => $createdcategories,
        ];
    }

    /**
     * Return department categories and claim matching legacy categories.
     *
     * @param int $departmentid
     * @return array
     */
    private static function get_or_claim_category_map(int $departmentid): array {
        global $DB;

        $categoryrecords = $DB->get_records_select(
            'dutydesk_category',
            'departmentid = :departmentid OR departmentid IS NULL',
            ['departmentid' => $departmentid],
            'departmentid DESC, name ASC',
            'id, name, departmentid'
        );
        $categorymap = [];
        $legacycategorymap = [];

        foreach ($categoryrecords as $category) {
            $categorykey = self::normalize($category->name);
            if (!empty($category->departmentid)) {
                $categorymap[$categorykey] = (int)$category->id;
                continue;
            }

            if (!isset($legacycategorymap[$categorykey])) {
                $legacycategorymap[$categorykey] = (int)$category->id;
            }
        }

        foreach ($legacycategorymap as $categorykey => $categoryid) {
            if (isset($categorymap[$categorykey])) {
                continue;
            }
            $DB->set_field('dutydesk_category', 'departmentid', $departmentid, ['id' => $categoryid]);
            $categorymap[$categorykey] = $categoryid;
        }

        return $categorymap;
    }
}
