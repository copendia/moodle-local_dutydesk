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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Handles task import template downloads.
 *
 * @package    local_dutydesk
 * @copyright  2026 onwards Copendia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_manager {
    /**
     * Download a CSV or XLSX import template.
     *
     * @param string $template
     * @return void
     */
    public static function download(string $template): void {
        if ($template !== 'csv' && $template !== 'xlsx') {
            return;
        }

        require_sesskey();

        $filename = 'GVPL_vorlage.' . $template;
        $rows = self::get_rows();

        if ($template === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
            die;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        spreadsheet_loader::load();
        $writer = new Xlsx(self::build_xlsx_template($rows));
        $writer->save('php://output');
        die;
    }

    /**
     * Return sample rows for import templates.
     *
     * @return array
     */
    private static function get_rows(): array {
        return [
            ['Department example:', '', ''],
            ['Sachgebiet', '', 'Beschreibung (Grundlage fuer Taetigkeitsdarstellung)'],
            ['A', 'Leitungs- und Fuehrungsaufgaben', 'Dienstbesprechung vorbereiten'],
            ['', '', 'Entscheidungsvorlage erstellen'],
            ['B', 'Organisation', 'Posteingang fachlich bewerten'],
            ['', '', 'Arbeitsstand mit Fachbereich abstimmen'],
        ];
    }

    /**
     * Build a GVPL-like XLSX import template.
     *
     * @param array $rows
     * @return Spreadsheet
     */
    private static function build_xlsx_template(array $rows): Spreadsheet {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('GVPL Import');
        $sheet->fromArray($rows, null, 'A1');

        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('B5:B6');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(34);
        foreach ([3, 4, 5, 6] as $row) {
            $sheet->getRowDimension($row)->setRowHeight(42);
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(78);

        $sheet->getStyle('A1:C6')->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A2:C2')->getFont()->setBold(true);
        $sheet->getStyle('A2:C2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('EDEDED');
        $sheet->getStyle('A1:C6')->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getStyle('A2:C2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3:A6')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_TOP);

        $borderstyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:C6')->applyFromArray($borderstyle);
        $sheet->getStyle('A2:C2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('B3:B6')->getFont()->setBold(true);

        return $spreadsheet;
    }
}
