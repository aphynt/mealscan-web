<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ImportEmployeesFromExcel extends Command
{
    protected $signature = 'employee:import-excel';
    protected $description = 'Import employee dari Excel (insert jika belum ada)';

    public function handle()
    {
        $file = storage_path('app/Book1_updated.xlsx');

        if (!file_exists($file)) {
            $this->error('File Excel tidak ditemukan');
            return;
        }

        $sheet = IOFactory::load($file)->getActiveSheet();
        $rows = $sheet->toArray();

        unset($rows[0]); // skip header

        $inserted = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $nik      = trim($row[0]); // NIK
            $nama     = trim($row[1]); // Nama
            $photoUrl = trim($row[4]); // Column1

            if (!$nik || !$nama) {
                $skipped++;
                continue;
            }

            // cek existing by no_ktp (PALING AMAN)
            $employee = Employee::where('nik', $nik)->first();

            if ($employee) {
                $this->line("Skip (sudah ada): $nik");
                continue;
            }

            Employee::create([
                'nik'        => $nik,
                'name'       => $nama,
                'photo_url'  => $photoUrl,
                'is_active'  => 1,
            ]);

            $inserted++;
            $this->info("Inserted: $nik");
        }

        $this->info("Selesai. Inserted: $inserted | Skipped: $skipped");
    }
}
