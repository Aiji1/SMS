<?php
/**
 * SMS Core - API Guru
 * File: core/api/guru.php
 */

$id = $endpoint_parts[1] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Get specific guru
            $guru = getOne("SELECT * FROM guru WHERE id = ?", [$id]);
            if ($guru) {
                jsonResponse(['success' => true, 'data' => $guru]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Guru not found'], 404);
            }
        } else {
            // Get all guru with pagination and search
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $mapel = $_GET['mapel'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $where_conditions = [];
            $params = [];
            
            if ($search) {
                $where_conditions[] = "(nama LIKE ? OR nip LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($mapel) {
                $where_conditions[] = "mapel = ?";
                $params[] = $mapel;
            }
            
            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
            
            // Get total count
            $total_query = "SELECT COUNT(*) as total FROM guru $where_clause";
            $total_result = getOne($total_query, $params);
            $total = $total_result ? $total_result['total'] : 0;
            
            // Get data
            $data_query = "SELECT * FROM guru $where_clause ORDER BY nama ASC LIMIT $limit OFFSET $offset";
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
        // Create new guru
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $required_fields = ['nama', 'jenis_kelamin'];
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
        
        // Check if NIP already exists (if provided)
        if (!empty($input['nip'])) {
            $existing = getOne("SELECT id FROM guru WHERE nip = ?", [$input['nip']]);
            if ($existing) {
                jsonResponse([
                    'success' => false,
                    'message' => 'NIP already exists'
                ], 400);
            }
        }
        
        // Insert data
        try {
            $id = insert('guru', [
                'nip' => $input['nip'] ?? null,
                'nama' => $input['nama'],
                'mapel' => $input['mapel'] ?? null,
                'jenis_kelamin' => $input['jenis_kelamin'],
                'alamat' => $input['alamat'] ?? null,
                'no_hp' => $input['no_hp'] ?? null
            ]);
            
            $guru = getOne("SELECT * FROM guru WHERE id = ?", [$id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Guru created successfully',
                'data' => $guru
            ], 201);
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to create guru: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    case 'PUT':
        // Update guru
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if guru exists
        $existing = getOne("SELECT * FROM guru WHERE id = ?", [$id]);
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Guru not found'], 404);
        }
        
        // Check if NIP already exists (exclude current record)
        if (isset($input['nip']) && $input['nip'] !== $existing['nip']) {
            $nip_exists = getOne("SELECT id FROM guru WHERE nip = ? AND id != ?", [$input['nip'], $id]);
            if ($nip_exists) {
                jsonResponse([
                    'success' => false,
                    'message' => 'NIP already exists'
                ], 400);
            }
        }
        
        // Prepare update data
        $update_data = [];
        $allowed_fields = ['nip', 'nama', 'mapel', 'jenis_kelamin', 'alamat', 'no_hp'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $update_data[$field] = $input[$field];
            }
        }
        
        if (empty($update_data)) {
            jsonResponse(['success' => false, 'message' => 'No data to update'], 400);
        }
        
        try {
            $affected = update('guru', $update_data, 'id = ?', [$id]);
            
            if ($affected >= 0) {
                $guru = getOne("SELECT * FROM guru WHERE id = ?", [$id]);
                jsonResponse([
                    'success' => true,
                    'message' => 'Guru updated successfully',
                    'data' => $guru
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Failed to update'], 500);
            }
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to update guru: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    case 'DELETE':
        // Delete guru
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID required'], 400);
        }
        
        // Check if guru exists
        $existing = getOne("SELECT * FROM guru WHERE id = ?", [$id]);
        if (!$existing) {
            jsonResponse(['success' => false, 'message' => 'Guru not found'], 404);
        }
        
        try {
            $affected = delete('guru', 'id = ?', [$id]);
            
            if ($affected > 0) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Guru deleted successfully'
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Failed to delete guru'], 500);
            }
            
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to delete guru: ' . $e->getMessage()
            ], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>