<?php
/**
 * Validation Functions untuk Siswa
 * File: modules/master-data/siswa/includes/validation.php
 */

/**
 * Validate student data
 */
function validateStudentData($data, $id = null) {
    $errors = [];
    
    // NIS validation
    if (empty($data['nis'])) {
        $errors[] = 'NIS wajib diisi';
    } elseif (strlen($data['nis']) < 5) {
        $errors[] = 'NIS minimal 5 karakter';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $data['nis'])) {
        $errors[] = 'NIS hanya boleh berisi huruf dan angka';
    } else {
        // Check if NIS already exists
        $check_query = $id ? 
            "SELECT id FROM siswa WHERE nis = ? AND id != ?" : 
            "SELECT id FROM siswa WHERE nis = ?";
        $check_params = $id ? [$data['nis'], $id] : [$data['nis']];
        
        $existing = getOne($check_query, $check_params);
        if ($existing) {
            $errors[] = 'NIS sudah digunakan oleh siswa lain';
        }
    }
    
    // Nama validation
    if (empty($data['nama'])) {
        $errors[] = 'Nama wajib diisi';
    } elseif (strlen($data['nama']) < 2) {
        $errors[] = 'Nama minimal 2 karakter';
    } elseif (strlen($data['nama']) > 100) {
        $errors[] = 'Nama maksimal 100 karakter';
    } elseif (!preg_match('/^[a-zA-Z\s\'.,-]+$/u', $data['nama'])) {
        $errors[] = 'Nama hanya boleh berisi huruf, spasi, dan tanda baca dasar';
    }
    
    // Jenis kelamin validation
    if (empty($data['jenis_kelamin'])) {
        $errors[] = 'Jenis kelamin wajib dipilih';
    } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
        $errors[] = 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan)';
    }
    
    // Email validation (optional)
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        } elseif (strlen($data['email']) > 100) {
            $errors[] = 'Email maksimal 100 karakter';
        }
    }
    
    // Phone number validation (optional)
    if (!empty($data['no_hp'])) {
        if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $data['no_hp'])) {
            $errors[] = 'Format nomor HP tidak valid (10-15 digit)';
        }
    }
    
    // Parent phone validation (optional)
    if (!empty($data['no_hp_ortu'])) {
        if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $data['no_hp_ortu'])) {
            $errors[] = 'Format nomor HP orang tua tidak valid (10-15 digit)';
        }
    }
    
    // Date validation (optional)
    if (!empty($data['tanggal_lahir'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
        if (!$date || $date->format('Y-m-d') !== $data['tanggal_lahir']) {
            $errors[] = 'Format tanggal lahir tidak valid (YYYY-MM-DD)';
        } else {
            // Check if date is reasonable
            $now = new DateTime();
            $min_date = new DateTime('-30 years');
            $max_date = new DateTime('-5 years');
            
            if ($date < $min_date || $date > $max_date) {
                $errors[] = 'Tanggal lahir tidak masuk akal (harus antara 5-30 tahun yang lalu)';
            }
        }
    }
    
    // Status validation
    if (!empty($data['status'])) {
        $valid_statuses = ['aktif', 'nonaktif', 'lulus', 'pindah'];
        if (!in_array($data['status'], $valid_statuses)) {
            $errors[] = 'Status tidak valid';
        }
    }
    
    // Text field length validations
    $text_fields = [
        'tempat_lahir' => 50,
        'alamat' => 500,
        'kelas' => 20,
        'nama_ayah' => 100,
        'nama_ibu' => 100,
        'pekerjaan_ayah' => 100,
        'pekerjaan_ibu' => 100
    ];
    
    foreach ($text_fields as $field => $max_length) {
        if (!empty($data[$field]) && strlen($data[$field]) > $max_length) {
            $field_name = str_replace('_', ' ', ucfirst($field));
            $errors[] = "$field_name maksimal $max_length karakter";
        }
    }
    
    return $errors;
}

/**
 * Validate import data row
 */
function validateImportRow($row, $row_number) {
    $errors = [];
    
    // Required fields
    if (empty($row['nis'])) {
        $errors[] = "Baris $row_number: NIS wajib diisi";
    }
    
    if (empty($row['nama'])) {
        $errors[] = "Baris $row_number: Nama wajib diisi";
    }
    
    if (empty($row['jenis_kelamin'])) {
        $errors[] = "Baris $row_number: Jenis kelamin wajib diisi";
    } elseif (!in_array(strtoupper($row['jenis_kelamin']), ['L', 'P'])) {
        $errors[] = "Baris $row_number: Jenis kelamin harus L atau P";
    }
    
    // Date format validation
    if (!empty($row['tanggal_lahir'])) {
        $date_formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        $valid_date = false;
        
        foreach ($date_formats as $format) {
            $date = DateTime::createFromFormat($format, $row['tanggal_lahir']);
            if ($date && $date->format($format) === $row['tanggal_lahir']) {
                $valid_date = true;
                break;
            }
        }
        
        if (!$valid_date) {
            $errors[] = "Baris $row_number: Format tanggal lahir tidak valid (gunakan YYYY-MM-DD, DD/MM/YYYY, atau DD-MM-YYYY)";
        }
    }
    
    // Email validation
    if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Baris $row_number: Format email tidak valid";
    }
    
    // Phone validation
    if (!empty($row['no_hp']) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $row['no_hp'])) {
        $errors[] = "Baris $row_number: Format nomor HP tidak valid";
    }
    
    if (!empty($row['no_hp_ortu']) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $row['no_hp_ortu'])) {
        $errors[] = "Baris $row_number: Format nomor HP orang tua tidak valid";
    }
    
    return $errors;
}

/**
 * Sanitize student data
 */
function sanitizeStudentData($data) {
    $sanitized = [];
    
    // Text fields to trim and sanitize
    $text_fields = [
        'nis', 'nama', 'tempat_lahir', 'alamat', 'no_hp', 'email',
        'nama_ayah', 'nama_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu', 'no_hp_ortu', 'kelas'
    ];
    
    foreach ($text_fields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = trim($data[$field]) ?: null;
        }
    }
    
    // Specific sanitizations
    if (isset($data['jenis_kelamin'])) {
        $sanitized['jenis_kelamin'] = strtoupper(trim($data['jenis_kelamin']));
    }
    
    if (isset($data['tanggal_lahir']) && !empty($data['tanggal_lahir'])) {
        // Try to convert to standard format
        $date_formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        $sanitized['tanggal_lahir'] = null;
        
        foreach ($date_formats as $format) {
            $date = DateTime::createFromFormat($format, $data['tanggal_lahir']);
            if ($date && $date->format($format) === $data['tanggal_lahir']) {
                $sanitized['tanggal_lahir'] = $date->format('Y-m-d');
                break;
            }
        }
    }
    
    if (isset($data['status'])) {
        $sanitized['status'] = strtolower(trim($data['status'])) ?: 'aktif';
    }
    
    // Clean phone numbers
    if (isset($sanitized['no_hp']) && $sanitized['no_hp']) {
        $sanitized['no_hp'] = preg_replace('/[^0-9+]/', '', $sanitized['no_hp']);
    }
    
    if (isset($sanitized['no_hp_ortu']) && $sanitized['no_hp_ortu']) {
        $sanitized['no_hp_ortu'] = preg_replace('/[^0-9+]/', '', $sanitized['no_hp_ortu']);
    }
    
    // Clean email
    if (isset($sanitized['email']) && $sanitized['email']) {
        $sanitized['email'] = strtolower($sanitized['email']);
    }
    
    return $sanitized;
}

/**
 * Validate bulk operations
 */
function validateBulkOperation($action, $ids, $additional_data = []) {
    $errors = [];
    
    // Validate IDs
    if (empty($ids) || !is_array($ids)) {
        $errors[] = 'Tidak ada siswa yang dipilih';
        return $errors;
    }
    
    $ids = array_filter($ids, 'is_numeric');
    if (empty($ids)) {
        $errors[] = 'ID siswa tidak valid';
        return $errors;
    }
    
    // Check if students exist
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $existing_count = getOne("SELECT COUNT(*) as count FROM siswa WHERE id IN ($placeholders)", $ids);
    
    if ($existing_count['count'] != count($ids)) {
        $errors[] = 'Beberapa siswa tidak ditemukan';
    }
    
    // Action-specific validations
    switch ($action) {
        case 'bulk_edit_kelas':
            if (empty($additional_data['kelas'])) {
                $errors[] = 'Kelas baru wajib diisi';
            } elseif (strlen($additional_data['kelas']) > 20) {
                $errors[] = 'Kelas maksimal 20 karakter';
            }
            break;
            
        case 'bulk_delete':
            // Check for related data (uncomment if needed)
            /*
            foreach ($ids as $id) {
                $related_check = getOne("SELECT COUNT(*) as count FROM jurnal WHERE siswa_id = ?", [$id]);
                if ($related_check['count'] > 0) {
                    $siswa_name = getOne("SELECT nama FROM siswa WHERE id = ?", [$id]);
                    $errors[] = 'Tidak dapat menghapus siswa "' . $siswa_name['nama'] . '" karena masih memiliki data terkait';
                }
            }
            */
            break;
    }
    
    return $errors;
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_extensions = ['csv', 'xlsx', 'xls'], $max_size = 5242880) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File terlalu besar';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File tidak terupload sempurna';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'Tidak ada file yang dipilih';
                break;
            default:
                $errors[] = 'Error upload file';
        }
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = round($max_size / 1024 / 1024, 1);
        $errors[] = "File terlalu besar. Maksimal {$max_mb}MB";
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = 'Format file tidak didukung. Gunakan: ' . implode(', ', $allowed_extensions);
    }
    
    // Check MIME type (basic security)
    $allowed_mimes = [
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel'
    ];
    
    if (isset($allowed_mimes[$extension])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // CSV might have different MIME types
        if ($extension === 'csv' && !in_array($mime, ['text/csv', 'text/plain', 'application/csv'])) {
            $errors[] = 'File CSV tidak valid';
        } elseif ($extension !== 'csv' && $mime !== $allowed_mimes[$extension]) {
            $errors[] = 'File tidak sesuai dengan ekstensi';
        }
    }
    
    return $errors;
}

/**
 * Get validation error message for specific field
 */
function getFieldError($field, $errors) {
    foreach ($errors as $error) {
        if (stripos($error, $field) !== false) {
            return $error;
        }
    }
    return null;
}

/**
 * Check if field has validation error
 */
function hasFieldError($field, $errors) {
    return getFieldError($field, $errors) !== null;
}

/**
 * Generate validation rules description
 */
function getValidationRules() {
    return [
        'nis' => [
            'required' => true,
            'min_length' => 5,
            'pattern' => 'Huruf dan angka saja',
            'unique' => true
        ],
        'nama' => [
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'pattern' => 'Huruf, spasi, dan tanda baca dasar'
        ],
        'jenis_kelamin' => [
            'required' => true,
            'options' => ['L', 'P']
        ],
        'email' => [
            'required' => false,
            'format' => 'Email valid',
            'max_length' => 100
        ],
        'no_hp' => [
            'required' => false,
            'pattern' => '10-15 digit angka',
            'format' => 'Nomor telepon valid'
        ],
        'tanggal_lahir' => [
            'required' => false,
            'format' => 'YYYY-MM-DD',
            'range' => '5-30 tahun yang lalu'
        ]
    ];
}

/**
 * Get validation summary for display
 */
function getValidationSummary($errors) {
    if (empty($errors)) {
        return null;
    }
    
    $summary = [
        'total_errors' => count($errors),
        'categories' => [],
        'errors' => $errors
    ];
    
    // Categorize errors
    foreach ($errors as $error) {
        if (stripos($error, 'nis') !== false) {
            $summary['categories']['nis'][] = $error;
        } elseif (stripos($error, 'nama') !== false) {
            $summary['categories']['nama'][] = $error;
        } elseif (stripos($error, 'email') !== false) {
            $summary['categories']['email'][] = $error;
        } elseif (stripos($error, 'hp') !== false) {
            $summary['categories']['phone'][] = $error;
        } elseif (stripos($error, 'tanggal') !== false) {
            $summary['categories']['date'][] = $error;
        } else {
            $summary['categories']['other'][] = $error;
        }
    }
    
    return $summary;
}
?>