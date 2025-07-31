<?php

namespace App\Exports\GeneralLedger;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


class GroupSheet implements WithTitle, WithEvents
{
    protected $data;
    protected $periode;

    public function __construct($data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function title(): string
    {
        return $this->data['group_name'] ?? 'Unknown Group';
    }

    public static function afterExport(AfterExport $event)
    {
        $spreadsheet = $event->getWriter()->getSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set kursor sel ke A1
        $sheet->setActiveCell('A1');
    }

    private function setCellValueAndStyle($sheet, $cell, $value, $style = [])
    {
        $sheet->setCellValue($cell, $value);
        if (!empty($style)) {
            $sheet->getStyle($cell)->applyFromArray($style);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $row = 1; // Mulai dari baris pertama
                $datas = $this->data;

                // atur lebar kolom
                $sheet->getColumnDimension('A')->setWidth(11);
                $sheet->getColumnDimension('B')->setWidth(24);
                $sheet->getColumnDimension('C')->setWidth(11);
                $sheet->getColumnDimension('D')->setWidth(50);
                $sheet->getColumnDimension('E')->setWidth(24);
                $sheet->getColumnDimension('F')->setWidth(16);
                $sheet->getColumnDimension('G')->setWidth(16);
                $sheet->getColumnDimension('H')->setWidth(16);

                // dd($datas);
                foreach ($datas['groups'] as $groups) {
                    // Tambahkan Header Group
                    // dd($groups);
                    $sheet->setCellValue("A{$row}", "Coa Group");
                    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('@ * \:');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $datas['coa_group']);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                    ]);
                    $sheet->mergeCells("C{$row}:H{$row}");
                    $sheet->setCellValue("C{$row}", get_arrangement('company_name'));
                    $sheet->getStyle("C{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 14,],
                            'alignment' => ['horizontal' => 'center']
                        ]);
                    $row++;

                    $sheet->setCellValue("A{$row}", "Coa Head");
                    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('@ * \:');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa_head']);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                    ]);
                    $sheet->mergeCells("C{$row}:H{$row}");
                    $sheet->setCellValue("C{$row}", $groups['coa_name']);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0070C0']],
                            'alignment' => ['horizontal' => 'center']
                        ]);
                    $row++;

                    $sheet->setCellValue("A{$row}", "Coa Body");
                    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('@ * \:');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa_body']);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                    ]);
                    $sheet->mergeCells("C{$row}:H{$row}");
                    $sheet->setCellValue("C{$row}", "BUKU BESAR");
                    $sheet->getStyle("C{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 13,],
                            'alignment' => ['horizontal' => 'center']
                        ]);
                    $row++;

                    $sheet->setCellValue("A{$row}", "Coa");
                    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('@ * \:');
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa']);
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                    ]);
                    $sheet->mergeCells("C{$row}:H{$row}");
                    $sheet->setCellValue("C{$row}", $this->periode);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 12, 'italic' => true],
                            'alignment' => ['horizontal' => 'center']
                        ]);
                    $row++;
                    $row++;

                    // Tambahkan Header Tabel
                    $sheet->setCellValue("A{$row}", 'NO');
                    $sheet->setCellValue("B{$row}", 'TRANSACTION NUMBER');
                    $sheet->setCellValue("C{$row}", 'DATE');
                    $sheet->setCellValue("D{$row}", 'DESCRIPTION');
                    $sheet->setCellValue("E{$row}", 'REF');
                    $sheet->setCellValue("F{$row}", 'DEBIT');
                    $sheet->setCellValue("G{$row}", 'CREDIT');
                    $sheet->setCellValue("H{$row}", 'BALANCE');
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(30);
                    $row++;

                    // Tambahkan Data Transaksi
                    $no = 1;
                    foreach ($groups['transactions'] as $transaction) {
                        $sheet->setCellValue("A{$row}", $no++);
                        $sheet->setCellValue("B{$row}", $transaction['transaction_number']);
                        $sheet->setCellValue("C{$row}", $transaction['date']);
                        $sheet->setCellValue("D{$row}", $transaction['description']);
                        $sheet->setCellValue("E{$row}", $transaction['ref_number']);
                        $sheet->setCellValue("F{$row}", $transaction['debit']);
                        $sheet->setCellValue("G{$row}", $transaction['credit']);
                        $sheet->setCellValue("H{$row}", $transaction['balance']);

                        // Kolom A, B, C, dan E rata tengah (center alignment)
                        foreach (['A', 'B', 'C', 'E'] as $column) {
                            $sheet->getStyle("{$column}{$row}")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }

                        // Kolom F, G, H format angka dengan format Rp
                        foreach (['F', 'G', 'H'] as $column) {
                            $sheet->getStyle("{$column}{$row}")
                                ->getNumberFormat()
                                ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                        }

                        // Kolom D wrap text
                        $sheet->getStyle("D{$row}")
                            ->getAlignment()
                            ->setWrapText(true);

                        // Semua kolom dari A hingga H rata atas (top alignment)
                        foreach (range('A', 'H') as $column) {
                            $sheet->getStyle("{$column}{$row}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_TOP);
                        }
                        $row++;
                    }

                    // Tambahkan Baris Total
                    $sheet->mergeCells("A{$row}:E{$row}");
                    $sheet->setCellValue("A{$row}", 'JUMLAH');
                    $sheet->setCellValue("F{$row}", '=SUM(F' . ($row - count($groups['transactions'])) . ":F" . ($row - 1) . ')');
                    $sheet->setCellValue("G{$row}", '=SUM(G' . ($row - count($groups['transactions'])) . ":G" . ($row - 1) . ')');
                    $sheet->setCellValue("H{$row}", '=H' . ($row - 1));
                    // format total
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(18);
                    // Kolom F, G, H format angka dengan format Rp
                    foreach (['F', 'G', 'H'] as $column) {
                        $sheet->getStyle("{$column}{$row}")
                            ->getNumberFormat()
                            ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                    }
                    $row += 3; // Tambahkan spasi setelah total
                }
            },
        ];
    }
}
