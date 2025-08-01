<?php
// Minimal Master Data Index for Troubleshooting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Master Data Test</title></head><body>";
echo "<h1>Master Data Module Test</h1>";

// Step 1: Test current directory
echo "<h3>Step 1: Directory Check</h3>";
echo "Current directory: " . getcwd() . "<br>";
echo "Files in current directory:<br>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- $file<br>";
    }
}

// Step 2: Test config file
echo "<h3>Step 2: Config Test</h3>";
$config_path = '../../core/config/database.php';
if (file_exists($config_path)) {
    echo "✅ Config file exists<br>";
    try {
        require_once $config_path;
        echo "✅ Config loaded successfully<br>";
        echo "Base URL: $base_url<br>";
    } catch (Exception $e) {
        echo "❌ Config error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Config file not found at: $config_path<br>";
    
    // Try alternative paths
    $alt_paths = [
        '../core/config/database.php',
        'core/config/database.php',
        '../../SMS/core/config/database.php'
    ];
    
    foreach ($alt_paths as $alt_path) {
        if (file_exists($alt_path)) {
            echo "Found config at: $alt_path<br>";
            break;
        }
    }
}

// Step 3: Test authentication
echo "<h3>Step 3: Auth Test</h3>";
if (function_exists('isLoggedIn')) {
    if (isLoggedIn()) {
        echo "✅ User logged in<br>";
    } else {
        echo "❌ User not logged in - redirecting to login...<br>";
        echo "<a href='../../core/login.php'>Login Here</a><br>";
    }
} else {
    echo "❌ Auth functions not available<br>";
}

// Step 4: Static content test
echo "<h3>Step 4: Static Content Test</h3>";
echo "
<div style='background: #e3f2fd; padding: 20px; margin: 20px 0; border-radius: 5px;'>
    <h4>Master Data Dashboard</h4>
    <p>This is a test version to check if basic HTML loads.</p>
    
    <div style='display: flex; gap: 20px; margin: 20px 0;'>
        <div style='background: #fff; padding: 15px; border-radius: 5px; flex: 1;'>
            <h5>📊 Total Siswa</h5>
            <h2 style='color: #2196f3;'>Loading...</h2>
        </div>
        <div style='background: #fff; padding: 15px; border-radius: 5px; flex: 1;'>
            <h5>👨‍🏫 Total Guru</h5>
            <h2 style='color: #4caf50;'>Loading...</h2>
        </div>
    </div>
    
    <div style='margin: 20px 0;'>
        <a href='siswa/' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>
            📚 Kelola Siswa
        </a>
        <a href='../../core/api/' target='_blank' style='background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
            🔧 Test API
        </a>
    </div>
</div>
";

// Step 5: JavaScript test
echo "<h3>Step 5: JavaScript Test</h3>";
echo "
<script>
console.log('JavaScript is working');
alert('If you see this alert, JavaScript is working!');

// Test API call
fetch('../../core/api/siswa')
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            document.body.innerHTML += '<div style=\"background: #c8e6c9; padding: 10px; margin: 10px; border-radius: 5px;\">✅ API Test Success: Found ' + data.pagination.total + ' siswa</div>';
        } else {
            document.body.innerHTML += '<div style=\"background: #ffcdd2; padding: 10px; margin: 10px; border-radius: 5px;\">❌ API Test Failed</div>';
        }
    })
    .catch(error => {
        console.error('API Error:', error);
        document.body.innerHTML += '<div style=\"background: #ffcdd2; padding: 10px; margin: 10px; border-radius: 5px;\">❌ API Error: ' + error + '</div>';
    });
</script>
";

echo "</body></html>";
?>