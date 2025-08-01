<?php
/**
 * Delete Multiple Siswa via AJAX
 * File: modules/master-data/siswa/ajax/delete_multiple.php
 */

header('Content-Type: application/json');

// Include database connection
require_once '../../../../core/config/database.php';

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        throw new Exception('Tidak ada siswa yang dipilih');
    }
    
    // Validate IDs
    $ids = array_filter($ids, 'is_numeric');
    if (empty($ids)) {
        throw new Exception('ID siswa tidak valid');
    }
    
    // Get siswa names for response
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $siswa_list = getAll("SELECT id, nama, nis FROM siswa WHERE id IN ($placeholders)", $ids);
    
    if (count($siswa_list) !== count($ids)) {
        throw new Exception('Beberapa siswa tidak ditemukan');
    }
    
    // Check for related data (optional)
    /*
    foreach ($ids as $id) {
        $related_check = getOne("SELECT COUNT(*) as count FROM jurnal WHERE siswa_id = ?", [$id]);
        if ($related_check['count'] > 0) {
            $siswa_name = array_filter($siswa_list, fn($s) => $s['id'] == $id)[0]['nama'] ?? 'Unknown';
            throw new Exception("Tidak dapat menghapus siswa '$siswa_name' karena masih memiliki data terkait");
        }
    }
    */
    
    // Begin transaction
    beginTransaction();
    
    try {
        $deleted_count = 0;
        $deleted_names = [];
        
        foreach ($ids as $id) {
            $result = execute("DELETE FROM siswa WHERE id = ?", [$id]);
            if ($result) {
                $deleted_count++;
                $siswa = array_filter($siswa_list, fn($s) => $s['id'] == $id);
                if (!empty($siswa)) {
                    $deleted_names[] = reset($siswa)['nama'];
                }
            }
        }
        
        // Commit transaction
        commit();
        
        // Log the deletion (optional)
        $log_message = "Menghapus $deleted_count siswa: " . implode(', ', array_slice($deleted_names, 0, 3));
        if (count($deleted_names) > 3) {
            $log_message .= ' dan ' . (count($deleted_names) - 3) . ' lainnya';
        }
        // logActivity('bulk_delete_siswa', $log_message);
        
        echo json_encode([
            'success' => true,
            'message' => "Berhasil menghapus $deleted_count siswa",
            'deleted_count' => $deleted_count,
            'deleted_names' => $deleted_names
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Database transaction functions
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