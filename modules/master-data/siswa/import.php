<?php
/**
 * Import Data Siswa dari Excel/CSV
 * File: modules/master-data/siswa/import.php
 */

$page_title = 'Import Data Siswa';
$breadcrumb = [
    ['title' => 'Master Data', 'url' => '../index.php'],
    ['title' => 'Data Siswa', 'url' => 'index.php'],
    ['title' => 'Import Data']
];

// Include SMS Core layout
include '../../../core/includes/header.php';

// Handle file upload and import
if ($_POST && isset($_FILES['import_file'])) {
    try {
        $uploadedFile = $_FILES['import_file'];
        
        // Validate file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file');
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Format file tidak didukung. Gunakan CSV, XLS, atau XLSX');
        }
        
        // Process the file
        $importResult = processImportFile($uploadedFile, $fileExtension);
        
        if ($importResult['success']) {
            $_SESSION['message'] = "Import berhasil! {$importResult['imported']} data ditambahkan, {$importResult['updated']} data diperbarui";
            $_SESSION['message_type'] = 'success';
            
            if (!empty($importResult['errors'])) {
                $_SESSION['import_errors'] = $importResult['errors'];
            }
            
            header('Location: index.php');
            exit;
        } else {
            throw new Exception($importResult['message']);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Check for import errors from previous attempt
$import_errors = $_SESSION['import_errors'] ?? [];
unset($_SESSION['import_errors']);
?>

<!-- Custom CSS -->
<style>
.import-template {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.file-drop-zone {
    border: 2px dashed #007bff;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.file-drop-zone.dragover {
    border-color: #0056b3;
    background: #e3f2fd;
}

.progress {
    display: none;
}

.error-list {
    max-height: 300px;
    overflow-y: auto;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-upload mr-2"></i>Import Data Siswa
                </h3>
                <div class="card-tools">
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Import Instructions -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Petunjuk Import:</h6>
                    <ul class="mb-0">
                        <li>File yang didukung: CSV, XLS, XLSX</li>
                        <li>Maksimal ukuran file: 5MB</li>
                        <li>Kolom NIS wajib diisi dan unik</li>
                        <li>Jika NIS sudah ada, data akan diperbarui</li>
                        <li>Download template untuk format yang benar</li>
                    </ul>
                </div>

                <!-- Error Messages -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <!-- Import Errors -->
                <?php if (!empty($import_errors)): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Beberapa data gagal diimport:</h6>
                        <div class="error-list">
                            <ul class="mb-0">
                                <?php foreach ($import_errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Download Template -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="import-template">
                            <h5><i class="fas fa-download text-primary"></i> Download Template</h5>
                            <p class="text-muted">Download template untuk format import yang benar</p>
                            <div class="btn-group">
                                <a href="?download_template=csv" class="btn btn-outline-primary">
                                    <i class="fas fa-file-csv"></i> Template CSV
                                </a>
                                <a href="?download_template=excel" class="btn btn-outline-success">
                                    <i class="fas fa-file-excel"></i> Template Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="import-template">
                            <h5><i class="fas fa-eye text-info"></i> Preview Format</h5>
                            <p class="text-muted">Lihat contoh format data yang benar</p>
                            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#previewModal">
                                <i class="fas fa-table"></i> Lihat Format
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <div class="form-group">
                        <label>Upload File Excel/CSV</label>
                        <div class="file-drop-zone" id="dropZone">
                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                            <h5>Drag & Drop file di sini</h5>
                            <p class="text-muted">atau klik untuk memilih file</p>
                            <input type="file" 
                                   name="import_file" 
                                   id="importFile" 
                                   accept=".csv,.xlsx,.xls" 
                                   required 
                                   style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('importFile').click()">
                                <i class="fas fa-folder-open"></i> Pilih File
                            </button>
                        </div>
                        
                        <!-- File Info -->
                        <div id="fileInfo" style="display: none;" class="mt-3">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                File dipilih: <span id="fileName"></span>
                                (<span id="fileSize"></span>)
                            </div>
                        </div>
                    </div>

                    <!-- Import Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mode Import</label>
                                <select name="import_mode" class="form-control">
                                    <option value="insert_update">Insert & Update (Recommended)</option>
                                    <option value="insert_only">Insert Only (Skip existing NIS)</option>
                                    <option value="update_only">Update Only (Skip new NIS)</option>
                                </select>
                                <small class="form-text text-muted">
                                    Insert & Update: Tambah data baru dan perbarui yang sudah ada
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Validasi Data</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="strict_validation" name="strict_validation" checked>
                                    <label class="custom-control-label" for="strict_validation">
                                        Validasi ketat (Stop jika ada error)
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Jika dimatikan, data yang error akan dilewati
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-3" id="uploadProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 0%"></div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="fas fa-upload"></i> Mulai Import
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-table"></i> Format Template Import
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>nis *</th>
                                <th>nama *</th>
                                <th>jenis_kelamin *</th>
                                <th>tempat_lahir</th>
                                <th>tanggal_lahir</th>
                                <th>kelas</th>
                                <th>alamat</th>
                                <th>no_hp</th>
                                <th>email</th>
                                <th>nama_ayah</th>
                                <th>nama_ibu</th>
                                <th>pekerjaan_ayah</th>
                                <th>pekerjaan_ibu</th>
                                <th>no_hp_ortu</th>
                                <th>status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>12345</td>
                                <td>Ahmad Fauzi</td>
                                <td>L</td>
                                <td>Jakarta</td>
                                <td>2008-05-15</td>
                                <td>7A</td>
                                <td>Jl. Merdeka No. 123</td>
                                <td>081234567890</td>
                                <td>ahmad@email.com</td>
                                <td>Budi Santoso</td>
                                <td>Siti Aminah</td>
                                <td>Pegawai Swasta</td>
                                <td>Ibu Rumah Tangga</td>
                                <td>081234567891</td>
                                <td>aktif</td>
                            </tr>
                            <tr>
                                <td>12346</td>
                                <td>Siti Nurhaliza</td>
                                <td>P</td>
                                <td>Bandung</td>
                                <td>2008-08-20</td>
                                <td>7B</td>
                                <td>Jl. Sudirman No. 456</td>
                                <td>081234567892</td>
                                <td>siti@email.com</td>
                                <td>Dedi Kurniawan</td>
                                <td>Ratna Sari</td>
                                <td>Wiraswasta</td>
                                <td>Guru</td>
                                <td>081234567893</td>
                                <td>aktif</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <h6><i class="fas fa-info-circle"></i> Keterangan:</h6>
                    <ul class="mb-0">
                        <li><strong>Kolom bertanda (*) wajib diisi</strong></li>
                        <li><strong>jenis_kelamin:</strong> L (Laki-laki) atau P (Perempuan)</li>
                        <li><strong>tanggal_lahir:</strong> Format YYYY-MM-DD (contoh: 2008-05-15)</li>
                        <li><strong>status:</strong> aktif, nonaktif, lulus, atau pindah</li>
                        <li><strong>nis:</strong> Harus unik, tidak boleh sama</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <a href="?download_template=excel" class="btn btn-success">
                    <i class="fas fa-download"></i> Download Template
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// File upload handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('importFile');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const uploadProgress = document.getElementById('uploadProgress');
const submitBtn = document.getElementById('submitBtn');

// Drag and drop events
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        showFileInfo(files[0]);
    }
});

// File input change
fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        showFileInfo(this.files[0]);
    }
});

// Show file information
function showFileInfo(file) {
    const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
    fileName.textContent = file.name;
    fileSize.textContent = sizeInMB + ' MB';
    fileInfo.style.display = 'block';
    
    // Validate file size
    if (file.size > 5 * 1024 * 1024) { // 5MB
        fileInfo.className = 'mt-3';
        fileInfo.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> File terlalu besar! Maksimal 5MB</div>';
        submitBtn.disabled = true;
    } else {
        submitBtn.disabled = false;
    }
}

// Form submission with progress
document.getElementById('importForm').addEventListener('submit', function(e) {
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Pilih file terlebih dahulu!');
        return;
    }
    
    // Show progress bar
    uploadProgress.style.display = 'block';
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimport...';
    
    // Simulate progress (in real implementation, use XMLHttpRequest for actual progress)
    let progress = 0;
    const progressBar = uploadProgress.querySelector('.progress-bar');
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        progressBar.style.width = progress + '%';
        
        if (progress >= 90) {
            clearInterval(interval);
        }
    }, 500);
});

// Reset form
function resetForm() {
    document.getElementById('importForm').reset();
    fileInfo.style.display = 'none';
    uploadProgress.style.display = 'none';
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-upload"></i> Mulai Import';
}
</script>

<?php
// Handle template download
if (isset($_GET['download_template'])) {
    $type = $_GET['download_template'];
    generateTemplate($type);
    exit;
}

/**
 * Generate and download template file
 */
function generateTemplate($type) {
    $headers = [
        'nis', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'kelas', 'alamat', 'no_hp', 'email', 'nama_ayah', 'nama_ibu',
        'pekerjaan_ayah', 'pekerjaan_ibu', 'no_hp_ortu', 'status'
    ];
    
    $sample_data = [
        ['12345', 'Ahmad Fauzi', 'L', 'Jakarta', '2008-05-15', '7A', 'Jl. Merdeka No. 123', '081234567890', 'ahmad@email.com', 'Budi Santoso', 'Siti Aminah', 'Pegawai Swasta', 'Ibu Rumah Tangga', '081234567891', 'aktif'],
        ['12346', 'Siti Nurhaliza', 'P', 'Bandung', '2008-08-20', '7B', 'Jl. Sudirman No. 456', '081234567892', 'siti@email.com', 'Dedi Kurniawan', 'Ratna Sari', 'Wiraswasta', 'Guru', '081234567893', 'aktif']
    ];
    
    if ($type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="template_import_siswa.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        fputcsv($output, $headers);
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    } else {
        // Excel template
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="template_import_siswa.xls"');
        
        echo '<table border="1">';
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($sample_data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
}

/**
 * Process uploaded import file
 */
function processImportFile($uploadedFile, $fileExtension) {
    $result = [
        'success' => false,
        'message' => '',
        'imported' => 0,
        'updated' => 0,
        'errors' => []
    ];
    
    try {
        // Read file content
        $filePath = $uploadedFile['tmp_name'];
        $data = [];
        
        if ($fileExtension === 'csv') {
            $data = readCSV($filePath);
        } else {
            $data = readExcel($filePath);
        }
        
        if (empty($data)) {
            throw new Exception('File kosong atau format tidak valid');
        }
        
        // Process data
        $imported = 0;
        $updated = 0;
        $import_mode = $_POST['import_mode'] ?? 'insert_update';
        $strict_validation = isset($_POST['strict_validation']);
        
        foreach ($data as $index => $row) {
            $row_number = $index + 2; // +2 because index starts from 0 and we have header
            
            try {
                // Validate required fields
                if (empty($row['nis']) || empty($row['nama']) || empty($row['jenis_kelamin'])) {
                    throw new Exception("Baris $row_number: NIS, nama, dan jenis kelamin wajib diisi");
                }
                
                // Check if NIS exists
                $existing = getOne("SELECT id FROM siswa WHERE nis = ?", [$row['nis']]);
                
                if ($existing) {
                    if ($import_mode === 'insert_only') {
                        continue; // Skip existing
                    }
                    
                    // Update existing record
                    $updateData = prepareStudentData($row);
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    $updateData['id'] = $existing['id'];
                    
                    $set_clause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($updateData)));
                    unset($updateData['id']);
                    $updateData['id'] = $existing['id'];
                    
                    $sql = "UPDATE siswa SET " . str_replace(', id = :id', '', $set_clause) . " WHERE id = :id";
                    execute($sql, $updateData);
                    $updated++;
                    
                } else {
                    if ($import_mode === 'update_only') {
                        continue; // Skip new records
                    }
                    
                    // Insert new record
                    $insertData = prepareStudentData($row);
                    $insertData['created_at'] = date('Y-m-d H:i:s');
                    
                    $columns = implode(', ', array_keys($insertData));
                    $placeholders = ':' . implode(', :', array_keys($insertData));
                    
                    $sql = "INSERT INTO siswa ($columns) VALUES ($placeholders)";
                    execute($sql, $insertData);
                    $imported++;
                }
                
            } catch (Exception $e) {
                $error_msg = "Baris $row_number: " . $e->getMessage();
                
                if ($strict_validation) {
                    throw new Exception($error_msg);
                } else {
                    $result['errors'][] = $error_msg;
                }
            }
        }
        
        $result['success'] = true;
        $result['imported'] = $imported;
        $result['updated'] = $updated;
        
    } catch (Exception $e) {
        $result['message'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Read CSV file
 */
function readCSV($filePath) {
    $data = [];
    $headers = [];
    
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $row_index = 0;
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row_index === 0) {
                $headers = array_map('trim', $row);
            } else {
                $data[] = array_combine($headers, array_map('trim', $row));
            }
            $row_index++;
        }
        
        fclose($handle);
    }
    
    return $data;
}

/**
 * Read Excel file (basic implementation)
 */
function readExcel($filePath) {
    // For basic implementation, assume it's saved as CSV-compatible
    // In production, use PhpSpreadsheet library
    return readCSV($filePath);
}

/**
 * Prepare student data for database
 */
function prepareStudentData($row) {
    return [
        'nis' => trim($row['nis']),
        'nama' => trim($row['nama']),
        'jenis_kelamin' => strtoupper(trim($row['jenis_kelamin'])),
        'tempat_lahir' => trim($row['tempat_lahir'] ?? '') ?: null,
        'tanggal_lahir' => !empty($row['tanggal_lahir']) ? date('Y-m-d', strtotime($row['tanggal_lahir'])) : null,
        'kelas' => trim($row['kelas'] ?? '') ?: null,
        'alamat' => trim($row['alamat'] ?? '') ?: null,
        'no_hp' => trim($row['no_hp'] ?? '') ?: null,
        'email' => trim($row['email'] ?? '') ?: null,
        'nama_ayah' => trim($row['nama_ayah'] ?? '') ?: null,
        'nama_ibu' => trim($row['nama_ibu'] ?? '') ?: null,
        'pekerjaan_ayah' => trim($row['pekerjaan_ayah'] ?? '') ?: null,
        'pekerjaan_ibu' => trim($row['pekerjaan_ibu'] ?? '') ?: null,
        'no_hp_ortu' => trim($row['no_hp_ortu'] ?? '') ?: null,
        'status' => trim($row['status'] ?? 'aktif')
    ];
}

include '../../../core/includes/footer.php';
?>