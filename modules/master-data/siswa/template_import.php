<?php
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header kolom
$headers = [
    'NIS', 'Nama', 'Jenis Kelamin (L/P)', 
    'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)',
    'Alamat', 'No HP', 'Kelas',
    'Nama Orang Tua', 'No HP Orang Tua'
];
$sheet->fromArray([$headers], NULL, 'A1');

// Set contoh data
$contohData = [
    '2024001', 'Contoh Siswa', 'L',
    'Jakarta', '2005-01-15',
    'Jl. Contoh No. 123', '08123456789', 'XII IPA 1',
    'Nama Orang Tua', '08123456780'
];
$sheet->fromArray([$contohData], NULL, 'A2');

// Set lebar kolom otomatis
foreach(range('A','J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set proteksi sheet (opsional)
$sheet->getProtection()->setSheet(true);

// Download file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_siswa.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;