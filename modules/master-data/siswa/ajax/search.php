<?php
/**
 * Search Autocomplete untuk Siswa
 * File: modules/master-data/siswa/ajax/search.php
 */

header('Content-Type: application/json');

// Include database connection
require_once '../../../../core/config/database.php';

try {
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    // Search in nama and nis
    $search_query = "SELECT id, nis, nama, kelas, jenis_kelamin 
                     FROM siswa 
                     WHERE (nama LIKE ? OR nis LIKE ?) 
                     AND status = 'aktif'
                     ORDER BY nama ASC 
                     LIMIT ?";
    
    $results = getAll($search_query, ["%$query%", "%$query%", $limit]);
    
    // Format results for autocomplete
    $formatted_results = [];
    foreach ($results as $siswa) {
        $formatted_results[] = [
            'id' => $siswa['id'],
            'value' => $siswa['nama'],
            'label' => $siswa['nama'] . ' (' . $siswa['nis'] . ')',
            'nis' => $siswa['nis'],
            'nama' => $siswa['nama'],
            'kelas' => $siswa['kelas'],
            'jenis_kelamin' => $siswa['jenis_kelamin'],
            'jenis_kelamin_text' => $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>