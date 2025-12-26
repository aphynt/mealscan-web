<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPhotoUrl extends Command
{
    protected $signature = 'employee:import-photo-url';
    protected $description = 'Import photo_url dari Excel';

    public function handle()
    {
        $file = storage_path('app/Book1_updated.xlsx');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        unset($rows[0]); // header

        $updated = 0;

        foreach ($rows as $row) {
            $nik = trim($row[0]);

            $photoUrl = trim($row[4]); // Column1

            if (!$nik || !$photoUrl) {
                continue;
            }

            $employee = Employee::where('nik', $nik)->first();
            // dd($employee);
            if (!$employee) {
                $this->warn("NIK tidak ditemukan: $nik");
                continue;
            }

            $employee->update([
                'photo_path' => null
            ]);

            $updated++;
        }

        $this->info("Selesai. Photo URL diupdate: $updated");
    }
}
