<?php
/**
 * Get User API
 * File: modules/master-data/users/get_user.php
 */

header('Content-Type: application/json');

require_once '../../../core/config/database.php';

// Check authorization
if (!hasRole(['admin', 'kepala_sekolah'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid user ID');
    }
    
    $id = (int)$_GET['id'];
    
    $user = getOne("SELECT id, username, nama, email, role, status, created_at FROM users WHERE id = ?", [$id]);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Format created_at
    $user['created_at_formatted'] = date('d F Y H:i', strtotime($user['created_at']));
    
    // Get role name
    $role_names = [
        'admin' => 'Administrator',
        'kepala_sekolah' => 'Kepala Sekolah',
        'guru' => 'Guru',
        'wali_kelas' => 'Wali Kelas',
        'petugas' => 'Petugas',
        'wali_murid' => 'Wali Murid',
        'murid' => 'Murid'
    ];
    
    $user['role_name'] = $role_names[$user['role']] ?? ucfirst($user['role']);
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>