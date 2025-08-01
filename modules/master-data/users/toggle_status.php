<?php
/**
 * Toggle User Status API
 * File: modules/master-data/users/toggle_status.php
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
    
    if (!isset($input['id']) || !isset($input['status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $id = (int)$input['id'];
    $status = $input['status'];
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid status');
    }
    
    // Check if user exists
    $user = getOne("SELECT id, nama, role FROM users WHERE id = ?", [$id]);
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Prevent deactivating admin if current user is not admin
    if ($user['role'] === 'admin' && !hasRole(['admin'])) {
        throw new Exception('Cannot modify admin user');
    }
    
    // Update status
    $result = execute("UPDATE users SET status = ? WHERE id = ?", [$status, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status user berhasil diubah'
        ]);
    } else {
        throw new Exception('Failed to update user status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>