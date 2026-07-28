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

namespace local_dutydesk;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use local_dutydesk\local\task_import\importer;

/**
 * Tests for task import parsing and persistence.
 *
 * @package    local_dutydesk
 * @category   test
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class task_import_importer_test extends \advanced_testcase {
    /**
     * Handles setUp.
     *
     * @return mixed
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Tests that GVPL-style rows reuse the last category and skip duplicate or empty task rows.
     */
    public function test_parse_rows_uses_current_category_and_skips_duplicates_or_empty_rows(): void {
        $items = importer::parse_rows([
            1 => ['A' => 'Sachgebiet', 'B' => '', 'C' => 'Beschreibung'],
            2 => ['A' => 'A', 'B' => 'Organisation', 'C' => 'Posteingang fachlich bewerten'],
            3 => ['A' => '', 'B' => '', 'C' => 'Arbeitsstand abstimmen'],
            4 => ['A' => '', 'B' => '', 'C' => 'Arbeitsstand abstimmen'],
            5 => ['A' => '', 'B' => '', 'C' => ''],
            6 => ['A' => '', 'B' => '', 'C' => '   '],
            7 => ['A' => 'B', 'B' => 'Kommunikation', 'C' => 'Rueckmeldung erstellen'],
        ]);

        $this->assertCount(3, $items);
        $this->assertSame('Organisation', $items[0]['category']);
        $this->assertSame('Posteingang fachlich bewerten', $items[0]['title']);
        $this->assertSame('Organisation', $items[1]['category']);
        $this->assertSame('Arbeitsstand abstimmen', $items[1]['title']);
        $this->assertSame('Kommunikation', $items[2]['category']);
    }

    /**
     * Tests that imported text is normalised consistently for matching and duplicate checks.
     */
    public function test_normalize_collapses_case_and_whitespace(): void {
        $this->assertSame('posteingang fachlich bewerten', importer::normalize("  Posteingang \n fachlich\tbewerten  "));
    }

    /**
     * Tests that task similarity catches close matches but ignores clearly different titles.
     */
    public function test_is_similar_detects_close_titles_only(): void {
        $this->assertTrue(importer::is_similar('Posteingang fachlich bewerten', 'Posteingang fachlich bewerten'));
        $this->assertTrue(importer::is_similar('Posteingang fachlich bewerten', 'Posteingang fachl. bewerten'));
        $this->assertFalse(importer::is_similar('Test 1', 'Zettel abgeben'));
        $this->assertFalse(importer::is_similar('Test 1', 'Test 2'));
        $this->assertFalse(importer::is_similar('Posteingang fachlich bewerten', 'Serverwartung dokumentieren'));
    }

    /**
     * Tests that parsing fails explicitly when the expected Sachgebiet/Beschreibung columns are missing.
     */
    public function test_parse_rows_requires_category_and_description_columns(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('taskimportmissingcolumns', 'local_dutydesk'));

        importer::parse_rows([
            1 => ['A' => 'Kategorie', 'B' => 'Aufgabe'],
            2 => ['A' => 'Organisation', 'B' => 'Posteingang fachlich bewerten'],
        ]);
    }

    /**
     * Tests that similar imported tasks are reported against existing tasks.
     */
    public function test_find_warnings_detects_similar_existing_tasks(): void {
        $this->create_task('Posteingang fachlich bewerten');

        $warnings = importer::find_warnings([
            [
                'row' => 2,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
        ]);

        $this->assertCount(1, $warnings);
        $this->assertSame(2, $warnings[0]['row']);
        $this->assertSame('Posteingang fachlich bewerten', $warnings[0]['match']);
    }

    /**
     * Tests that duplicate or similar task titles inside the uploaded file are reported before import.
     */
    public function test_find_warnings_detects_similar_import_rows(): void {
        $warnings = importer::find_warnings([
            [
                'row' => 2,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
            [
                'row' => 3,
                'category' => 'Organisation',
                'title' => 'Posteingang fachl. bewerten',
            ],
        ]);

        $this->assertCount(1, $warnings);
        $this->assertSame(3, $warnings[0]['row']);
        $this->assertSame('Posteingang fachlich bewerten', $warnings[0]['match']);
    }

    /**
     * Tests that committing an import reuses a matching legacy category and creates active tasks.
     */
    public function test_commit_reuses_legacy_category_and_creates_tasks(): void {
        global $DB;

        $departmentid = $this->create_department('Import department');
        $legacycategoryid = $this->create_category('Organisation');

        $result = importer::commit([
            [
                'row' => 2,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
            [
                'row' => 3,
                'category' => 'Kommunikation',
                'title' => 'Rueckmeldung erstellen',
            ],
        ], $departmentid);

        $this->assertSame(2, $result['tasks']);
        $this->assertSame(1, $result['categories']);
        $this->assertSame($departmentid, (int)$DB->get_field('local_dutydesk_category', 'departmentid', ['id' => $legacycategoryid]));

        $firsttask = $DB->get_record('local_dutydesk_task', ['title' => 'Posteingang fachlich bewerten'], '*', MUST_EXIST);
        $secondtask = $DB->get_record('local_dutydesk_task', ['title' => 'Rueckmeldung erstellen'], '*', MUST_EXIST);
        foreach ([$firsttask, $secondtask] as $task) {
            $this->assertSame(1, (int)$task->active);
            $this->assertNotEmpty($task->categoryid);
        }
    }

    /**
     * Tests that committing an import reuses a matching category already assigned to the selected department.
     */
    public function test_commit_reuses_existing_department_category(): void {
        global $DB;

        $departmentid = $this->create_department('Import department');
        $categoryid = $this->create_category('Organisation', $departmentid);

        $result = importer::commit([
            [
                'row' => 5,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
        ], $departmentid);

        $this->assertSame(1, $result['tasks']);
        $this->assertSame(0, $result['categories']);

        $task = $DB->get_record('local_dutydesk_task', ['title' => 'Posteingang fachlich bewerten'], '*', MUST_EXIST);
        $this->assertSame($categoryid, (int)$task->categoryid);
    }

    /**
     * Tests that committing an import does not reuse a category assigned to a different department.
     */
    public function test_commit_does_not_reuse_category_from_other_department(): void {
        global $DB;

        $departmentid = $this->create_department('Import department');
        $otherdepartmentid = $this->create_department('Other department');
        $othercategoryid = $this->create_category('Organisation', $otherdepartmentid);

        $result = importer::commit([
            [
                'row' => 2,
                'category' => 'Organisation',
                'title' => 'Posteingang fachlich bewerten',
            ],
        ], $departmentid);

        $this->assertSame(1, $result['tasks']);
        $this->assertSame(1, $result['categories']);
        $this->assertSame($otherdepartmentid, (int)$DB->get_field('local_dutydesk_category', 'departmentid', [
            'id' => $othercategoryid,
        ]));

        $task = $DB->get_record('local_dutydesk_task', ['title' => 'Posteingang fachlich bewerten'], '*', MUST_EXIST);
        $category = $DB->get_record('local_dutydesk_category', ['id' => $task->categoryid], '*', MUST_EXIST);
        $this->assertSame('Organisation', $category->name);
        $this->assertSame($departmentid, (int)$category->departmentid);
        $this->assertNotEquals($othercategoryid, (int)$category->id);
    }

    /**
     * Handles create_department.
     *
     * @return mixed
     */
    private function create_department(string $name): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_department', (object) [
            'name' => $name,
            'description' => '',
            'timestamp' => time(),
        ]);
    }

    /**
     * Handles create_category.
     *
     * @return mixed
     */
    private function create_category(string $name, ?int $departmentid = null): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_category', (object) [
            'name' => $name,
            'departmentid' => $departmentid,
        ]);
    }

    /**
     * Handles create_task.
     *
     * @return mixed
     */
    private function create_task(string $title): int {
        global $DB;

        return (int)$DB->insert_record('local_dutydesk_task', (object) [
            'title' => $title,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'categoryid' => null,
            'weight' => 0,
            'active' => 1,
            'timestamp' => time(),
        ]);
    }
}
