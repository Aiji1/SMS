<?php
/**
 * Template Generator untuk Import Siswa
 * File: modules/master-data/siswa/template_import.php
 */

// Load PhpSpreadsheet if available
$vendorLoaded = false;
$vendorPaths = [
    '../../../vendor/autoload.php',
    '../../../../vendor/autoload.php', 
    '../../../../../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'
];

foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $vendorLoaded = true;
        break;
    }
}

// Generate Excel template
if ($_GET['format'] === 'excel' && $vendorLoaded && class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers - EXACT MATCH dengan mapping di import.php
        $headers = [
            'NIS',
            'Nama', 
            'Jenis Kelamin (L/P)',
            'Tempat Lahir',
            'Tanggal Lahir (YYYY-MM-DD)',
            'Alamat',
            'No HP',
            'Kelas',
            'Nama Orang Tua',
            'No HP Orang Tua'
        ];
        
        // Set header row
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('E3F2FD');
            $col++;
        }
        
        // Add sample data
        $sampleData = [
            ['12345', 'Ahmad Budi Santoso', 'L', 'Jakarta', '2008-05-15', 'Jl. Merdeka No. 123', '081234567890', '7A', 'Budi Santoso', '081987654321'],
            ['12346', 'Siti Nurhaliza', 'P', 'Bandung', '2008-08-20', 'Jl. Sudirman No. 456', '082345678901', '7B', 'Ahmad Nurdin', '082876543210'],
            ['12347', 'Muhammad Rizki', 'L', 'Surabaya', '2008-12-10', 'Jl. Diponegoro No. 789', '083456789012', '7A', 'Rizki Abdullah', '083765432109']
        ];
        
        $row = 2;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Auto size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');
        
        $instructions = [
            ['PETUNJUK PENGGUNAAN TEMPLATE IMPORT SISWA'],
            [''],
            ['1. Kolom yang WAJIB diisi:'],
            ['   - NIS: Nomor Induk Siswa (unik)'],
            ['   - Nama: Nama lengkap siswa'],
            [''],
            ['2. Format data:'],
            ['   - Jenis Kelamin: L untuk Laki-laki, P untuk Perempuan'],
            ['   - Tanggal Lahir: Format YYYY-MM-DD (contoh: 2008-05-15)'],
            ['   - Nomor HP: Gunakan format angka (081234567890)'],
            [''],
            ['3. Tips:'],
            ['   - Hapus baris contoh sebelum import data asli'],
            ['   - Pastikan tidak ada baris kosong di tengah data'],
            ['   - Jangan mengubah nama header di baris pertama'],
            ['   - Simpan file dalam format .xlsx atau .xls'],
            [''],
            ['4. Mode Import:'],
            ['   - Insert & Update: Tambah baru dan perbarui yang sudah ada'],
            ['   - Insert Only: Hanya tambah data baru, skip yang sudah ada'],
            ['   - Update Only: Hanya update data yang sudah ada']
        ];
        
        $row = 1;
        foreach ($instructions as $instruction) {
            $instructionSheet->setCellValue('A' . $row, $instruction[0]);
            if ($row == 1) {
                $instructionSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            } elseif (strpos($instruction[0], ':') !== false) {
                $instructionSheet->getStyle('A' . $row)->getFont()->setBold(true);
            }
            $row++;
        }
        
        $instructionSheet->getColumnDimension('A')->setWidth(60);
        
        // Set active sheet back to data sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        // Output Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_Siswa.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        die('Error creating Excel template: ' . $e->getMessage());
    }
}

// Generate CSV template
if ($_GET['format'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="Template_Import_Siswa.csv"');
    
    // Add BOM for UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Headers - EXACT MATCH dengan mapping
    $headers = [
        'NIS',
        'Nama',
        'Jenis Kelamin (L/P)', 
        'Tempat Lahir',
        'Tanggal Lahir (YYYY-MM-DD)',
        'Alamat',
        'No HP',
        'Kelas',
        'Nama Orang Tua',
        'No HP Orang Tua'
    ];
    
    fputcsv($output, $headers);
    
    // Sample data
    $sampleData = [
        ['12345', 'Ahmad Budi Santoso', 'L', 'Jakarta', '2008-05-15', 'Jl. Merdeka No. 123', '081234567890', '7A', 'Budi Santoso', '081987654321'],
        ['12346', 'Siti Nurhaliza', 'P', 'Bandung', '2008-08-20', 'Jl. Sudirman No. 456', '082345678901', '7B', 'Ahmad Nurdin', '082876543210'],
        ['12347', 'Muhammad Rizki', 'L', 'Surabaya', '2008-12-10', 'Jl. Diponegoro No. 789', '083456789012', '7A', 'Rizki Abdullah', '083765432109']
    ];
    
    foreach ($sampleData as $data) {
        fputcsv($output, $data);
    }
    
    fclose($output);
    exit;
}

// Show template download page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Download Template Import</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-download"></i> Download Template Import Siswa</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Pilih format template yang diinginkan:</h6>
                        <p class="mb-0">Template sudah disesuaikan dengan format yang benar untuk menghindari error "NIS dan nama wajib diisi"</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                    <h6>Excel Template</h6>
                                    <p class="small text-muted">Format .xlsx dengan petunjuk lengkap</p>
                                    <?php if ($vendorLoaded): ?>
                                        <a href="?format=excel" class="btn btn-success">
                                            <i class="fas fa-download"></i> Download Excel
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            PhpSpreadsheet tidak tersedia
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-csv fa-3x text-primary mb-3"></i>
                                    <h6>CSV Template</h6>
                                    <p class="small text-muted">Format .csv dengan encoding UTF-8</p>
                                    <a href="?format=csv" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Download CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Petunjuk Penting:</h6>
                        <ol class="mb-0">
                            <li><strong>Jangan mengubah nama header</strong> di baris pertama</li>
                            <li><strong>Kolom NIS dan Nama wajib diisi</strong> untuk setiap baris</li>
                            <li>Hapus baris contoh sebelum mengisi data asli</li>
                            <li>Format tanggal: YYYY-MM-DD (contoh: 2008-05-15)</li>
                            <li>Jenis kelamin: L untuk Laki-laki, P untuk Perempuan</li>
                        </ol>
                    </div>
                    
                    <div class="text-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Data Siswa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>