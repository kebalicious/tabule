<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['table']) || !isset($input['where'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing table name or where condition']);
    exit();
}

$tableName = $input['table'];
$whereCondition = $input['where'];
$current_database = $_SESSION['db_database'] ?? '';

if (empty($current_database) || empty($tableName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Database or table not specified']);
    exit();
}

// Recreate PDO connection from session data
try {
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
    
    // Switch to the selected database
    $pdo->exec("USE `$current_database`");
    
    // Build DELETE query
    $whereClause = [];
    $values = [];
    
    foreach ($whereCondition as $column => $value) {
        if ($value === null) {
            $whereClause[] = "`$column` IS NULL";
        } else {
            $whereClause[] = "`$column` = ?";
            $values[] = $value;
        }
    }
    
    $sql = "DELETE FROM `$tableName` WHERE " . implode(' AND ', $whereClause);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Row deleted successfully',
        'affected_rows' => $affectedRows
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
