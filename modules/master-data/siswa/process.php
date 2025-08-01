<?php
/**
 * Process Handler untuk CRUD Siswa
 * File: modules/master-data/siswa/process.php
 */

// Include SMS Core functions
require_once '../../../core/config/database.php';

// Check if action is provided
if (!isset($_POST['action'])) {
    $_SESSION['message'] = 'Aksi tidak valid';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'bulk_delete':
            handleBulkDelete();
            break;
            
        case 'bulk_edit_kelas':
            handleBulkEditKelas();
            break;
            
        case 'export_selected':
            handleExportSelected();
            break;
            
        default:
            throw new Exception('Aksi tidak dikenali');
    }
} catch (Exception $e) {
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

/**
 * Handle bulk delete siswa
 */
function handleBulkDelete() {
    if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
        throw new Exception('Tidak ada siswa yang dipilih');
    }
    
    $ids = array_filter($_POST['ids'], 'is_numeric');
    
    if (empty($ids)) {
        throw new Exception('ID siswa tidak valid');
    }
    
    // Get siswa names for logging
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $siswa_list = getAll("SELECT nama, nis FROM siswa WHERE id IN ($placeholders)", $ids);
    
    // Check for related data (optional)
    /*
    foreach ($ids as $id) {
        $related_check = getOne("SELECT COUNT(*) as count FROM jurnal WHERE siswa_id = ?", [$id]);
        if ($related_check['count'] > 0) {
            $siswa_name = getOne("SELECT nama FROM siswa WHERE id = ?", [$id]);
            throw new Exception('Tidak dapat menghapus siswa "' . $siswa_name['nama'] . '" karena masih memiliki data terkait');
        }
    }
    */
    
    // Begin transaction
    beginTransaction();
    
    try {
        // Delete all selected siswa
        $deleted_count = 0;
        foreach ($ids as $id) {
            $result = execute("DELETE FROM siswa WHERE id = ?", [$id]);
            if ($result) {
                $deleted_count++;
            }
        }
        
        // Commit transaction
        commit();
        
        // Create log message
        $siswa_names = array_column($siswa_list, 'nama');
        $log_message = "Menghapus $deleted_count siswa: " . implode(', ', array_slice($siswa_names, 0, 3));
        if (count($siswa_names) > 3) {
            $log_message .= ' dan ' . (count($siswa_names) - 3) . ' lainnya';
        }
        
        // Log the bulk deletion (optional)
        // logActivity('bulk_delete_siswa', $log_message);
        
        $_SESSION['message'] = "Berhasil menghapus $deleted_count siswa";
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        // Rollback transaction
        rollback();
        throw $e;
    }
    
    header('Location: index.php');
    exit;
}

/**
 * Handle bulk edit kelas
 */
function handleBulkEditKelas() {
    if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
        throw new Exception('Tidak ada siswa yang dipilih');
    }
    
    if (empty($_POST['kelas'])) {
        throw new Exception('Kelas baru wajib diisi');
    }
    
    $ids = array_filter($_POST['ids'], 'is_numeric');
    $new_kelas = trim($_POST['kelas']);
    
    if (empty($ids)) {
        throw new Exception('ID siswa tidak valid');
    }
    
    // Get siswa names for logging
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $siswa_list = getAll("SELECT nama FROM siswa WHERE id IN ($placeholders)", $ids);
    
    // Begin transaction
    beginTransaction();
    
    try {
        // Update kelas for all selected siswa
        $updated_count = 0;
        foreach ($ids as $id) {
            $result = execute("UPDATE siswa SET kelas = ?, updated_at = ? WHERE id = ?", [
                $new_kelas,
                date('Y-m-d H:i:s'),
                $id
            ]);
            if ($result) {
                $updated_count++;
            }
        }
        
        // Commit transaction
        commit();
        
        // Create log message
        $siswa_names = array_column($siswa_list, 'nama');
        $log_message = "Mengubah kelas $updated_count siswa ke '$new_kelas': " . implode(', ', array_slice($siswa_names, 0, 3));
        if (count($siswa_names) > 3) {
            $log_message .= ' dan ' . (count($siswa_names) - 3) . ' lainnya';
        }
        
        // Log the bulk edit (optional)
        // logActivity('bulk_edit_kelas', $log_message);
        
        $_SESSION['message'] = "Berhasil mengubah kelas $updated_count siswa ke '$new_kelas'";
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        // Rollback transaction
        rollback();
        throw $e;
    }
    
    header('Location: index.php');
    exit;
}

/**
 * Handle export selected siswa
 */
function handleExportSelected() {
    if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
        throw new Exception('Tidak ada siswa yang dipilih');
    }
    
    $ids = array_filter($_POST['ids'], 'is_numeric');
    
    if (empty($ids)) {
        throw new Exception('ID siswa tidak valid');
    }
    
    $export_type = $_POST['export_type'] ?? 'excel';
    
    // Get selected siswa data
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $siswa_data = getAll("SELECT * FROM siswa WHERE id IN ($placeholders) ORDER BY nama", $ids);
    
    if (empty($siswa_data)) {
        throw new Exception('Data siswa tidak ditemukan');
    }
    
    // Store selected data in session for export
    $_SESSION['export_data'] = $siswa_data;
    $_SESSION['export_type'] = $export_type;
    
    // Redirect to export handler
    header("Location: export.php?type=$export_type&selected=1");
    exit;
}

/**
 * Database transaction functions (implement based on your database class)
 */
function beginTransaction() {
    global $pdo;
    if (isset($pdo)) {
        $pdo->beginTransaction();
    }
}

function commit() {
    global $pdo;
    if (isset($pdo)) {
        $pdo->commit();
    }
}

function rollback() {
    global $pdo;
    if (isset($pdo)) {
        $pdo->rollBack();
    }
}
?>