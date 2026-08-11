<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class GenerateSampleExcel extends Command
{
    protected $signature = 'sample:excel';
    protected $description = 'Generate a sample Excel file for employee import';

    public function handle(): int
    {
        $headers = ['first_name', 'last_name', 'email', 'phone', 'address', 'salary', 'system_role', 'job_role'];

        $rows = [
            ['John', 'Doe', 'john.doe@example.com', '555-0100', '123 Main St, New York, NY', '75000', 'employee', 'Software Engineer'],
            ['Jane', 'Smith', 'jane.smith@example.com', '555-0101', '456 Oak Ave, Los Angeles, CA', '82000', 'employee', 'Product Manager'],
            ['Bob', 'Johnson', 'bob.johnson@example.com', '555-0102', '789 Pine Rd, Chicago, IL', '65000', 'employee', 'Designer'],
        ];

        $path = storage_path('app/sample-import-template.xlsx');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
            $sheet->getStyle([$colIndex + 1, 1])->getFont()->setBold(true);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        $this->info('Sample Excel file created: ' . $path);

        return 0;
    }
}
