<?php
/**
 * Import Data Siswa - Fixed Version
 * File: modules/master-data/siswa/import.php
 */

// Prevent any output before JSON response
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent HTML output

// Include database config with error suppression
@require_once '../../../core/config/database.php';

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

// Check authorization with proper error handling
try {
    if (!function_exists('hasRole') || !hasRole(['admin', 'kepala_sekolah', 'wali_kelas', 'petugas'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean(); // Clean any output buffer
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        $_SESSION['message'] = 'Anda tidak memiliki akses untuk halaman ini';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle AJAX file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    ob_clean(); // Clean any output buffer
    header('Content-Type: application/json');
    
    try {
        $uploadedFile = $_FILES['import_file'];
        
        // Validasi file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file: ' . $uploadedFile['error']);
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Format file tidak didukung. Gunakan Excel (.xlsx, .xls) atau CSV');
        }
        
        // Validasi ukuran file (5MB)
        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('File terlalu besar. Maksimal 5MB');
        }
        
        // Proses file
        $filePath = $uploadedFile['tmp_name'];
        $data = [];
        
        if ($fileExtension === 'csv') {
            // Process CSV file
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $headers = [];
                $row_count = 0;
                
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row_count === 0) {
                        // Header row - clean and normalize
                        $headers = array_map(function($header) {
                            // Remove BOM and normalize
                            $header = str_replace("\xEF\xBB\xBF", '', $header);
                            return strtolower(trim($header));
                        }, $row);
                    } else {
                        // Data row - skip empty rows
                        $rowData = array_map('trim', $row);
                        if (!empty(array_filter($rowData))) {
                            if (count($rowData) >= count($headers)) {
                                $data[] = array_combine($headers, array_slice($rowData, 0, count($headers)));
                            }
                        }
                    }
                    $row_count++;
                }
                fclose($handle);
            }
        } else {
            // Process Excel file
            try {
                if (!$vendorLoaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                    throw new Exception('PhpSpreadsheet tidak tersedia untuk membaca file Excel. Gunakan format CSV.');
                }
                
                // Load spreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray(null, true, true, true);
                
                if (empty($rows)) {
                    throw new Exception('File Excel kosong');
                }
                
                // Get headers from first row
                $headerRow = array_values($rows)[0];
                $headers = array_map(function($header) {
                    return strtolower(trim($header ?? ''));
                }, $headerRow);
                
                // Remove header row and process data rows
                unset($rows[array_keys($rows)[0]]);
                
                foreach ($rows as $rowIndex => $row) {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    // Convert row to indexed array
                    $rowData = array_values($row);
                    
                    // Combine headers with row data
                    if (count($rowData) >= count($headers)) {
                        $combinedData = [];
                        for ($i = 0; $i < count($headers); $i++) {
                            $combinedData[$headers[$i]] = isset($rowData[$i]) ? trim($rowData[$i] ?? '') : '';
                        }
                        $data[] = $combinedData;
                    }
                }
                
                // Clean up
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                
            } catch (Exception $e) {
                throw new Exception('Error membaca file Excel: ' . $e->getMessage() . '. Coba gunakan format CSV.');
            }
        }
        
        if (empty($data)) {
            throw new Exception('File kosong atau format tidak valid');
        }
        
        // Map headers for compatibility with template - IMPROVED MAPPING
        $headerMapping = [
            // Standard mapping
            'nis' => 'nis',
            'nama' => 'nama',
            'jenis kelamin (l/p)' => 'jenis_kelamin',
            'jenis kelamin' => 'jenis_kelamin',
            'jk' => 'jenis_kelamin',
            'kelamin' => 'jenis_kelamin',
            'tempat lahir' => 'tempat_lahir',
            'tanggal lahir (yyyy-mm-dd)' => 'tanggal_lahir',
            'tanggal lahir' => 'tanggal_lahir',
            'tgl lahir' => 'tanggal_lahir',
            'alamat' => 'alamat',
            'no hp' => 'no_hp',
            'nohp' => 'no_hp',
            'hp' => 'no_hp',
            'nomor hp' => 'no_hp',
            'kelas' => 'kelas',
            'nama orang tua' => 'nama_ortu',
            'nama ortu' => 'nama_ortu',
            'orang tua' => 'nama_ortu',
            'ortu' => 'nama_ortu',
            'no hp orang tua' => 'no_hp_ortu',
            'no hp ortu' => 'no_hp_ortu',
            'hp orang tua' => 'no_hp_ortu',
            'hp ortu' => 'no_hp_ortu'
        ];
        
        // Normalize headers and map them with better cleaning
        $normalizedHeaders = [];
        foreach ($headers as $header) {
            // More aggressive cleaning
            $normalizedHeader = $header;
            
            // Remove BOM
            $normalizedHeader = str_replace("\xEF\xBB\xBF", '', $normalizedHeader);
            // Remove other invisible characters
            $normalizedHeader = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $normalizedHeader);
            // Normalize whitespace and convert to lowercase
            $normalizedHeader = strtolower(trim($normalizedHeader));
            // Remove extra spaces
            $normalizedHeader = preg_replace('/\s+/', ' ', $normalizedHeader);
            
            if (isset($headerMapping[$normalizedHeader])) {
                $normalizedHeaders[] = $headerMapping[$normalizedHeader];
            } else {
                $normalizedHeaders[] = $normalizedHeader;
            }
        }
        $headers = $normalizedHeaders;
        
        // Validasi header
        $required_headers = ['nis', 'nama'];
        $missing_headers = [];
        
        foreach ($required_headers as $header) {
            if (!in_array($header, $headers)) {
                $missing_headers[] = $header;
            }
        }
        
        if (!empty($missing_headers)) {
            throw new Exception('Header yang diperlukan tidak ditemukan: ' . implode(', ', $missing_headers));
        }
        
        // Check if database connection exists and functions are available
        if (!isset($pdo)) {
            throw new Exception('Koneksi database tidak tersedia');
        }
        
        if (!function_exists('getOne') || !function_exists('execute')) {
            throw new Exception('Fungsi database tidak tersedia');
        }
        
        // Mulai proses import ke database
        $pdo->beginTransaction();
        
        $imported = 0;
        $updated = 0;
        $errors = [];
        $import_mode = $_POST['import_mode'] ?? 'insert_update';
        
        foreach ($data as $index => $row) {
            $row_number = $index + 2; // +2 karena header + index dimulai dari 0
            
            try {
                // More aggressive data cleaning
                $cleanRow = [];
                foreach ($row as $key => $value) {
                    // Clean each cell value
                    if ($value === null || $value === '') {
                        $cleanRow[$key] = '';
                    } else {
                        // Convert to string and clean
                        $cleanValue = (string)$value;
                        // Remove BOM and invisible characters
                        $cleanValue = str_replace("\xEF\xBB\xBF", '', $cleanValue);
                        $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanValue);
                        $cleanValue = trim($cleanValue);
                        $cleanRow[$key] = $cleanValue;
                    }
                }
                $row = $cleanRow;
                
                // Validasi data required with better checking
                $nis = trim($row['nis'] ?? '');
                $nama = trim($row['nama'] ?? '');
                
                // Debug log for first few rows
                if ($index < 3) {
                    error_log("Row $row_number debug - NIS: '$nis' (len: " . strlen($nis) . "), Nama: '$nama' (len: " . strlen($nama) . ")");
                }
                
                if (empty($nis) || empty($nama)) {
                    $errors[] = "Baris $row_number: NIS dan nama wajib diisi (NIS: '$nis', Nama: '$nama')";
                    continue;
                }
                
                // Additional validation for minimum length
                if (strlen($nis) < 2) {
                    $errors[] = "Baris $row_number: NIS terlalu pendek (minimal 2 karakter)";
                    continue;
                }
                
                if (strlen($nama) < 2) {
                    $errors[] = "Baris $row_number: Nama terlalu pendek (minimal 2 karakter)";
                    continue;
                }
                
                // Validasi jenis kelamin jika ada
                $jenis_kelamin = null;
                if (!empty($row['jenis_kelamin'])) {
                    $jenis_kelamin = strtoupper(trim($row['jenis_kelamin'] ?? ''));
                    if (!in_array($jenis_kelamin, ['L', 'P'])) {
                        $errors[] = "Baris $row_number: Jenis kelamin harus L atau P";
                        continue;
                    }
                }
                
                // Validasi tanggal lahir jika ada
                $tanggal_lahir = null;
                if (!empty($row['tanggal_lahir'])) {
                    try {
                        // Handle Excel date formats
                        if (is_numeric($row['tanggal_lahir'])) {
                            // Excel serial date
                            if ($vendorLoaded && class_exists('PhpOffice\PhpSpreadsheet\Shared\Date')) {
                                $tanggal_lahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_lahir'])->format('Y-m-d');
                            } else {
                                // Fallback for numeric dates - Excel epoch starts from 1900-01-01
                                $tanggal_lahir = date('Y-m-d', ($row['tanggal_lahir'] - 25569) * 86400);
                            }
                        } else {
                            // String date - try multiple formats
                            $dateString = trim($row['tanggal_lahir'] ?? '');
                            $date = DateTime::createFromFormat('Y-m-d', $dateString);
                            if (!$date) {
                                $date = DateTime::createFromFormat('d/m/Y', $dateString);
                            }
                            if (!$date) {
                                $date = DateTime::createFromFormat('d-m-Y', $dateString);
                            }
                            if (!$date) {
                                // Try strtotime as last resort
                                $timestamp = strtotime($dateString);
                                if ($timestamp !== false && $timestamp > 0) {
                                    $tanggal_lahir = date('Y-m-d', $timestamp);
                                }
                            } else {
                                $tanggal_lahir = $date->format('Y-m-d');
                            }
                            
                            // If still can't parse, add to errors but continue processing
                            if (!$tanggal_lahir) {
                                $errors[] = "Baris $row_number: Format tanggal lahir tidak valid ($dateString), diabaikan";
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Baris $row_number: Error parsing tanggal lahir - " . $e->getMessage();
                        $tanggal_lahir = null; // Continue without date
                    }
                }
                
                // Cek NIS sudah ada atau belum
                try {
                    $existing = getOne("SELECT id FROM siswa WHERE nis = ?", [$nis]);
                } catch (Exception $e) {
                    throw new Exception("Error checking existing NIS: " . $e->getMessage());
                }
                
                if ($existing) {
                    if ($import_mode === 'insert_only') {
                        continue; // Skip existing
                    }
                    
                    // Update existing record
                    $updateData = [
                        'nama' => $nama,
                        'jenis_kelamin' => $jenis_kelamin,
                        'tempat_lahir' => trim($row['tempat_lahir'] ?? '') ?: null,
                        'tanggal_lahir' => $tanggal_lahir,
                        'alamat' => trim($row['alamat'] ?? '') ?: null,
                        'no_hp' => trim($row['no_hp'] ?? '') ?: null,
                        'email' => null, // Email tidak ada di template
                        'nama_ayah' => trim($row['nama_ortu'] ?? '') ?: null,
                        'nama_ibu' => null, // Tidak ada di template
                        'pekerjaan_ayah' => null, // Tidak ada di template
                        'pekerjaan_ibu' => null, // Tidak ada di template
                        'no_hp_ortu' => trim($row['no_hp_ortu'] ?? '') ?: null,
                        'kelas' => trim($row['kelas'] ?? '') ?: null,
                        'status' => 'aktif',
                        'updated_at' => date('Y-m-d H:i:s'),
                        'id' => $existing['id']
                    ];
                    
                    $set_clause = implode(', ', array_map(function($key) {
                        return "$key = :$key";
                    }, array_keys(array_diff_key($updateData, ['id' => '']))));
                    
                    $sql = "UPDATE siswa SET $set_clause WHERE id = :id";
                    try {
                        execute($sql, $updateData);
                        $updated++;
                    } catch (Exception $e) {
                        throw new Exception("Error updating record: " . $e->getMessage());
                    }
                    
                } else {
                    if ($import_mode === 'update_only') {
                        continue; // Skip new records
                    }
                    
                    // Insert new record
                    $insertData = [
                        'nis' => $nis,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenis_kelamin,
                        'tempat_lahir' => trim($row['tempat_lahir'] ?? '') ?: null,
                        'tanggal_lahir' => $tanggal_lahir,
                        'alamat' => trim($row['alamat'] ?? '') ?: null,
                        'no_hp' => trim($row['no_hp'] ?? '') ?: null,
                        'email' => null, // Not in template
                        'nama_ayah' => trim($row['nama_ortu'] ?? '') ?: null,
                        'nama_ibu' => null, // Not in template
                        'pekerjaan_ayah' => null, // Not in template
                        'pekerjaan_ibu' => null, // Not in template
                        'no_hp_ortu' => trim($row['no_hp_ortu'] ?? '') ?: null,
                        'kelas' => trim($row['kelas'] ?? '') ?: null,
                        'status' => 'aktif',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $columns = implode(', ', array_keys($insertData));
                    $placeholders = ':' . implode(', :', array_keys($insertData));
                    
                    $sql = "INSERT INTO siswa ($columns) VALUES ($placeholders)";
                    try {
                        execute($sql, $insertData);
                        $imported++;
                    } catch (Exception $e) {
                        throw new Exception("Error inserting record: " . $e->getMessage());
                    }
                }
                
            } catch (Exception $e) {
                $errors[] = "Baris $row_number: " . $e->getMessage();
            }
        }
        
        // Commit transaction
        try {
            $pdo->commit();
        } catch (Exception $e) {
            throw new Exception("Error committing transaction: " . $e->getMessage());
        }
        
        $message = "Import selesai! ";
        if ($imported > 0) $message .= "$imported data baru ditambahkan. ";
        if ($updated > 0) $message .= "$updated data diperbarui. ";
        
        // Clean output buffer and send JSON response
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Rollback transaction
        if (isset($pdo) && $pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (Exception $rollbackError) {
                // Log rollback error but don't throw
                error_log("Rollback error: " . $rollbackError->getMessage());
            }
        }
        
        // Clean output buffer and send JSON error response
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Clear output buffer for HTML display
ob_end_clean();

// Only show modal HTML if not a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h4 class="modal-title" id="importModalLabel">
                    <i class="fas fa-upload"></i> Import Data Siswa
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Alert for messages -->
                <div id="importAlert" style="display: none;"></div>
                
                <!-- Instructions -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> <strong>Petunjuk Import:</strong></h6>
                    <ol class="mb-0 small">
                        <li>Download template Excel dari tombol "Template Import"</li>
                        <li>Isi data siswa sesuai format template</li>
                        <li>Kolom <strong>NIS</strong> dan <strong>Nama</strong> wajib diisi</li>
                        <li>Jenis kelamin: <code>L</code> untuk Laki-laki, <code>P</code> untuk Perempuan</li>
                        <li>Format tanggal: <code>YYYY-MM-DD</code> (contoh: 2008-05-15)</li>
                    </ol>
                </div>

                <!-- Mode Import -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card card-outline card-info">
                            <div class="card-body py-3">
                                <h6><i class="fas fa-cogs"></i> Mode Import</h6>
                                <select class="form-control form-control-sm" id="import_mode" name="import_mode">
                                    <option value="insert_update">Insert & Update (Rekomendasi)</option>
                                    <option value="insert_only">Hanya Insert (Skip yang sudah ada)</option>
                                    <option value="update_only">Hanya Update (Skip yang baru)</option>
                                </select>
                                <small class="text-muted">Insert & Update: Tambah baru dan perbarui yang sudah ada berdasarkan NIS</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>File Excel/CSV:</label>
                        <div class="custom-file">
                            <input type="file" 
                                   name="import_file" 
                                   id="import_file" 
                                   class="custom-file-input"
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            <label class="custom-file-label" for="import_file">Pilih file Excel atau CSV...</label>
                        </div>
                        <small class="text-muted">Format: Excel (.xlsx, .xls) atau CSV, Maksimal: 5MB</small>
                    </div>
                </form>

                <!-- Progress Bar -->
                <div id="uploadProgress" style="display: none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Memproses import...</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" class="btn btn-success" id="submitImport" disabled>
                    <i class="fas fa-upload"></i> Import Data
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for jQuery to be available
function initImportModal() {
    if (typeof $ === 'undefined') {
        setTimeout(initImportModal, 100);
        return;
    }
    
    console.log('Import modal initialized');
    
    // File input change handler
    $('#import_file').on('change', function() {
        const fileName = $(this)[0].files[0]?.name || 'Pilih file Excel atau CSV...';
        $(this).next('.custom-file-label').text(fileName);
        
        // Enable/disable submit button
        $('#submitImport').prop('disabled', !$(this)[0].files[0]);
        
        // Validate file
        if ($(this)[0].files[0]) {
            const file = $(this)[0].files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!['xlsx', 'xls', 'csv'].includes(fileExtension)) {
                showAlert('danger', 'Format file tidak didukung. Gunakan Excel (.xlsx, .xls) atau CSV.');
                $(this).val('');
                $(this).next('.custom-file-label').text('Pilih file Excel atau CSV...');
                $('#submitImport').prop('disabled', true);
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                showAlert('danger', 'File terlalu besar. Maksimal 5MB.');
                $(this).val('');
                $(this).next('.custom-file-label').text('Pilih file Excel atau CSV...');
                $('#submitImport').prop('disabled', true);
                return;
            }
            
            // Clear previous alerts
            $('#importAlert').hide();
        }
    });

    // Submit import
    $('#submitImport').on('click', function() {
        const formData = new FormData();
        const fileInput = $('#import_file')[0];
        const importMode = $('#import_mode').val();
        
        if (!fileInput.files[0]) {
            showAlert('danger', 'Pilih file terlebih dahulu!');
            return;
        }
        
        formData.append('import_file', fileInput.files[0]);
        formData.append('import_mode', importMode);
        
        // Show progress
        $('#uploadProgress').show();
        $('#submitImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        
        // Animate progress bar
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            $('.progress-bar').css('width', progress + '%');
        }, 200);
        
        $.ajax({
            url: 'import.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000, // 60 seconds timeout
            success: function(response) {
                clearInterval(progressInterval);
                $('.progress-bar').css('width', '100%');
                
                if (response.success) {
                    showAlert('success', response.message);
                    
                    // Show errors if any
                    if (response.errors && response.errors.length > 0) {
                        let errorHtml = '<div class="alert alert-warning mt-2">';
                        errorHtml += '<h6><i class="fas fa-exclamation-triangle"></i> Detail Error:</h6>';
                        errorHtml += '<div style="max-height: 200px; overflow-y: auto;"><ul class="mb-0">';
                        response.errors.forEach(function(error) {
                            errorHtml += '<li>' + escapeHtml(error) + '</li>';
                        });
                        errorHtml += '</ul></div></div>';
                        $('#importAlert').after(errorHtml);
                    }
                    
                    // Auto close and reload after 3 seconds
                    setTimeout(function() {
                        $('#importModal').modal('hide');
                        location.reload();
                    }, 3000);
                } else {
                    showAlert('danger', response.message || 'Terjadi kesalahan saat import');
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                console.error('Import error:', error, xhr.responseText);
                
                let errorMessage = 'Terjadi kesalahan saat upload';
                
                if (xhr.status === 0) {
                    errorMessage = 'Koneksi terputus. Periksa koneksi internet Anda.';
                } else if (xhr.status === 404) {
                    errorMessage = 'File import.php tidak ditemukan.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error server internal. Periksa log server.';
                } else if (status === 'timeout') {
                    errorMessage = 'Request timeout. File mungkin terlalu besar atau server lambat.';
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // If response is not JSON, it might be HTML error
                        if (xhr.responseText.includes('<!DOCTYPE') || xhr.responseText.includes('<html')) {
                            errorMessage = 'Server mengembalikan halaman HTML. Kemungkinan ada error PHP atau redirect.';
                        } else {
                            errorMessage += ': ' + error;
                        }
                    }
                }
                
                showAlert('danger', errorMessage);
            },
            complete: function() {
                $('#uploadProgress').hide();
                $('#submitImport').prop('disabled', false).html('<i class="fas fa-upload"></i> Import Data');
                $('.progress-bar').css('width', '0%');
            }
        });
    });

    // Reset modal when closed
    $('#importModal').on('hidden.bs.modal', function() {
        $('#importForm')[0].reset();
        $('#import_file').next('.custom-file-label').text('Pilih file Excel atau CSV...');
        $('#submitImport').prop('disabled', true);
        $('#importAlert').hide().html('');
        $('.alert-warning').remove();
    });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : (type === 'danger' ? 'alert-danger' : 'alert-info');
    const iconClass = type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle');
    
    $('#importAlert').removeClass('alert-success alert-danger alert-info')
                    .addClass('alert ' + alertClass)
                    .html('<i class="fas ' + iconClass + '"></i> ' + escapeHtml(message))
                    .show();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initImportModal);
</script>

<?php 
} // End of if ($_SERVER['REQUEST_METHOD'] !== 'POST')
?>