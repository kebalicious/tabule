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

// Get current database
$current_database = $_SESSION['db_database'] ?? '';

if (empty($current_database)) {
    echo json_encode(['success' => false, 'error' => 'No database selected']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['name'] ?? '';
$columns = $input['columns'] ?? [];

if (empty($tableName)) {
    echo json_encode(['success' => false, 'error' => 'Table name is required']);
    exit();
}

if (empty($columns)) {
    echo json_encode(['success' => false, 'error' => 'At least one column is required']);
    exit();
}

// Validate table name
if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    echo json_encode(['success' => false, 'error' => 'Invalid table name. Only letters, numbers, and underscores are allowed.']);
    exit();
}

try {
    // Switch to the selected database
    $pdo->exec("USE `$current_database`");
    
    // Build CREATE TABLE query
    $columnDefinitions = [];
    $primaryKeys = [];
    
    foreach ($columns as $column) {
        $name = $column['name'] ?? '';
        $type = $column['type'] ?? '';
        $primary = $column['primary'] ?? false;
        
        if (empty($name) || empty($type)) {
            continue;
        }
        
        // Validate column name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            echo json_encode(['success' => false, 'error' => "Invalid column name '$name'. Only letters, numbers, and underscores are allowed."]);
            exit();
        }
        
        $definition = "`$name` $type";
        
        // Add NOT NULL for primary keys
        if ($primary) {
            $definition .= " NOT NULL";
            $primaryKeys[] = $name;
        }
        
        $columnDefinitions[] = $definition;
    }
    
    if (empty($columnDefinitions)) {
        echo json_encode(['success' => false, 'error' => 'No valid columns provided']);
        exit();
    }
    
    // Add PRIMARY KEY constraint if any primary keys are specified
    if (!empty($primaryKeys)) {
        $columnDefinitions[] = "PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
    }
    
    $createTableSQL = "CREATE TABLE `$tableName` (\n  " . implode(",\n  ", $columnDefinitions) . "\n)";
    
    // Execute the CREATE TABLE query
    $stmt = $pdo->prepare($createTableSQL);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => "Table '$tableName' created successfully in database '$current_database'"
    ]);
    
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
