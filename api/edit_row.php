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

if (!$input || !isset($input['table']) || !isset($input['data']) || !isset($input['where'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing table name, data, or where condition']);
    exit();
}

$tableName = $input['table'];
$rowData = $input['data'];
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
    
    // Build UPDATE query
    $setClause = [];
    $values = [];
    
    foreach ($rowData as $column => $value) {
        $setClause[] = "`$column` = ?";
        $values[] = $value;
    }
    
    $whereClause = [];
    foreach ($whereCondition as $column => $value) {
        if ($value === null) {
            $whereClause[] = "`$column` IS NULL";
        } else {
            $whereClause[] = "`$column` = ?";
            $values[] = $value;
        }
    }
    
    $sql = "UPDATE `$tableName` SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
    
    // Debug logging
    error_log("SQL Query: " . $sql);
    error_log("Values: " . print_r($values, true));
    error_log("Row Data: " . print_r($rowData, true));
    error_log("Where Condition: " . print_r($whereCondition, true));
    
    // First, let's check if the row exists
    $checkSql = "SELECT COUNT(*) as count FROM `$tableName` WHERE " . implode(' AND ', $whereClause);
    $checkStmt = $pdo->prepare($checkSql);
    $checkValues = [];
    foreach ($whereCondition as $column => $value) {
        if ($value !== null) {
            $checkValues[] = $value;
        }
    }
    $checkStmt->execute($checkValues);
    $rowCount = $checkStmt->fetch()['count'];
    error_log("Rows found with WHERE condition: " . $rowCount);
    
    // Also log the actual data in the table
    $sampleSql = "SELECT * FROM `$tableName` LIMIT 5";
    $sampleStmt = $pdo->query($sampleSql);
    $sampleData = $sampleStmt->fetchAll();
    error_log("Sample data from table: " . print_r($sampleData, true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    $affectedRows = $stmt->rowCount();
    error_log("Affected Rows: " . $affectedRows);
    
    echo json_encode([
        'success' => true,
        'message' => 'Row updated successfully',
        'affected_rows' => $affectedRows,
        'debug' => [
            'sql' => $sql,
            'values' => $values,
            'row_data' => $rowData,
            'where_condition' => $whereCondition,
            'rows_found' => $rowCount,
            'sample_data' => $sampleData
        ]
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
