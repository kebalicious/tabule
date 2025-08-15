<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    header('Location: index.php');
    exit();
}

// Get current database and table
$current_database = $_SESSION['db_database'] ?? '';
$tableName = $_GET['table'] ?? '';

if (empty($current_database) || empty($tableName)) {
    header('Location: dashboard.php');
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

try {
    // Switch to the selected database
    $pdo->exec("USE `$current_database`");
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE `$tableName`");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get table data (limit to 100 rows for performance)
    $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 100");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get row count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
    $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Table - <?php echo htmlspecialchars($tableName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .structure-table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .data-table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .btn-back {
            background: #6c757d;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="dashboard.php" class="btn btn-back text-white">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                    <div class="text-center">
                        <h3><i class="bi bi-table me-2"></i><?php echo htmlspecialchars($tableName); ?></h3>
                        <small class="text-muted">Database: <?php echo htmlspecialchars($current_database); ?></small>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="addRow()">
                            <i class="bi bi-plus-circle me-2"></i>Add Row
                        </button>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php else: ?>
                    <!-- Table Info -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-table text-primary" style="font-size: 2rem;"></i>
                                    <h5 class="card-title mt-2"><?php echo count($structure); ?></h5>
                                    <p class="card-text">Columns</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-list-ul text-success" style="font-size: 2rem;"></i>
                                    <h5 class="card-title mt-2"><?php echo $rowCount; ?></h5>
                                    <p class="card-text">Total Rows</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-eye text-info" style="font-size: 2rem;"></i>
                                    <h5 class="card-title mt-2"><?php echo count($data); ?></h5>
                                    <p class="card-text">Showing</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="bi bi-gear text-warning" style="font-size: 2rem;"></i>
                                    <h5 class="card-title mt-2">Actions</h5>
                                    <p class="card-text">Manage</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="tableTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab">
                                <i class="bi bi-list-ul me-2"></i>Data
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="structure-tab" data-bs-toggle="tab" data-bs-target="#structure" type="button" role="tab">
                                <i class="bi bi-gear me-2"></i>Structure
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sql-tab" data-bs-toggle="tab" data-bs-target="#sql" type="button" role="tab">
                                <i class="bi bi-code-slash me-2"></i>SQL
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="tableTabsContent">
                        <!-- Data Tab -->
                        <div class="tab-pane fade show active" id="data" role="tabpanel">
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover data-table mb-0">
                                        <thead>
                                            <tr>
                                                <?php if (!empty($data)): ?>
                                                    <?php foreach (array_keys($data[0]) as $column): ?>
                                                        <th><?php echo htmlspecialchars($column); ?></th>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?php echo htmlspecialchars($value !== null ? $value : 'NULL'); ?></td>
                                                    <?php endforeach; ?>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editRow(this)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRow(this)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Structure Tab -->
                        <div class="tab-pane fade" id="structure" role="tabpanel">
                            <div class="table-container">
                                <table class="table table-striped structure-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Type</th>
                                            <th>Null</th>
                                            <th>Key</th>
                                            <th>Default</th>
                                            <th>Extra</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($structure as $column): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($column['Field']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                                <td>
                                                    <?php if ($column['Null'] === 'YES'): ?>
                                                        <span class="badge bg-success">YES</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">NO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($column['Key'] === 'PRI'): ?>
                                                        <span class="badge bg-primary">PRIMARY</span>
                                                    <?php elseif ($column['Key'] === 'UNI'): ?>
                                                        <span class="badge bg-info">UNIQUE</span>
                                                    <?php elseif ($column['Key'] === 'MUL'): ?>
                                                        <span class="badge bg-warning">INDEX</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                                <td><?php echo htmlspecialchars($column['Extra']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SQL Tab -->
                        <div class="tab-pane fade" id="sql" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Sample Queries:</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Select all data:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="SELECT * FROM `<?php echo htmlspecialchars($tableName); ?>`" readonly>
                                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this.previousElementSibling)">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Count rows:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="SELECT COUNT(*) FROM `<?php echo htmlspecialchars($tableName); ?>`" readonly>
                                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this.previousElementSibling)">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Show table structure:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="DESCRIBE `<?php echo htmlspecialchars($tableName); ?>`" readonly>
                                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this.previousElementSibling)">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRow() {
            // Implementation for adding new row
            alert('Add row functionality will be implemented here');
        }

        function editRow(button) {
            // Implementation for editing row
            alert('Edit row functionality will be implemented here');
        }

        function deleteRow(button) {
            if (confirm('Are you sure you want to delete this row?')) {
                // Implementation for deleting row
                alert('Delete row functionality will be implemented here');
            }
        }

        function copyToClipboard(input) {
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Show feedback
            const button = input.nextElementSibling;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 1000);
        }
    </script>
</body>
</html>
