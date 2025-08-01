<?php
// Debug file - untuk cek error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug SMS System</h2>";

// Test 1: Include config
echo "<h3>1. Testing Database Config</h3>";
try {
    require_once 'config/database.php';
    echo "✅ Database config loaded successfully<br>";
    echo "App Name: " . $app_name . "<br>";
    echo "Base URL: " . $base_url . "<br>";
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
}

// Test 2: Test database connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    $test_query = getOne("SELECT COUNT(*) as total FROM siswa");
    echo "✅ Database connection OK<br>";
    echo "Total siswa: " . $test_query['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Test authentication
echo "<h3>3. Testing Authentication</h3>";
if (isLoggedIn()) {
    echo "✅ User is logged in<br>";
    $user = getUser();
    echo "User: " . $user['nama'] . " (" . $user['role'] . ")<br>";
} else {
    echo "❌ User not logged in<br>";
}

// Test 4: Test includes
echo "<h3>4. Testing Includes</h3>";
if (file_exists('includes/header.php')) {
    echo "✅ header.php exists<br>";
} else {
    echo "❌ header.php not found<br>";
}

if (file_exists('includes/sidebar.php')) {
    echo "✅ sidebar.php exists<br>";
} else {
    echo "❌ sidebar.php not found<br>";
}

if (file_exists('includes/footer.php')) {
    echo "✅ footer.php exists<br>";
} else {
    echo "❌ footer.php not found<br>";
}

// Test 5: Test file permissions
echo "<h3>5. File Structure</h3>";
echo "Current directory: " . getcwd() . "<br>";
echo "Files in current directory:<br>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- " . $file . "<br>";
    }
}

echo "<h3>6. PHP Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
?>

<hr>
<p><a href="index.php">← Back to Dashboard</a></p>