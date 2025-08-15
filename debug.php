<?php
// Simple debug file to test PHP functionality
echo "<h1>PHP Debug Test</h1>";

// Test 1: Basic PHP functionality
echo "<h2>Test 1: Basic PHP</h2>";
echo "<p>✅ PHP is working!</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Test 2: Check if PDO is available
echo "<h2>Test 2: PDO Extension</h2>";
if (extension_loaded('pdo')) {
    echo "<p>✅ PDO extension is loaded</p>";
    echo "<p>Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
} else {
    echo "<p>❌ PDO extension is NOT loaded</p>";
}

// Test 3: Check if PDO MySQL is available
echo "<h2>Test 3: PDO MySQL Extension</h2>";
if (extension_loaded('pdo_mysql')) {
    echo "<p>✅ PDO MySQL extension is loaded</p>";
} else {
    echo "<p>❌ PDO MySQL extension is NOT loaded</p>";
}

// Test 4: Check session functionality
echo "<h2>Test 4: Session Functionality</h2>";
try {
    session_start();
    echo "<p>✅ Sessions are working</p>";
    $_SESSION['test'] = 'test_value';
    echo "<p>Session test value: " . $_SESSION['test'] . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . $e->getMessage() . "</p>";
}

// Test 5: Check file permissions
echo "<h2>Test 5: File Permissions</h2>";
$current_file = __FILE__;
if (is_readable($current_file)) {
    echo "<p>✅ Current file is readable</p>";
} else {
    echo "<p>❌ Current file is NOT readable</p>";
}

if (is_writable(dirname($current_file))) {
    echo "<p>✅ Directory is writable</p>";
} else {
    echo "<p>❌ Directory is NOT writable</p>";
}

// Test 6: Check server information
echo "<h2>Test 6: Server Information</h2>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";

// Test 7: Check for common PHP extensions
echo "<h2>Test 7: Common Extensions</h2>";
$extensions = ['json', 'mbstring', 'openssl', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ $ext extension is loaded</p>";
    } else {
        echo "<p>❌ $ext extension is NOT loaded</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li><a href='test_connection.php'>Test Database Connection</a></li>";
echo "<li><a href='index.php'>Go to Database Manager</a></li>";
echo "</ul>";
?>
