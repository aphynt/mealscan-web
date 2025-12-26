<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $attendances;

    public function __construct($attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return $this->attendances;
    }

    /**
     * Headings (Status dihapus)
     */
    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Waktu',
            'NIK',
            'Nama',
            'Kategori Makan',
            'Jumlah',
            'Rating',
            'Saran'
        ];
    }

    /**
     * Map row data (Status dihapus)
     */
    public function map($attendance): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $attendance->attendance_date->format('d/m/Y'),
            $attendance->attendance_time->format('H:i:s'),
            $attendance->nik,
            $attendance->employee->name ?? '-',
            $this->getMealTypeLabel($attendance->meal_type),
            $attendance->quantity,
            $attendance->rating,
            $attendance->remarks ?? '-'
        ];
    }

    private function getMealTypeLabel($mealType): string
    {
        $labels = [
            'breakfast' => 'Breakfast',
            'lunch' => 'Lunch',
            'dinner' => 'Dinner'
        ];

        return $labels[$mealType] ?? ucfirst($mealType);
    }

    /**
     * Update column widths (Status "H" dihapus, Saran pindah ke "H")
     */
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 12,
            'C' => 10,
            'D' => 15,
            'E' => 25,
            'F' => 15,
            'G' => 10,
            'H' => 10,
            'I' => 30, // Saran
        ];
    }

    /**
     * Styling (ubah range A1:H... dari sebelumnya A1:I...)
     */
    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $lastRow = $sheet->getHighestRow();

        // Global borders
        $sheet->getStyle('A1:I' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Center alignment for specific columns
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Wrap text for Saran column
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);

        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }

    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
