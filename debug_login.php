<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Debug</h1>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Form Submitted</h2>";
    
    $host = trim($_POST['host'] ?? 'localhost');
    $port = trim($_POST['port'] ?? '3306');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $database = trim($_POST['database'] ?? '');

    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Port:</strong> $port</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> " . (empty($password) ? 'Empty' : 'Set') . "</p>";
    echo "<p><strong>Database:</strong> " . (empty($database) ? 'Empty' : $database) . "</p>";

    // Basic validation
    if (empty($username)) {
        echo "<p style='color: red;'>❌ Username is required</p>";
    } elseif (empty($host)) {
        echo "<p style='color: red;'>❌ Host is required</p>";
    } else {
        echo "<h3>Attempting Database Connection...</h3>";
        
        try {
            // Build DSN
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            if (!empty($database)) {
                $dsn .= ";dbname=$database";
            }
            
            echo "<p><strong>DSN:</strong> $dsn</p>";
            
            // Create PDO connection with options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            echo "<p style='color: green;'>✅ PDO Connection created successfully</p>";
            
            // Test connection with a simple query
            $stmt = $pdo->query('SELECT 1 as test');
            $result = $stmt->fetch();
            
            echo "<p><strong>Test Query Result:</strong> " . print_r($result, true) . "</p>";
            
            if ($result && $result['test'] == 1) {
                echo "<p style='color: green;'>✅ Connection test successful!</p>";
                
                // Store in session
                $_SESSION['db_connected'] = true;
                $_SESSION['db_host'] = $host;
                $_SESSION['db_port'] = $port;
                $_SESSION['db_username'] = $username;
                $_SESSION['db_password'] = $password;
                $_SESSION['db_database'] = $database;
                $_SESSION['pdo'] = $pdo;
                
                echo "<p style='color: green;'>✅ Session data stored</p>";
                echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
                echo "<p><strong>Session Data:</strong> " . print_r($_SESSION, true) . "</p>";
                
                echo "<h3>Redirecting to Dashboard...</h3>";
                echo "<p><a href='dashboard.php'>Click here if not redirected automatically</a></p>";
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
                
            } else {
                echo "<p style='color: red;'>❌ Connection test failed</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ PDO Error: " . $e->getMessage() . "</p>";
            echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ General Error: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<h2>No Form Submitted</h2>";
    echo "<p>This page is for debugging login process.</p>";
}

echo "<hr>";
echo "<h2>Current Session Info:</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Data:</strong> " . print_r($_SESSION, true) . "</p>";

echo "<hr>";
echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>Go to Login Page</a></li>";
echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
echo "<li><a href='debug.php'>Run Debug Test</a></li>";
echo "</ul>";
?>
