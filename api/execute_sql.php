<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Recreate PDO connection from session data
$host = $_SESSION['db_host'];
$port = $_SESSION['db_port'];
$username = $_SESSION['db_username'];
$password = $_SESSION['db_password'];
$database = $_SESSION['db_database'] ?? '';

// Build DSN
$dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
if (!empty($database)) {
    $dsn .= ";dbname=$database";
}

// Create PDO connection
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'No query provided']);
    exit();
}

try {
    // Execute the query
    $stmt = $pdo->query($query);
    
    // Check if it's a SELECT query (returns rows)
    if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'rowCount' => count($rows),
                'columnCount' => $stmt->columnCount()
            ]
        ]);
    } else {
        // It's an INSERT, UPDATE, DELETE, etc.
        $rowCount = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => 'Query executed successfully',
                'affectedRows' => $rowCount
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
