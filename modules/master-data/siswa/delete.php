<?php
/**
 * Hapus Siswa
 * File: modules/master-data/siswa/delete.php
 */

// Include database configuration
require_once __DIR__ . '/../../../core/config/database.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'ID siswa tidak valid';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // Get siswa data first
    $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
    
    if (!$siswa) {
        $_SESSION['message'] = 'Data siswa tidak ditemukan';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
    
    // Check for related data (optional - implement based on your needs)
    // Example: Check if student has related records in other tables
    /*
    $related_jurnal = getOne("SELECT COUNT(*) as count FROM jurnal WHERE siswa_id = ?", [$id]);
    $related_nilai = getOne("SELECT COUNT(*) as count FROM nilai WHERE siswa_id = ?", [$id]);
    
    if ($related_jurnal['count'] > 0 || $related_nilai['count'] > 0) {
        $_SESSION['message'] = 'Tidak dapat menghapus siswa karena masih memiliki data terkait (jurnal/nilai)';
        $_SESSION['message_type'] = 'warning';
        header('Location: view.php?id=' . $id);
        exit;
    }
    */
    
    // Perform deletion
    $result = execute("DELETE FROM siswa WHERE id = ?", [$id]);
    
    if ($result) {
        $_SESSION['message'] = 'Data siswa "' . $siswa['nama'] . '" berhasil dihapus';
        $_SESSION['message_type'] = 'success';
        
        // Log the deletion (optional)
        // logActivity('delete_siswa', 'Menghapus siswa: ' . $siswa['nama'] . ' (NIS: ' . $siswa['nis'] . ')');
        
        header('Location: index.php');
        exit;
    } else {
        throw new Exception('Gagal menghapus data siswa');
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    header('Location: view.php?id=' . $id);
    exit;
}
?>