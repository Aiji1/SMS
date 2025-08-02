<?php
/**
 * Header Analyzer untuk Debug Import
 * File: modules/master-data/siswa/header_analyzer.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['analyze_file'])) {
    header('Content-Type: application/json');
    
    try {
        $uploadedFile = $_FILES['analyze_file'];
        
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file: ' . $uploadedFile['error']);
        }
        
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $filePath = $uploadedFile['tmp_name'];
        $analysis = [];
        
        if ($fileExtension === 'csv') {
            // Analyze CSV
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $rowCount = 0;
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE && $rowCount < 5) {
                    $analysis['rows'][] = [
                        'row_number' => $rowCount + 1,
                        'raw_data' => $row,
                        'cell_analysis' => array_map(function($cell, $index) {
                            return [
                                'index' => $index,
                                'value' => $cell,
                                'length' => strlen($cell),
                                'trimmed' => trim($cell),
                                'trimmed_length' => strlen(trim($cell)),
                                'is_empty_after_trim' => trim($cell) === '',
                                'has_bom' => strpos($cell, "\xEF\xBB\xBF") === 0,
                                'ascii_codes' => array_map('ord', str_split(substr($cell, 0, 10)))
                            ];
                        }, $row, array_keys($row))
                    ];
                    $rowCount++;
                }
                fclose($handle);
            }
        } else if (in_array($fileExtension, ['xlsx', 'xls'])) {
            // Analyze Excel
            if (!$vendorLoaded || !class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new Exception('PhpSpreadsheet tidak tersedia. Gunakan CSV untuk analisis.');
            }
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            
            $rowCount = 0;
            foreach ($rows as $rowIndex => $row) {
                if ($rowCount >= 5) break;
                
                $analysis['rows'][] = [
                    'row_number' => $rowCount + 1,
                    'excel_row' => $rowIndex,
                    'raw_data' => array_values($row),
                    'cell_analysis' => array_map(function($cell, $index) {
                        return [
                            'index' => $index,
                            'value' => $cell,
                            'type' => gettype($cell),
                            'length' => is_string($cell) ? strlen($cell) : 0,
                            'trimmed' => is_string($cell) ? trim($cell) : $cell,
                            'trimmed_length' => is_string($cell) ? strlen(trim($cell)) : 0,
                            'is_empty_after_trim' => is_string($cell) ? trim($cell) === '' : empty($cell),
                            'is_null' => $cell === null,
                            'ascii_codes' => is_string($cell) ? array_map('ord', str_split(substr($cell, 0, 10))) : []
                        ];
                    }, array_values($row), array_keys(array_values($row)))
                ];
                $rowCount++;
            }
            
            $spreadsheet->disconnectWorksheets();
        }
        
        // Header mapping analysis
        if (!empty($analysis['rows'])) {
            $headerRow = $analysis['rows'][0];
            $analysis['header_mapping'] = [];
            
            $headerMapping = [
                'nis' => 'nis',
                'nama' => 'nama',
                'jenis kelamin (l/p)' => 'jenis_kelamin',
                'jenis kelamin' => 'jenis_kelamin',
                'tempat lahir' => 'tempat_lahir',
                'tanggal lahir (yyyy-mm-dd)' => 'tanggal_lahir',
                'tanggal lahir' => 'tanggal_lahir',
                'alamat' => 'alamat',
                'no hp' => 'no_hp',
                'kelas' => 'kelas',
                'nama orang tua' => 'nama_ortu',
                'no hp orang tua' => 'no_hp_ortu'
            ];
            
            foreach ($headerRow['cell_analysis'] as $cell) {
                $originalHeader = $cell['value'];
                $normalizedHeader = strtolower(trim($originalHeader ?? ''));
                $mappedHeader = $headerMapping[$normalizedHeader] ?? $normalizedHeader;
                
                $analysis['header_mapping'][] = [
                    'original' => $originalHeader,
                    'normalized' => $normalizedHeader,
                    'mapped' => $mappedHeader,
                    'is_required' => in_array($mappedHeader, ['nis', 'nama']),
                    'will_be_found' => isset($headerMapping[$normalizedHeader])
                ];
            }
        }
        
        // Data validation for row 2
        if (count($analysis['rows']) > 1) {
            $dataRow = $analysis['rows'][1];
            $analysis['data_validation'] = [];
            
            foreach ($dataRow['cell_analysis'] as $index => $cell) {
                $headerInfo = $analysis['header_mapping'][$index] ?? null;
                $fieldName = $headerInfo['mapped'] ?? "unknown_$index";
                
                $validation = [
                    'field' => $fieldName,
                    'value' => $cell['value'],
                    'is_empty' => $cell['is_empty_after_trim'],
                    'is_required' => $headerInfo['is_required'] ?? false
                ];
                
                if ($validation['is_required'] && $validation['is_empty']) {
                    $validation['error'] = "Field wajib tidak boleh kosong";
                }
                
                $analysis['data_validation'][] = $validation;
            }
        }
        
        echo json_encode([
            'success' => true,
            'file_info' => [
                'name' => $uploadedFile['name'],
                'size' => $uploadedFile['size'],
                'type' => $fileExtension
            ],
            'analysis' => $analysis
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Header Analyzer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .analysis-section { margin-bottom: 30px; }
        .cell-detail { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .error-cell { background: #f8d7da; }
        .success-cell { background: #d4edda; }
        pre { font-size: 12px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-search"></i> Header Analyzer - Debug Import Excel/CSV</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Fungsi:</strong> Menganalisis struktur file Excel/CSV untuk debug masalah import "NIS dan nama wajib diisi"
                    </div>
                    
                    <form id="analyzeForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Upload file untuk dianalisis:</label>
                            <input type="file" name="analyze_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="analyzeFile()">
                            <i class="fas fa-search"></i> Analisis File
                        </button>
                    </form>
                    
                    <div id="results" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function analyzeFile() {
    const form = document.getElementById('analyzeForm');
    const formData = new FormData(form);
    const fileInput = form.querySelector('input[type="file"]');
    
    if (!fileInput.files[0]) {
        alert('Pilih file terlebih dahulu');
        return;
    }
    
    $('#results').html('<div class="alert alert-info">Menganalisis file...</div>');
    
    $.ajax({
        url: 'header_analyzer.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                displayResults(response);
            } else {
                $('#results').html(`<div class="alert alert-danger">Error: ${response.message}</div>`);
            }
        },
        error: function(xhr, status, error) {
            $('#results').html(`<div class="alert alert-danger">
                <h6>Error:</h6>
                <p>${error}</p>
                <pre>${xhr.responseText}</pre>
            </div>`);
        }
    });
}

function displayResults(data) {
    let html = `
        <div class="analysis-section">
            <h6>Informasi File:</h6>
            <ul>
                <li><strong>Nama:</strong> ${data.file_info.name}</li>
                <li><strong>Ukuran:</strong> ${data.file_info.size} bytes</li>
                <li><strong>Tipe:</strong> ${data.file_info.type}</li>
            </ul>
        </div>
    `;
    
    // Header Mapping Analysis
    if (data.analysis.header_mapping) {
        html += `
            <div class="analysis-section">
                <h6>Analisis Header (Baris 1):</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Index</th>
                                <th>Original</th>
                                <th>Normalized</th>
                                <th>Mapped To</th>
                                <th>Required</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.analysis.header_mapping.forEach((header, index) => {
            const statusClass = header.will_be_found ? 'success' : 'warning';
            const statusText = header.will_be_found ? 'Found' : 'Not Found';
            
            html += `
                <tr class="${header.is_required ? 'font-weight-bold' : ''}">
                    <td>${index}</td>
                    <td>"${header.original}"</td>
                    <td>"${header.normalized}"</td>
                    <td>${header.mapped}</td>
                    <td>${header.is_required ? 'YES' : 'No'}</td>
                    <td><span class="badge badge-${statusClass}">${statusText}</span></td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Data Validation
    if (data.analysis.data_validation) {
        html += `
            <div class="analysis-section">
                <h6>Validasi Data (Baris 2):</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                                <th>Required</th>
                                <th>Empty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.analysis.data_validation.forEach(validation => {
            const hasError = validation.error;
            const rowClass = hasError ? 'table-danger' : 'table-success';
            
            html += `
                <tr class="${rowClass}">
                    <td>${validation.field}</td>
                    <td>"${validation.value}"</td>
                    <td>${validation.is_required ? 'YES' : 'No'}</td>
                    <td>${validation.is_empty ? 'YES' : 'No'}</td>
                    <td>${hasError ? validation.error : 'OK'}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Raw Data Analysis
    if (data.analysis.rows) {
        html += `
            <div class="analysis-section">
                <h6>Detail Raw Data:</h6>
        `;
        
        data.analysis.rows.forEach((row, rowIndex) => {
            html += `
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Baris ${row.row_number}</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
            `;
            
            row.cell_analysis.forEach(cell => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="cell-detail">
                            <strong>Cell ${cell.index}:</strong> "${cell.value}"<br>
                            <small>
                                Type: ${cell.type || 'string'} | 
                                Length: ${cell.length} | 
                                Trimmed: "${cell.trimmed}" (${cell.trimmed_length}) |
                                Empty: ${cell.is_empty_after_trim ? 'YES' : 'No'}
                            </small>
                        </div>
                    </div>
                `;
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    $('#results').html(html);
}
</script>
</body>
</html>