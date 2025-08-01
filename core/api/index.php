<?php
/**
 * SMS Core - API Router (CORS Fixed)
 * File: core/api/index.php
 */

// Set headers FIRST before any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Disable any HTML output
ob_start();

try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit();
}

// Get request info
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Find API endpoint
$endpoint = '';
for ($i = 0; $i < count($path_parts); $i++) {
    if ($path_parts[$i] === 'api' && isset($path_parts[$i + 1])) {
        $endpoint = $path_parts[$i + 1];
        $endpoint_parts = array_slice($path_parts, $i + 1);
        break;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// Authentication check for API
function requireApiAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }
}

function jsonResponse($data, $status = 200) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Route requests
switch ($endpoint) {
    case 'siswa':
        requireApiAuth();
        include 'siswa.php';
        break;
        
    case 'guru':
        requireApiAuth();
        include 'guru.php';
        break;
        
    case 'test':
        // Test endpoint
        jsonResponse([
            'success' => true,
            'message' => 'API is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'endpoint' => $endpoint
        ]);
        break;
        
    default:
        // API Documentation
        if (empty($endpoint)) {
            jsonResponse([
                'success' => true,
                'app' => 'SMS Core API',
                'version' => '1.0',
                'status' => 'OK',
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'test' => 'GET /test - Test API connection',
                    'siswa' => [
                        'GET /siswa' => 'Get all siswa with pagination',
                        'GET /siswa/{id}' => 'Get siswa by ID',
                        'POST /siswa' => 'Create new siswa',
                        'PUT /siswa/{id}' => 'Update siswa',
                        'DELETE /siswa/{id}' => 'Delete siswa'
                    ],
                    'guru' => [
                        'GET /guru' => 'Get all guru',
                        'GET /guru/{id}' => 'Get guru by ID',
                        'POST /guru' => 'Create new guru',
                        'PUT /guru/{id}' => 'Update guru',
                        'DELETE /guru/{id}' => 'Delete guru'
                    ]
                ],
                'usage' => [
                    'base_url' => $base_url . '/core/api/',
                    'authentication' => 'Session-based (login required)',
                    'content_type' => 'application/json'
                ],
                'examples' => [
                    'test_api' => $base_url . '/core/api/test',
                    'get_siswa' => $base_url . '/core/api/siswa',
                    'get_siswa_with_pagination' => $base_url . '/core/api/siswa?page=1&limit=10',
                    'search_siswa' => $base_url . '/core/api/siswa?search=ahmad',
                    'filter_by_kelas' => $base_url . '/core/api/siswa?kelas=X-A'
                ]
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Endpoint not found', 'requested' => $endpoint], 404);
        }
}
?>