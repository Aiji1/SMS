<?php
/**
 * Delete User API
 * File: modules/master-data/users/delete_user.php
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Missing user ID');
    }
    
    $id = (int)$input['id'];
    
    // Check if user exists
    $user = getOne("SELECT id, nama, role, username FROM users WHERE id = ?", [$id]);
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Prevent deleting admin if current user is not admin
    if ($user['role'] === 'admin' && !hasRole(['admin'])) {
        throw new Exception('Cannot delete admin user');
    }
    
    // Prevent deleting self
    $current_user = getUser();
    if ($id == $current_user['id']) {
        throw new Exception('Cannot delete your own account');
    }
    
    // Check for related data (optional)
    // For example, if user is a siswa (murid), check if they have siswa record
    if ($user['role'] === 'murid') {
        $siswa_record = getOne("SELECT id FROM siswa WHERE nis = ?", [$user['username']]);
        if ($siswa_record) {
            // Delete siswa record too or just set user to inactive
            // For safety, we'll just set to inactive instead of deleting
            $result = execute("UPDATE users SET status = 'inactive' WHERE id = ?", [$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User siswa dinonaktifkan (data siswa tetap ada)'
                ]);
            } else {
                throw new Exception('Failed to deactivate user');
            }
            exit;
        }
    }
    
    // Delete user
    $result = execute("DELETE FROM users WHERE id = ?", [$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
    } else {
        throw new Exception('Failed to delete user');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>