<?php
/**
 * Get Data Siswa untuk AJAX/JSON
 * File: modules/master-data/siswa/ajax/get_siswa.php
 */

header('Content-Type: application/json');

// Include database connection
require_once '../../../../core/config/database.php';

try {
    // Get parameters
    $id = $_GET['id'] ?? null;
    $search = $_GET['search'] ?? '';
    $kelas = $_GET['kelas'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    $page = (int)($_GET['page'] ?? 1);
    $offset = ($page - 1) * $limit;
    
    if ($id) {
        // Get single siswa
        $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
        
        if ($siswa) {
            // Format data
            $siswa['tanggal_lahir_formatted'] = $siswa['tanggal_lahir'] ? 
                date('d F Y', strtotime($siswa['tanggal_lahir'])) : null;
            $siswa['created_at_formatted'] = $siswa['created_at'] ? 
                date('d F Y H:i', strtotime($siswa['created_at'])) : null;
            $siswa['updated_at_formatted'] = $siswa['updated_at'] ? 
                date('d F Y H:i', strtotime($siswa['updated_at'])) : null;
            
            echo json_encode([
                'success' => true,
                'data' => $siswa
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Siswa tidak ditemukan'
            ]);
        }
        
    } else {
        // Get multiple siswa with filters
        $where_conditions = [];
        $params = [];
        
        if ($search) {
            $where_conditions[] = "(nama LIKE ? OR nis LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($kelas) {
            $where_conditions[] = "kelas = ?";
            $params[] = $kelas;
        }
        
        $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get total count
        $total_query = "SELECT COUNT(*) as total FROM siswa $where_clause";
        $total_result = getOne($total_query, $params);
        $total = $total_result ? $total_result['total'] : 0;
        
        // Get data
        $data_query = "SELECT * FROM siswa $where_clause ORDER BY nama ASC LIMIT $limit OFFSET $offset";
        $siswa_data = getAll($data_query, $params);
        
        // Format data
        foreach ($siswa_data as &$siswa) {
            $siswa['tanggal_lahir_formatted'] = $siswa['tanggal_lahir'] ? 
                date('d F Y', strtotime($siswa['tanggal_lahir'])) : null;
            $siswa['jenis_kelamin_text'] = $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan';
            $siswa['status_badge'] = [
                'aktif' => 'success',
                'nonaktif' => 'secondary',
                'lulus' => 'primary',
                'pindah' => 'warning'
            ][$siswa['status']] ?? 'secondary';
        }
        
        $total_pages = ceil($total / $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $siswa_data,
            'pagination' => [
                'total' => $total,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'from' => $offset + 1,
                'to' => min($offset + $limit, $total)
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>