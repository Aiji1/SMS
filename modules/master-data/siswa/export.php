<?php
/**
 * Export Data Siswa
 * File: modules/master-data/siswa/export.php
 */

require_once '../../../core/config/database.php';

// Get export parameters
$type = $_GET['type'] ?? 'excel';
$selected = $_GET['selected'] ?? false;
$kelas_filter = $_GET['kelas'] ?? '';
$search = $_GET['search'] ?? '';

try {
    // Get data to export
    if ($selected && isset($_SESSION['export_data'])) {
        // Use selected data from session
        $siswa_data = $_SESSION['export_data'];
        unset($_SESSION['export_data']);
        $filename_suffix = 'selected';
    } else {
        // Get all data with filters
        $where_conditions = [];
        $params = [];
        
        if ($search) {
            $where_conditions[] = "(nama LIKE ? OR nis LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($kelas_filter) {
            $where_conditions[] = "kelas = ?";
            $params[] = $kelas_filter;
        }
        
        $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
        $siswa_data = getAll("SELECT * FROM siswa $where_clause ORDER BY nama ASC", $params);
        
        $filename_suffix = $kelas_filter ? "kelas_$kelas_filter" : 'all';
    }
    
    if (empty($siswa_data)) {
        $_SESSION['message'] = 'Tidak ada data untuk diekspor';
        $_SESSION['message_type'] = 'warning';
        header('Location: index.php');
        exit;
    }
    
    // Generate filename
    $date = date('Y-m-d_H-i-s');
    $filename = "data_siswa_{$filename_suffix}_{$date}";
    
    switch ($type) {
        case 'excel':
            exportToExcel($siswa_data, $filename);
            break;
        case 'pdf':
            exportToPDF($siswa_data, $filename);
            break;
        case 'csv':
            exportToCSV($siswa_data, $filename);
            break;
        default:
            throw new Exception('Format export tidak didukung');
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Error export: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

/**
 * Export to Excel using simple HTML table (can be opened by Excel)
 */
function exportToExcel($data, $filename) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start output
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Data Siswa</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>Data Siswa - Exported on ' . date('d/m/Y H:i:s') . '</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama Lengkap</th>
                    <th>JK</th>
                    <th>Tempat Lahir</th>
                    <th>Tanggal Lahir</th>
                    <th>Kelas</th>
                    <th>Alamat</th>
                    <th>No HP</th>
                    <th>Email</th>
                    <th>Nama Ayah</th>
                    <th>Nama Ibu</th>
                    <th>Pekerjaan Ayah</th>
                    <th>Pekerjaan Ibu</th>
                    <th>No HP Ortu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $index => $siswa) {
        $no = $index + 1;
        $tanggal_lahir = $siswa['tanggal_lahir'] ? date('d/m/Y', strtotime($siswa['tanggal_lahir'])) : '';
        
        echo '<tr>
                <td>' . $no . '</td>
                <td>' . htmlspecialchars($siswa['nis']) . '</td>
                <td>' . htmlspecialchars($siswa['nama']) . '</td>
                <td>' . htmlspecialchars($siswa['jenis_kelamin']) . '</td>
                <td>' . htmlspecialchars($siswa['tempat_lahir'] ?? '') . '</td>
                <td>' . $tanggal_lahir . '</td>
                <td>' . htmlspecialchars($siswa['kelas'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['alamat'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['no_hp'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['email'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['nama_ayah'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['nama_ibu'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['pekerjaan_ayah'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['pekerjaan_ibu'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['no_hp_ortu'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['status']) . '</td>
              </tr>';
    }
    
    echo '</tbody>
        </table>
    </body>
    </html>';
    exit;
}

/**
 * Export to CSV
 */
function exportToCSV($data, $filename) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    $headers = [
        'No', 'NIS', 'Nama Lengkap', 'JK', 'Tempat Lahir', 'Tanggal Lahir',
        'Kelas', 'Alamat', 'No HP', 'Email', 'Nama Ayah', 'Nama Ibu',
        'Pekerjaan Ayah', 'Pekerjaan Ibu', 'No HP Ortu', 'Status'
    ];
    
    fputcsv($output, $headers);
    
    // CSV data
    foreach ($data as $index => $siswa) {
        $row = [
            $index + 1,
            $siswa['nis'],
            $siswa['nama'],
            $siswa['jenis_kelamin'],
            $siswa['tempat_lahir'] ?? '',
            $siswa['tanggal_lahir'] ? date('d/m/Y', strtotime($siswa['tanggal_lahir'])) : '',
            $siswa['kelas'] ?? '',
            $siswa['alamat'] ?? '',
            $siswa['no_hp'] ?? '',
            $siswa['email'] ?? '',
            $siswa['nama_ayah'] ?? '',
            $siswa['nama_ibu'] ?? '',
            $siswa['pekerjaan_ayah'] ?? '',
            $siswa['pekerjaan_ibu'] ?? '',
            $siswa['no_hp_ortu'] ?? '',
            $siswa['status']
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export to PDF (basic HTML to PDF)
 */
function exportToPDF($data, $filename) {
    // Simple HTML to PDF conversion (requires browser print to PDF)
    // For production, consider using libraries like TCPDF or DOMPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Data Siswa</title>
        <style>
            @media print {
                .no-print { display: none; }
            }
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 10px; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px;">
                Print/Save as PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; margin-left: 5px;">
                Close
            </button>
        </div>
        
        <div class="header">
            <h1>DATA SISWA</h1>
            <p>Dicetak pada: ' . date('d F Y H:i:s') . '</p>
            <p>Total: ' . count($data) . ' siswa</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 10%;">NIS</th>
                    <th style="width: 20%;">Nama</th>
                    <th style="width: 5%;">JK</th>
                    <th style="width: 12%;">Tempat, Tgl Lahir</th>
                    <th style="width: 8%;">Kelas</th>
                    <th style="width: 25%;">Alamat</th>
                    <th style="width: 12%;">Kontak</th>
                    <th style="width: 5%;">Status</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $index => $siswa) {
        $no = $index + 1;
        $tempat_tgl_lahir = '';
        if ($siswa['tempat_lahir'] || $siswa['tanggal_lahir']) {
            $tempat_tgl_lahir = ($siswa['tempat_lahir'] ?? '') . 
                               ($siswa['tempat_lahir'] && $siswa['tanggal_lahir'] ? ', ' : '') .
                               ($siswa['tanggal_lahir'] ? date('d/m/Y', strtotime($siswa['tanggal_lahir'])) : '');
        }
        
        $kontak = '';
        if ($siswa['no_hp']) {
            $kontak .= 'HP: ' . $siswa['no_hp'];
        }
        if ($siswa['email']) {
            $kontak .= ($kontak ? '<br>' : '') . $siswa['email'];
        }
        
        echo '<tr>
                <td>' . $no . '</td>
                <td>' . htmlspecialchars($siswa['nis']) . '</td>
                <td>' . htmlspecialchars($siswa['nama']) . '</td>
                <td>' . htmlspecialchars($siswa['jenis_kelamin']) . '</td>
                <td>' . htmlspecialchars($tempat_tgl_lahir) . '</td>
                <td>' . htmlspecialchars($siswa['kelas'] ?? '') . '</td>
                <td>' . htmlspecialchars($siswa['alamat'] ?? '') . '</td>
                <td>' . $kontak . '</td>
                <td>' . htmlspecialchars($siswa['status']) . '</td>
              </tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="footer">
            <p>Dicetak dari Sistem Manajemen Sekolah (SMS)</p>
            <p>Total: ' . count($data) . ' siswa</p>
        </div>
    </body>
    </html>';
    exit;
}
?>