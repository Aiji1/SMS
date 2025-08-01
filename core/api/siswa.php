<?php
/**
 * SMS Core - API Siswa
 * File: core/api/siswa.php
 */

$id = $endpoint_parts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Get specific siswa
            $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
            if ($siswa) {
                jsonResponse(['success' => true, 'data' => $siswa]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Siswa not found'], 404);
            }
        } else {
            // Get all siswa with pagination and search
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $kelas = $_GET['kelas'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            // Build query
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
            $data = getAll($data_query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // Create new siswa
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $required_fields = ['nis', 'nama', 'jenis_kelamin'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            jsonResponse([
                'success' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
            ], 400);
        }
        
        // Check if NIS already exists
        $existing = getOne("SELECT id FROM siswa WHERE nis = ?", [$input['nis']]);
        if ($existing) {
            jsonResponse([
                'success' => false,
                'message' => 'NIS already exists'
            ], 400);
        }
        
        // Insert data
        try {
            $id = insert('siswa', [
                'nis' => $input['nis'],
                'nama' => $input['nama'],
                'kelas' => $input['kelas'] ?? null,
                'jenis_kelamin' => $input['jenis_kelamin'],
                'alamat' => $input['alamat'] ?? null,
                'no_hp' => $input['no_hp'] ?? null
            ]);
            
            $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Siswa created successfully',
                'data' => $siswa
            ], 201);
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to create siswa: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    case 'PUT':
        // Update siswa
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if siswa exists
        $existing = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Siswa not found'], 404);
        }
        
        // Check if NIS already exists (exclude current record)
        if (isset($input['nis']) && $input['nis'] !== $existing['nis']) {
            $nis_exists = getOne("SELECT id FROM siswa WHERE nis = ? AND id != ?", [$input['nis'], $id]);
            if ($nis_exists) {
                jsonResponse([
                    'success' => false,
                    'message' => 'NIS already exists'
                ], 400);
            }
        }
        
        // Prepare update data
        $update_data = [];
        $allowed_fields = ['nis', 'nama', 'kelas', 'jenis_kelamin', 'alamat', 'no_hp'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $update_data[$field] = $input[$field];
            }
        }
        
        if (empty($update_data)) {
            jsonResponse(['success' => false, 'message' => 'No data to update'], 400);
        }
        
        try {
            $affected = update('siswa', $update_data, 'id = ?', [$id]);
            
            if ($affected >= 0) { // Changed from > 0 to >= 0 to handle no changes
                $siswa = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
                jsonResponse([
                    'success' => true,
                    'message' => 'Siswa updated successfully',
                    'data' => $siswa
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Failed to update'], 500);
            }
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to update siswa: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    case 'DELETE':
        // Delete siswa
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }
        
        // Check if siswa exists
        $existing = getOne("SELECT * FROM siswa WHERE id = ?", [$id]);
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Siswa not found'], 404);
        }
        
        try {
            $affected = delete('siswa', 'id = ?', [$id]);
            
            if ($affected > 0) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Siswa deleted successfully'
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Failed to delete siswa'], 500);
            }
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to delete siswa: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>