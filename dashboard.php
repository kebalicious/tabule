<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    header('Location: index.php');
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
    
} catch (PDOException $e) {
    // If connection fails, redirect back to login
    session_destroy();
    header('Location: index.php?error=' . urlencode('Connection lost: ' . $e->getMessage()));
    exit();
}

// Get current database
$current_database = $_SESSION['db_database'] ?? '';

// Get databases list
$databases = [];
try {
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

// Get tables if database is selected
$tables = [];
if (!empty($current_database)) {
    try {
        $pdo->exec("USE `$current_database`");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $error_message = $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - Dashboard</title>
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
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            background: #F5F5F5;
            height: 100vh;
            width: 350px;
            color: #333;
            overflow-y: auto;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            border-right: 1px solid #ddd;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .main-content {
            background: #F5F5F5;
            min-height: 100vh;
            margin-left: 350px;
            transition: margin-left 0.3s ease;
            width: calc(100vw - 350px);
            overflow-x: hidden;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .main-content.sidebar-hidden {
            margin-left: 0;
            width: 100vw;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                height: 100vh;
                width: 100%;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .sidebar-toggle-mobile {
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1001;
                background: #2c3e50;
                color: white;
                border: none;
                border-radius: 5px;
                padding: 8px 12px;
            }
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        .container-fluid {
            padding: 0;
            max-width: 100vw;
            overflow-x: hidden;
        }
        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        /* Main application tabs (Notebook, Dashboard, Activate) */
        .app-tabs {
            display: flex;
            align-items: center;
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0 20px;
            height: 50px;
        }
        
        .app-tab {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .app-tab.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        
        .app-tab:hover {
            color: #007bff;
        }
        
        /* Action buttons container */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            border-radius: 4px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .action-btn.primary {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .action-btn.primary:hover {
            background: #0056b3;
        }
        
        /* SQL query display */
        .sql-query-container {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
        }
        
        .sql-query {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            color: #495057;
        }
        
        .sql-query select {
            border: none;
            background: transparent;
            color: #007bff;
            font-weight: 500;
            cursor: pointer;
        }
        
        /* Table tabs navigation */
        .table-tabs-navigation {
            display: flex;
            align-items: center;
            background: #F5F5F5;
            border-bottom: 1px solid #dee2e6;
            height: 40px;
        }
        
        .table-tabs-scroll {
            display: flex;
            align-items: center;
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .table-tabs-wrapper {
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
            min-width: 100%;
        }
        
        .table-tabs {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            border: none;
            flex-wrap: nowrap;
            min-width: 100%;
        }
        
        .table-tabs .tab-item {
            flex-shrink: 0;
            margin-right: 2px;
        }
        
        .table-tabs .tab-link {
            border: none;
            border-radius: 4px 4px 0 0;
            background: #e9ecef;
            color: #6c757d;
            white-space: nowrap;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            padding: 6px 12px;
            font-size: 0.875rem;
            height: 32px;
        }
        
        .table-tabs .tab-link.active {
            background: #007bff;
            color: white;
        }
        
        .table-tabs .tab-link .close-tab {
            margin-left: 6px;
            opacity: 0.7;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            line-height: 1;
        }
        
        .table-tabs .tab-link .close-tab:hover {
            opacity: 1;
        }
        
        /* Table tabs navigation arrows */
        .table-nav-arrow {
            background: #e9ecef;
            border: none;
            border-radius: 3px;
            padding: 6px 8px;
            cursor: pointer;
            color: #6c757d;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
            height: 28px;
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .table-nav-arrow:hover {
            background: #dee2e6;
            color: #495057;
        }
        
        .table-nav-arrow:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .table-container {
            background: white;
            width: 100%;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .table-responsive {
            flex: 1; /* Take remaining space */
            overflow-y: auto;
            overflow-x: hidden; /* Hide horizontal scroll */
            width: 100%;
        }
        .table th {
            background: #F5F5F5;
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 8px 4px; /* Reduce padding */
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
            table-layout: auto; /* Auto layout to fit content */
        }
        
        .table th,
        .table td {
            white-space: normal; /* Allow text to wrap */
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
            padding: 8px 4px; /* Reduce padding */
            font-size: 0.875rem; /* Smaller font */
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
        .database-selector {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .tables-section {
            margin-top: 20px;
        }
        
        /* Edit sidebar styles */
        .edit-sidebar {
            position: fixed;
            right: -400px;
            top: 0;
            height: 100vh;
            width: 400px;
            background-color: #ffffff;
            border-left: 1px solid #dee2e6;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }
        
        .edit-sidebar.show {
            right: 0;
        }
        
        .edit-sidebar-content {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .edit-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .edit-sidebar-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .edit-sidebar-footer {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Adjust main content when edit sidebar is open */
        .edit-sidebar.show ~ .main-content {
            margin-right: 400px;
        }
        
                         .database-selector {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
        }
        
        /* Sidebar navigation items */
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #666;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .sidebar-nav-item:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
        }
        
        .sidebar-nav-item.active {
            background: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }
        
        .sidebar-nav-item i {
            margin-right: 12px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        
        
                 /* Projects section (for tables) */
         .projects-header {
             display: flex;
             align-items: center;
             justify-content: space-between;
             padding: 0 16px;
             margin-bottom: 12px;
             color: #666;
             font-size: 0.75rem;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }
         
         .project-item {
             display: flex;
             align-items: center;
             padding: 8px 16px;
             color: #333;
             text-decoration: none;
             border-radius: 4px;
             margin-bottom: 2px;
             transition: all 0.2s ease;
             font-size: 0.875rem;
         }
         
         .project-item:hover {
             background: rgba(0, 0, 0, 0.1);
         }
         
         .project-item.active {
             background: rgba(0, 123, 255, 0.2);
             color: #007bff;
         }
         
         .project-item i {
             margin-right: 8px;
             font-size: 0.875rem;
             width: 16px;
             text-align: center;
         }
         
         /* Add project button */
         .add-project-btn {
             display: flex;
             align-items: center;
             padding: 8px 16px;
             color: #007bff;
             background: none;
             border: none;
             border-radius: 4px;
             margin: 8px 0;
             transition: all 0.2s ease;
             font-size: 0.875rem;
             cursor: pointer;
         }
         
         .add-project-btn:hover {
             background: rgba(0, 123, 255, 0.1);
         }
         
         .add-project-btn i {
             margin-right: 8px;
             font-size: 0.875rem;
         }
         
         /* User profile */
         .user-profile {
             display: flex;
             align-items: center;
             padding: 12px 16px;
             border-top: 1px solid rgba(0, 0, 0, 0.1);
             margin-top: auto;
         }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 12px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: #666;
        }
         
         /* Table search styling */
         #tableSearch {
             background: rgba(255, 255, 255, 0.8);
             border: 1px solid rgba(0, 0, 0, 0.2);
             color: #333;
             font-size: 0.875rem;
         }
         
         #tableSearch::placeholder {
             color: rgba(0, 0, 0, 0.6);
         }
         
         #tableSearch:focus {
             background: rgba(255, 255, 255, 1);
             border-color: rgba(0, 0, 0, 0.3);
             color: #333;
             box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.1);
         }
         
         .table-item.hidden {
             display: none;
         }
         
         .table-btn {
             transition: all 0.2s ease;
         }
         
         .table-btn:hover {
             background: rgba(0, 0, 0, 0.1) !important;
             transform: translateX(5px);
         }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn sidebar-toggle-mobile d-md-none" onclick="toggleSidebar()" id="mobileSidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Logo -->
            <div class="d-flex align-items-center p-3">
                <div class="d-flex align-items-center">
                    <span class="text-dark">TABULE DATABASE MANAGER</span>
                </div>
                <i class="bi bi-chevron-down text-muted ms-auto"></i>
            </div>            
            
            <!-- Database Selector -->
            <div class="p-3">
                <div class="database-selector">
                    <label class="form-label text-dark mb-2">
                        <i class="bi bi-database me-2"></i>Select Database
                    </label>
                    <select class="form-select form-select-sm" id="databaseSelect" onchange="selectDatabase(this.value)">
                        <option value="">Choose database...</option>
                        <?php foreach ($databases as $db): ?>
                            <option value="<?php echo htmlspecialchars($db); ?>" 
                                    <?php echo ($db === $current_database) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($db); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if (!empty($current_database)): ?>
                        <div class="mt-2">
                            <small class="text-dark">
                                <i class="bi bi-check-circle me-1"></i>
                                Connected to: <strong><?php echo htmlspecialchars($current_database); ?></strong>
                            </small>
                        </div>
                    <?php else: ?>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <button class="btn btn-outline-dark btn-sm w-100" data-bs-toggle="modal" data-bs-target="#createDatabaseModal">
                            <i class="bi bi-plus-circle me-2"></i>Create Database
                        </button>
                    </div>
                </div>
            </div>

                                                  <!-- Tables Section -->
              <?php if (!empty($current_database)): ?>
                  <div class="p-3">
                      <div class="projects-header">
                          <span>Tables (<?php echo count($tables); ?>)</span>
                      </div>
                      
                      <!-- Table Search -->
                      <div class="mb-3">
                          <input type="text" class="form-control form-control-sm" id="tableSearch" 
                                 placeholder="Search tables..." onkeyup="filterTables()">
                      </div>
                      
                      <?php if (empty($tables)): ?>
                          <p class="text-muted small px-3">No tables found</p>
                      <?php else: ?>
                          <div class="table-list" id="tableList">
                              <?php foreach ($tables as $table): ?>
                                  <div class="table-item" data-table-name="<?php echo htmlspecialchars($table); ?>">
                                      <button class="project-item w-100 text-start table-btn" 
                                              onclick="openTable('<?php echo htmlspecialchars($table); ?>')">
                                          <i class="bi bi-table"></i>
                                          <span class="table-name"><?php echo htmlspecialchars($table); ?></span>
                                      </button>
                                  </div>
                              <?php endforeach; ?>
                          </div>
                          
                          <!-- No results message -->
                          <div id="noTablesFound" class="text-muted small px-3" style="display: none;">
                              No tables match your search
                          </div>
                      <?php endif; ?>
                      
                      <button class="add-project-btn w-100" data-bs-toggle="modal" data-bs-target="#createTableModal">
                          <i class="bi bi-plus-circle"></i>Create Table
                      </button>
                  </div>
              <?php endif; ?>
         </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Application Tabs -->
            <div class="app-tabs">
                <button class="app-tab active" onclick="switchAppTab('notebook')">
                    <i class="bi bi-journal-text me-2"></i>Notebook
                </button>
                <button class="app-tab" onclick="switchAppTab('dashboard')">
                    <i class="bi bi-graph-up me-2"></i>Dashboard
                </button>
                <button class="app-tab" onclick="switchAppTab('activate')">
                    <i class="bi bi-play-circle me-2"></i>Activate
                </button>
                
                <div class="action-buttons">
                    <button class="action-btn primary" onclick="runAllQueries()">
                        <i class="bi bi-play-fill me-1"></i>Run All
                    </button>
                    <button class="action-btn" onclick="toggleSQL()">
                        <i class="bi bi-code-slash me-1"></i>Hide SQL
                    </button>
                    <button class="action-btn" onclick="shareQuery()">
                        <i class="bi bi-share me-1"></i>Share
                    </button>
                    <button class="action-btn" onclick="scheduleQuery()">
                        <i class="bi bi-clock me-1"></i>Schedule
                    </button>
                </div>
            </div>
            
            <!-- SQL Query Display -->
            <div class="sql-query-container" id="sqlQueryContainer">
                <div class="sql-query">
                    <span>SELECT * FROM</span>
                    <select id="currentTableSelect" onchange="updateSQLQuery()">
                        <option value="">Select table...</option>
                    </select>
                </div>
            </div>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Welcome Message -->
                    <?php if (empty($current_database)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-database text-muted" style="font-size: 4rem;"></i>
                            <h3 class="mt-3 text-muted">Welcome to Database Manager</h3>
                            <p class="text-muted">Please select a database from the sidebar to get started.</p>
                        </div>
                                         <?php else: ?>
                         <!-- Table Tabs -->
                         <div id="tableTabsContainer" style="display: none;">
                             <div class="table-tabs-navigation">
                                 <button class="table-nav-arrow" id="tableNavLeft" onclick="scrollTableTabs('left')" disabled>
                                     <i class="bi bi-chevron-left"></i>
                                 </button>
                                 
                                 <div class="table-tabs-scroll">
                                     <div class="table-tabs-wrapper" id="tableTabsWrapper">
                                         <ul class="table-tabs" id="tableTabs" role="tablist">
                                             <!-- Tabs will be dynamically added here -->
                                         </ul>
                                     </div>
                                 </div>
                                 
                                 <button class="table-nav-arrow" id="tableNavRight" onclick="scrollTableTabs('right')" disabled>
                                     <i class="bi bi-chevron-right"></i>
                                 </button>
                             </div>
                             <div class="table-content" id="tableTabsContent">
                                 <!-- Tab content will be dynamically added here -->
                             </div>
                         </div>

                         <!-- Welcome Message for Tables -->
                         <div id="tablesWelcomeView">
                             <div class="text-center py-5">
                                 <i class="bi bi-table text-muted" style="font-size: 4rem;"></i>
                                 <h3 class="mt-3 text-muted">Select a Table</h3>
                                 <p class="text-muted">Choose a table from the sidebar to view and manage its data.</p>
                                 <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                                     <i class="bi bi-plus-circle me-2"></i>Create New Table
                                 </button>
                             </div>
                         </div>
                                         <?php endif; ?>
                 </div>
         </div>
     </div>

    <!-- Create Database Modal -->
    <div class="modal fade" id="createDatabaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createDatabaseForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="dbName" class="form-label">Database Name:</label>
                            <input type="text" class="form-control" id="dbName" name="dbName" required>
                            <div class="form-text">Only letters, numbers, and underscores are allowed.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Database</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Table Modal -->
    <div class="modal fade" id="createTableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createTableForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tableName" class="form-label">Table Name:</label>
                            <input type="text" class="form-control" id="tableName" name="tableName" required>
                        </div>
                        <div id="columnsContainer">
                            <h6>Columns:</h6>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" placeholder="Column Name" name="columns[0][name]" required>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="columns[0][type]" required>
                                        <option value="INT">INT</option>
                                        <option value="VARCHAR(255)">VARCHAR(255)</option>
                                        <option value="TEXT">TEXT</option>
                                        <option value="DATETIME">DATETIME</option>
                                        <option value="BOOLEAN">BOOLEAN</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[0][primary]" id="primary0">
                                        <label class="form-check-label" for="primary0">Primary Key</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeColumn(this)">Remove</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addColumn()">
                            <i class="bi bi-plus-circle me-2"></i>Add Column
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Table</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

         <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     <script src="js/dashboard.js" onerror="console.error('Failed to load dashboard.js')"></script>
     <script>
         // Debug: Check if functions are loaded
         console.log('Dashboard.js loaded. Available functions:', {
             selectDatabase: typeof selectDatabase,
             openTable: typeof openTable,
             closeTable: typeof closeTable,
             toggleView: typeof toggleView,
             refreshTable: typeof refreshTable,
             addRow: typeof addRow,
             editRow: typeof editRow,
             deleteRow: typeof deleteRow,
             closeEditSidebar: typeof closeEditSidebar,
             filterTables: typeof filterTables,
             clearTableSearch: typeof clearTableSearch
         });
         
         // Test database selector
         document.addEventListener('DOMContentLoaded', function() {
             const dbSelect = document.getElementById('databaseSelect');
             if (dbSelect) {
                 console.log('Database select found:', dbSelect);
                 dbSelect.addEventListener('change', function() {
                     console.log('Database select changed to:', this.value);
                     if (typeof selectDatabase === 'function') {
                         selectDatabase(this.value);
                     } else {
                         console.error('selectDatabase function not found!');
                     }
                 });
             } else {
                 console.error('Database select element not found!');
             }
         });
        
        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
            
            // Check if mobile
            if (window.innerWidth <= 767.98) {
                // Mobile behavior
                sidebar.classList.toggle('show');
                if (mobileToggleBtn) {
                    const icon = mobileToggleBtn.querySelector('i');
                    if (sidebar.classList.contains('show')) {
                        icon.className = 'bi bi-x-lg';
                    } else {
                        icon.className = 'bi bi-list';
                    }
                }
            } else {
                // Desktop behavior
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('sidebar-hidden');
                
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('i');
                    if (sidebar.classList.contains('hidden')) {
                        icon.className = 'bi bi-list';
                        toggleBtn.innerHTML = '<i class="bi bi-list"></i> Show Sidebar';
                    } else {
                        icon.className = 'bi bi-x-lg';
                        toggleBtn.innerHTML = '<i class="bi bi-x-lg"></i> Hide Sidebar';
                    }
                }
            }
        }
        
                 // Keyboard shortcut for sidebar toggle (Ctrl/Cmd + B)
         document.addEventListener('keydown', function(e) {
             if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                 e.preventDefault();
                 toggleSidebar();
             }
         });
         
         
          
          
    </script>
</body>
</html>
