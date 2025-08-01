<?php
/**
 * Add User Handler
 * File: modules/master-data/users/add_user.php
 */

require_once '../../../core/config/database.php';

// Check authorization
if (!hasRole(['admin', 'kepala_sekolah'])) {
    $_SESSION['message'] = 'Anda tidak memiliki akses';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

if ($_POST) {
    try {
        // Validation
        $errors = [];
        
        if (empty($_POST['username'])) {
            $errors[] = 'Username wajib diisi';
        } else {
            // Check if username already exists
            $check_username = getOne("SELECT id FROM users WHERE username = ?", [$_POST['username']]);
            if ($check_username) {
                $errors[] = 'Username sudah digunakan';
            }
        }
        
        if (empty($_POST['nama'])) {
            $errors[] = 'Nama wajib diisi';
        }
        
        if (empty($_POST['role'])) {
            $errors[] = 'Role wajib dipilih';
        }
        
        if (empty($_POST['password'])) {
            $errors[] = 'Password wajib diisi';
        } elseif (strlen($_POST['password']) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        }
        
        if (!empty($_POST['email'])) {
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid';
            } else {
                // Check if email already exists
                $check_email = getOne("SELECT id FROM users WHERE email = ?", [$_POST['email']]);
                if ($check_email) {
                    $errors[] = 'Email sudah digunakan';
                }
            }
        }
        
        // Validate role
        $valid_roles = ['admin', 'kepala_sekolah', 'guru', 'wali_kelas', 'petugas', 'wali_murid', 'murid'];
        if (!in_array($_POST['role'], $valid_roles)) {
            $errors[] = 'Role tidak valid';
        }
        
        if (empty($errors)) {
            $data = [
                'username' => trim($_POST['username']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'nama' => trim($_POST['nama']),
                'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'role' => $_POST['role'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
            $result = execute($sql, $data);
            
            if ($result) {
                $_SESSION['message'] = 'User berhasil ditambahkan';
                $_SESSION['message_type'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                throw new Exception('Gagal menyimpan data user');
            }
        } else {
            $_SESSION['message'] = implode('<br>', $errors);
            $_SESSION['message_type'] = 'danger';
            header('Location: index.php');
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>