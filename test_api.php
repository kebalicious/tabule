<?php
// Simple test script to check API functionality
session_start();

// Simulate session data (you need to login first to get real session)
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test API call
$tableName = $_GET['table'] ?? 'test_table';
$url = "view_table_data.php?table=" . urlencode($tableName);

echo "<h2>Testing API: $url</h2>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);

$response = file_get_contents($url, false, $context);

echo "<h3>Response:</h3>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to decode JSON
$data = json_decode($response, true);
if ($data) {
    echo "<h3>Decoded JSON:</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<h3>JSON Decode Error:</h3>";
    echo json_last_error_msg();
}
?>
