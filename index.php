<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is already logged in
if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? 'localhost');
    $port = trim($_POST['port'] ?? '3306');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $database = trim($_POST['database'] ?? '');

    // Basic validation
    if (empty($username)) {
        $error_message = 'Username is required';
    } elseif (empty($host)) {
        $error_message = 'Host is required';
    } else {
        try {
            // Build DSN
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            if (!empty($database)) {
                $dsn .= ";dbname=$database";
            }
            
            // Create PDO connection with options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            
            // Test connection with a simple query
            $stmt = $pdo->query('SELECT 1 as test');
            $result = $stmt->fetch();
            
            if ($result && $result['test'] == 1) {
                // Connection successful
                $_SESSION['db_connected'] = true;
                $_SESSION['db_host'] = $host;
                $_SESSION['db_port'] = $port;
                $_SESSION['db_username'] = $username;
                $_SESSION['db_password'] = $password;
                $_SESSION['db_database'] = $database;
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Connection test failed';
            }
            
        } catch (PDOException $e) {
            $error_code = $e->getCode();
            
            switch ($error_code) {
                case 2002:
                    $error_message = 'Cannot connect to MySQL server. Please check if the server is running and the host/port is correct.';
                    break;
                case 1045:
                    $error_message = 'Access denied. Please check your username and password.';
                    break;
                case 1049:
                    $error_message = 'Database does not exist. Please check the database name or leave it empty to connect to server only.';
                    break;
                case 2006:
                    $error_message = 'MySQL server has gone away. Please try again.';
                    break;
                default:
                    $error_message = 'Connection failed: ' . $e->getMessage();
            }
        } catch (Exception $e) {
            $error_message = 'Unexpected error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Global font settings */
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        html {
            font-size: 14px; /* Base font size */
        }
        
        body {
            font-size: 1rem; /* 1em = 14px */
            font-weight: 400;
            line-height: 1.5;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .connection-info {
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid rgba(0, 123, 255, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="login-container p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-database-fill text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-0">Database Manager</h2>
                        <p class="text-muted">Connect to your MySQL/MariaDB database</p>
                    </div>

                    <!-- Connection Info -->
                    <div class="connection-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Connection Tips:</h6>
                        <ul class="mb-0 small">
                            <li><strong>Local:</strong> Host: localhost, Port: 3306</li>
                            <li><strong>XAMPP:</strong> Host: localhost, Port: 3306</li>
                            <li><strong>Laragon:</strong> Host: localhost, Port: 3306</li>
                            <li><strong>Remote:</strong> Use server IP and port</li>
                        </ul>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="loginForm">
                        <div class="mb-3">
                            <label for="host" class="form-label">
                                <i class="bi bi-hdd-network me-2"></i>Host
                            </label>
                            <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                            <div class="form-text">Usually 'localhost' for local servers</div>
                        </div>

                        <div class="mb-3">
                            <label for="port" class="form-label">
                                <i class="bi bi-arrow-left-right me-2"></i>Port
                            </label>
                            <input type="number" class="form-control" id="port" name="port" value="3306" required>
                            <div class="form-text">Default MySQL port is 3306</div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person-fill me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="root" required>
                            <div class="form-text">Common: 'root' for local development</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave empty if no password">
                            <div class="form-text">Leave empty if no password is set</div>
                        </div>

                        <div class="mb-4">
                            <label for="database" class="form-label">
                                <i class="bi bi-database me-2"></i>Database (Optional)
                            </label>
                            <input type="text" class="form-control" id="database" name="database" placeholder="Leave empty to connect to server only">
                            <div class="form-text">Optional: Connect to specific database</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2" id="connectBtn">
                            <i class="bi bi-plug me-2"></i>Connect to Database
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Supports MySQL and MariaDB connections
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add loading state to form
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('connectBtn');
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Connecting...';
            btn.disabled = true;
        });

        // Auto-fill common values
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're on localhost
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                document.getElementById('host').value = 'localhost';
                document.getElementById('port').value = '3306';
            }
        });
    </script>
</body>
</html>
