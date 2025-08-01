<?php
// Debug Master Data Module
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Master Data Module</h2>";
echo "<p>Current Directory: " . getcwd() . "</p>";

// Test 1: Check file exists
echo "<h3>1. File Structure Check</h3>";
$files_to_check = [
    '../../core/config/database.php',
    '../../core/includes/header.php',
    '../../core/includes/sidebar.php', 
    '../../core/includes/footer.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT FOUND<br>";
    }
}

// Test 2: Try including config
echo "<h3>2. Database Config Test</h3>";
try {
    require_once '../../core/config/database.php';
    echo "✅ Database config loaded<br>";
    echo "Base URL: $base_url<br>";
    echo "App Name: $app_name<br>";
} catch (Exception $e) {
    echo "❌ Error loading database config: " . $e->getMessage() . "<br>";
}

// Test 3: Test authentication
echo "<h3>3. Authentication Test</h3>";
if (function_exists('isLoggedIn')) {
    if (isLoggedIn()) {
        echo "✅ User is logged in<br>";
        $user = getUser();
        echo "User: " . $user['nama'] . " (" . $user['role'] . ")<br>";
    } else {
        echo "❌ User not logged in<br>";
    }
} else {
    echo "❌ isLoggedIn function not found<br>";
}

// Test 4: Test database functions
echo "<h3>4. Database Functions Test</h3>";
if (function_exists('getOne')) {
    try {
        $test = getOne("SELECT COUNT(*) as total FROM siswa");
        echo "✅ Database query works<br>";
        echo "Total siswa: " . $test['total'] . "<br>";
    } catch (Exception $e) {
        echo "❌ Database query error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getOne function not found<br>";
}

// Test 5: Test API access
echo "<h3>5. API Test</h3>";
$api_url = "../../core/api/siswa";
echo "API URL: <a href='$api_url' target='_blank'>$api_url</a><br>";

// Test 6: Manual header include
echo "<h3>6. Manual Header Include Test</h3>";
try {
    ob_start();
    $page_title = "Debug Test";
    include '../../core/includes/header.php';
    $header_output = ob_get_contents();
    ob_end_clean();
    
    if (strlen($header_output) > 100) {
        echo "✅ Header loads successfully (" . strlen($header_output) . " bytes)<br>";
    } else {
        echo "❌ Header too short or empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Header include error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Master Data</a></p>";
echo "<p><a href='../../core/index.php'>← Back to Dashboard</a></p>";
?>