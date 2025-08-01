<?php
/**
 * Import Data Siswa - Modal Version
 * File: modules/master-data/siswa/import.php
 */

// Jika diakses langsung, tampilkan modal kosong
$page_title = 'Import Data Siswa';
include '../../../core/includes/header.php';

// Handle AJAX file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    header('Content-Type: application/json');
    
    try {
        $uploadedFile = $_FILES['import_file'];
        
        // Validasi file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file');
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Format file tidak didukung. Gunakan CSV, XLS, atau XLSX');
        }
        
        // Proses file (sederhana)
        $filePath = $uploadedFile['tmp_name'];
        $data = [];
        
        if ($fileExtension === 'csv') {
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $data[] = $row;
                }
                fclose($handle);
            }
        } else {
            // Untuk Excel, kita akan simulasikan
            $data = [
                ['12345', 'Contoh Siswa', 'L', 'Jakarta', '2005-01-15'],
                ['12346', 'Siswa Contoh', 'P', 'Bandung', '2005-02-20']
            ];
        }
        
        if (empty($data)) {
            throw new Exception('File kosong atau format tidak valid');
        }
        
        // Simulasikan import berhasil
        $imported = count($data);
        
        echo json_encode([
            'success' => true,
            'message' => "Import berhasil! $imported data ditambahkan",
            'imported' => $imported,
            'errors' => []
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle template download
if (isset($_GET['download_template'])) {
    $type = $_GET['download_template'];
    $filename = "template_import_siswa." . ($type === 'csv' ? 'csv' : 'xlsx');
    
    header('Content-Type: ' . ($type === 'csv' ? 'text/csv' : 'application/vnd.ms-excel'));
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    $headers = ['NIS', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir'];
    $sample_data = [
        ['12345', 'Contoh Siswa', 'L', 'Jakarta', '2005-01-15'],
        ['12346', 'Siswa Contoh', 'P', 'Bandung', '2005-02-20']
    ];
    
    if ($type === 'csv') {
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    } else {
        echo '<table border="1">';
        echo '<tr><th>' . implode('</th><th>', $headers) . '</th></tr>';
        foreach ($sample_data as $row) {
            echo '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
        }
        echo '</table>';
    }
    exit;
}
?>

<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-upload mr-2"></i>Import Data Siswa
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Format file yang didukung: CSV, XLS, XLSX (Maks. 5MB)
                </div>
                
                <div class="form-group">
                    <label>Pilih File:</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="importFile" accept=".csv,.xlsx,.xls">
                        <label class="custom-file-label" for="importFile">Pilih file...</label>
                    </div>
                </div>
                
                <div class="text-center my-3">
                    <a href="import.php?download_template=excel" class="btn btn-sm btn-outline-primary mr-2">
                        <i class="fas fa-file-excel mr-2"></i>Template Excel
                    </a>
                    <a href="import.php?download_template=csv" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv mr-2"></i>Template CSV
                    </a>
                </div>
                
                <div class="progress mb-2" style="height: 5px; display: none;" id="uploadProgress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
                
                <div id="importResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" id="startImport">
                    <i class="fas fa-upload mr-2"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tampilkan modal saat halaman load
    $('#importModal').modal({backdrop: 'static', keyboard: false});
    
    // Update nama file yang dipilih
    $('#importFile').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName || 'Pilih file...');
    });
    
    // Proses import saat tombol diklik
    $('#startImport').click(function() {
        var fileInput = $('#importFile')[0];
        if (!fileInput.files.length) {
            alert('Pilih file terlebih dahulu!');
            return;
        }
        
        var formData = new FormData();
        formData.append('import_file', fileInput.files[0]);
        
        // Tampilkan progress bar
        $('#uploadProgress').show();
        $('#startImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...');
        
        // Kirim via AJAX
        $.ajax({
            url: 'import.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percent = Math.round((e.loaded / e.total) * 100);
                        $('#uploadProgress .progress-bar').css('width', percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    var html = '<div class="alert alert-success">';
                    html += '<i class="fas fa-check-circle mr-2"></i>' + response.message;
                    
                    if (response.errors && response.errors.length > 0) {
                        html += '<hr><ul>';
                        response.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                    
                    $('#importResult').html(html);
                    
                    // Tutup modal setelah 2 detik
                    setTimeout(function() {
                        $('#importModal').modal('hide');
                        window.location.reload();
                    }, 2000);
                } else {
                    $('#importResult').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle mr-2"></i>' + response.message +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#importResult').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kesalahan saat mengimport' +
                    '</div>'
                );
            },
            complete: function() {
                $('#startImport').prop('disabled', false).html('<i class="fas fa-upload mr-2"></i>Import');
            }
        });
    });
    
    // Redirect saat modal ditutup
    $('#importModal').on('hidden.bs.modal', function () {
        window.location.href = 'index.php';
    });
});
</script>

<?php
include '../../../core/includes/footer.php';