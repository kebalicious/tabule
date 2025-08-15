// Dashboard JavaScript functionality

// Global variables
let columnIndex = 1;
let openTables = new Map(); // Store open tables with their data
let currentView = 'grid'; // 'grid' or 'tabs'

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

// Initialize event listeners
function initializeEventListeners() {
    // Create Database Form submission
    const createDatabaseForm = document.getElementById('createDatabaseForm');
    if (createDatabaseForm) {
        createDatabaseForm.addEventListener('submit', handleCreateDatabase);
    }

    // Create Table Form submission
    const createTableForm = document.getElementById('createTableForm');
    if (createTableForm) {
        createTableForm.addEventListener('submit', handleCreateTable);
    }
    
    // Window resize handler for tab navigation
    window.addEventListener('resize', () => {
        setTimeout(updateTabNavigation, 100);
    });
}

// Select database from dropdown
function selectDatabase(dbName) {
    if (!dbName) return;
    
    // Show loading state
    const select = document.getElementById('databaseSelect');
    const originalValue = select.value;
    select.disabled = true;
    
    // Clear table search before redirecting
    clearTableSearch();
    
    // Redirect to select database
    window.location.href = `select_database.php?db=${encodeURIComponent(dbName)}`;
}

// Open table in tabs view
function openTable(tableName) {
    console.log('Opening table:', tableName);
    
    // Close any open sidebars when opening a table
    closeEditSidebar();
    closeAddRowSidebar();
    
    if (openTables.has(tableName)) {
        // Table already open, just switch to its tab
        console.log('Table already open, switching to tab');
        switchToTab(tableName);
        return;
    }
    
    // Load table data
    console.log('Loading table data...');
    loadTableData(tableName).then(data => {
        console.log('Table data loaded:', data);
        if (data.success && data.data) {
            openTables.set(tableName, data.data);
            createTableTab(tableName, data.data);
            switchToTab(tableName);
            showTabsView();
        } else {
            showAlert('Error: ' + (data.error || 'Unknown error'), 'danger');
        }
    }).catch(error => {
        console.error('Error loading table:', error);
        showAlert('Error loading table: ' + error.message, 'danger');
    });
}

// Load table data via AJAX
async function loadTableData(tableName) {
    console.log('Fetching table data for:', tableName);
    const response = await fetch(`view_table_data.php?table=${encodeURIComponent(tableName)}`);
    console.log('Response status:', response.status);
    
    if (!response.ok) {
        const errorText = await response.text();
        console.error('Response error:', errorText);
        throw new Error(`Failed to load table data: ${response.status} ${response.statusText}`);
    }
    
    const data = await response.json();
    console.log('Response data:', data);
    return data;
}

// Create table tab
function createTableTab(tableName, data) {
    const tabsContainer = document.getElementById('tableTabs');
    const contentContainer = document.getElementById('tableTabsContent');
    
    // Create tab
    const tab = document.createElement('li');
    tab.className = 'nav-item';
    tab.innerHTML = `
        <button class="nav-link" id="tab-${tableName}" data-bs-toggle="tab" data-bs-target="#content-${tableName}" type="button">
            <i class="bi bi-table me-2"></i>${tableName}
            <span class="close-tab" onclick="closeTable('${tableName}', event)">×</span>
        </button>
    `;
    tabsContainer.appendChild(tab);
    
    // Create content
    const content = document.createElement('div');
    content.className = 'tab-pane fade';
    content.id = `content-${tableName}`;
    content.innerHTML = createTableContent(tableName, data);
    contentContainer.appendChild(content);
    
    // Update tab navigation arrows after adding new tab
    setTimeout(updateTabNavigation, 100);
}

// Create table content HTML
function createTableContent(tableName, data) {
    let html = `
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>${tableName}</h5>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="refreshTable('${tableName}')">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="addRow('${tableName}')">
                        <i class="bi bi-plus-circle me-2"></i>Add Row
                    </button>
                </div>
            </div>
    `;
    
    if (data.structure && data.structure.length > 0) {
        html += `
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
        `;
        
        // Add column headers
        data.structure.forEach(column => {
            html += `<th>${column.Field}</th>`;
        });
        html += `<th>Actions</th>`;
        
        html += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Add data rows
        if (data.data && data.data.length > 0) {
            data.data.forEach(row => {
                html += '<tr>';
                data.structure.forEach(column => {
                    const value = row[column.Field];
                    html += `<td>${formatValue(value)}</td>`;
                });
                html += `
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editRow('${tableName}', this)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('${tableName}', this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                html += '</tr>';
            });
        } else {
            html += `
                <tr>
                    <td colspan="${data.structure.length + 1}" class="text-center text-muted py-4">
                        No data found
                    </td>
                </tr>
            `;
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    html += '</div>';
    return html;
}

// Switch to specific tab
function switchToTab(tableName) {
    const tab = document.getElementById(`tab-${tableName}`);
    if (tab) {
        // Remove active from all tabs
        document.querySelectorAll('#tableTabs .nav-link').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('#tableTabsContent .tab-pane').forEach(p => p.classList.remove('show', 'active'));
        
        // Activate this tab
        tab.classList.add('active');
        const content = document.getElementById(`content-${tableName}`);
        if (content) {
            content.classList.add('show', 'active');
        }
        
        // Close any open sidebars when switching tabs
        closeEditSidebar();
        closeAddRowSidebar();
    }
}

// Close table tab
function closeTable(tableName, event) {
    event.stopPropagation();
    
    // Remove from open tables
    openTables.delete(tableName);
    
    // Remove tab and content
    const tab = document.getElementById(`tab-${tableName}`);
    const content = document.getElementById(`content-${tableName}`);
    
    if (tab) tab.remove();
    if (content) content.remove();
    
    // If no more tabs, show welcome view
    if (openTables.size === 0) {
        showWelcomeView();
    } else {
        // Switch to first available tab
        const firstTab = document.querySelector('#tableTabs .nav-link');
        if (firstTab) {
            firstTab.click();
        }
    }
    
    // Update tab navigation after closing tab
    setTimeout(updateTabNavigation, 100);
}

// Show tabs view
function showTabsView() {
    const tabsContainer = document.getElementById('tableTabsContainer');
    const welcomeView = document.getElementById('tablesWelcomeView');
    
    if (tabsContainer) tabsContainer.style.display = 'block';
    if (welcomeView) welcomeView.style.display = 'none';
    currentView = 'tabs';
}

// Show welcome view
function showWelcomeView() {
    const tabsContainer = document.getElementById('tableTabsContainer');
    const welcomeView = document.getElementById('tablesWelcomeView');
    
    if (tabsContainer) tabsContainer.style.display = 'none';
    if (welcomeView) welcomeView.style.display = 'block';
    currentView = 'welcome';
}

// Toggle between welcome and tabs view
function toggleView() {
    if (currentView === 'welcome') {
        showTabsView();
    } else {
        showWelcomeView();
    }
}

// Refresh table data
async function refreshTable(tableName) {
    try {
        console.log('Refreshing table:', tableName);
        const response = await loadTableData(tableName);
        
        if (response.success && response.data) {
            openTables.set(tableName, response.data);
            
            // Update tab content
            const content = document.getElementById(`content-${tableName}`);
            if (content) {
                content.innerHTML = createTableContent(tableName, response.data);
            }
            
            showAlert('Table refreshed successfully', 'success');
        } else {
            showAlert('Error: ' + (response.error || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Error refreshing table:', error);
        showAlert('Error refreshing table: ' + error.message, 'danger');
    }
}

// Handle create database form submission
async function handleCreateDatabase(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const dbName = formData.get('dbName').trim();
    
    if (!dbName) {
        showAlert('Please enter a database name', 'warning');
        return;
    }
    
    // Validate database name
    if (!/^[a-zA-Z0-9_]+$/.test(dbName)) {
        showAlert('Database name can only contain letters, numbers, and underscores', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/create_database.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: dbName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Database created successfully!', 'success');
            // Close modal and refresh page
            const modal = bootstrap.Modal.getInstance(document.getElementById('createDatabaseModal'));
            modal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error creating database: ' + error.message, 'danger');
    }
}

// Handle create table form submission
async function handleCreateTable(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const tableData = {
        name: formData.get('tableName'),
        columns: []
    };
    
    // Extract columns data
    const columnNames = formData.getAll('columns[0][name]');
    const columnTypes = formData.getAll('columns[0][type]');
    const columnPrimaries = formData.getAll('columns[0][primary]');
    
    for (let i = 0; i < columnNames.length; i++) {
        if (columnNames[i]) {
            tableData.columns.push({
                name: columnNames[i],
                type: columnTypes[i],
                primary: columnPrimaries[i] === 'on'
            });
        }
    }
    
    try {
        const response = await fetch('api/create_table.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(tableData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Table created successfully!', 'success');
            // Close modal and refresh page
            const modal = bootstrap.Modal.getInstance(document.getElementById('createTableModal'));
            modal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error creating table: ' + error.message, 'danger');
    }
}

// Add column to table creation form
function addColumn() {
    const container = document.getElementById('columnsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'row mb-2';
    newRow.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Column Name" name="columns[${columnIndex}][name]" required>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="columns[${columnIndex}][type]" required>
                <option value="INT">INT</option>
                <option value="VARCHAR(255)">VARCHAR(255)</option>
                <option value="TEXT">TEXT</option>
                <option value="DATETIME">DATETIME</option>
                <option value="BOOLEAN">BOOLEAN</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="columns[${columnIndex}][primary]" id="primary${columnIndex}">
                <label class="form-check-label" for="primary${columnIndex}">Primary Key</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeColumn(this)">Remove</button>
        </div>
    `;
    container.appendChild(newRow);
    columnIndex++;
}

// Remove column from table creation form
function removeColumn(button) {
    const row = button.closest('.row');
    row.remove();
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the main content
    const mainContent = document.querySelector('.main-content');
    mainContent.insertBefore(alertDiv, mainContent.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Format value for display
function formatValue(value) {
    if (value === null || value === undefined) {
        return '<em class="text-muted">NULL</em>';
    }
    if (typeof value === 'boolean') {
        return value ? 'true' : 'false';
    }
    
    // Handle JSON arrays - if it's a string that looks like JSON array, format it nicely
    if (typeof value === 'string' && value.startsWith('[') && value.endsWith(']')) {
        try {
            const parsed = JSON.parse(value);
            if (Array.isArray(parsed)) {
                // Format JSON array nicely for display
                return `<code class="text-primary">${JSON.stringify(parsed, null, 0)}</code>`;
            }
        } catch (e) {
            // If parsing fails, just return as string
        }
    }
    
    return String(value);
}

// Add new row to table
async function addRow(tableName) {
    const tableData = openTables.get(tableName);
    if (!tableData || !tableData.structure) {
        showAlert('Table structure not available', 'danger');
        return;
    }
    
    // Show add row form in sidebar
    showAddRowForm(tableName, tableData.structure);
}

// Edit existing row
async function editRow(tableName, button) {
    const row = button.closest('tr');
    const tableData = openTables.get(tableName);
    
    if (!tableData || !tableData.structure) {
        showAlert('Table structure not available', 'danger');
        return;
    }
    
    // Get current row data
    const currentData = {};
    const cells = row.querySelectorAll('td');
    
    // Skip the last cell (Actions column)
    const dataCells = Array.from(cells).slice(0, -1);
    
    tableData.structure.forEach((column, index) => {
        if (dataCells[index]) {
            const cell = dataCells[index];
            // Get text content and clean it
            let value = cell.textContent.trim();
            
            // Debug: Log the raw cell content
            console.log(`Cell ${index} (${column.Field}):`, {
                textContent: cell.textContent,
                innerHTML: cell.innerHTML,
                trimmed: value
            });
            
            // Handle NULL values - check for the italic NULL text
            if (value === 'NULL' || value === '' || cell.innerHTML.includes('<em class="text-muted">NULL</em>')) {
                value = null;
                console.log(`Setting ${column.Field} to null`);
            } else {
                // Handle JSON arrays - check if cell contains formatted JSON
                if (cell.innerHTML.includes('<code class="text-primary">')) {
                    // Extract JSON from the formatted display
                    const codeMatch = cell.innerHTML.match(/<code class="text-primary">(.*?)<\/code>/);
                    if (codeMatch) {
                        value = codeMatch[1];
                        console.log(`Extracted JSON from formatted display for ${column.Field}:`, value);
                    }
                } else if (typeof value === 'string' && value.startsWith('[') && value.endsWith(']')) {
                    try {
                        const parsed = JSON.parse(value);
                        // Keep as string if it's a valid JSON array
                        value = JSON.stringify(parsed);
                        console.log(`Parsed JSON array for ${column.Field}:`, parsed);
                    } catch (e) {
                        // If parsing fails, keep original value
                        console.log(`Failed to parse JSON for ${column.Field}:`, value);
                    }
                }
            }
            
            currentData[column.Field] = value;
        }
    });
    
    console.log('Current row data:', currentData);
    console.log('Table structure:', tableData.structure);
    
    // Show edit form in sidebar
    showEditForm(tableName, tableData.structure, currentData);
}

// Delete row from table
async function deleteRow(tableName, button) {
    if (!confirm('Are you sure you want to delete this row?')) {
        return;
    }
    
    const row = button.closest('tr');
    const tableData = openTables.get(tableName);
    
    if (!tableData || !tableData.structure) {
        showAlert('Table structure not available', 'danger');
        return;
    }
    
    // Get current row data for where condition
    const whereCondition = {};
    const cells = row.querySelectorAll('td');
    
    // Skip the last cell (Actions column)
    const dataCells = Array.from(cells).slice(0, -1);
    
    // Use only primary key for WHERE condition
    tableData.structure.forEach((column, index) => {
        if (dataCells[index] && column.Key === 'PRI') {
            const cell = dataCells[index];
            // Get text content and clean it
            let value = cell.textContent.trim();
            
            // Handle NULL values
            if (value === 'NULL' || value === '' || cell.innerHTML.includes('<em class="text-muted">NULL</em>')) {
                value = null;
            } else {
                // Handle JSON arrays - check if cell contains formatted JSON
                if (cell.innerHTML.includes('<code class="text-primary">')) {
                    // Extract JSON from the formatted display
                    const codeMatch = cell.innerHTML.match(/<code class="text-primary">(.*?)<\/code>/);
                    if (codeMatch) {
                        value = codeMatch[1];
                    }
                }
            }
            
            whereCondition[column.Field] = value;
        }
    });
    
    // If no primary key found, use first column
    if (Object.keys(whereCondition).length === 0 && tableData.structure.length > 0) {
        const firstColumn = tableData.structure[0];
        const cell = dataCells[0];
        if (cell) {
            let value = cell.textContent.trim();
            if (value === 'NULL' || value === '' || cell.innerHTML.includes('<em class="text-muted">NULL</em>')) {
                value = null;
            } else {
                // Handle JSON arrays - check if cell contains formatted JSON
                if (cell.innerHTML.includes('<code class="text-primary">')) {
                    // Extract JSON from the formatted display
                    const codeMatch = cell.innerHTML.match(/<code class="text-primary">(.*?)<\/code>/);
                    if (codeMatch) {
                        value = codeMatch[1];
                    }
                }
            }
            whereCondition[firstColumn.Field] = value;
        }
    }
    
    console.log('Delete where condition:', whereCondition);
    
    try {
        const response = await fetch('api/delete_row.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                table: tableName,
                where: whereCondition
            })
        });
        
        const result = await response.json();
        console.log('Delete response:', result);
        
        if (result.success) {
            showAlert('Row deleted successfully!', 'success');
            // Refresh the table
            await refreshTable(tableName);
        } else {
            showAlert(result.error, 'danger');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showAlert('Error deleting row: ' + error.message, 'danger');
    }
}

// Create add row modal HTML
function createAddRowModal(tableName, structure) {
    let formFields = '';
    structure.forEach(column => {
        const fieldName = column.Field;
        const fieldType = column.Type;
        const isNull = column.Null === 'YES';
        const isKey = column.Key === 'PRI';
        
        let inputType = 'text';
        if (fieldType.includes('int')) inputType = 'number';
        if (fieldType.includes('decimal') || fieldType.includes('float') || fieldType.includes('double')) inputType = 'number';
        if (fieldType.includes('datetime') || fieldType.includes('timestamp')) inputType = 'datetime-local';
        if (fieldType.includes('date')) inputType = 'date';
        if (fieldType.includes('time')) inputType = 'time';
        
        formFields += `
            <div class="mb-3">
                <label for="${fieldName}" class="form-label">${fieldName}${isKey ? ' <span class="text-danger">*</span>' : ''}</label>
                <input type="${inputType}" class="form-control" id="${fieldName}" name="${fieldName}" ${isKey ? 'required' : ''}>
                <div class="form-text">Type: ${fieldType}${isNull ? ' (NULL allowed)' : ' (NOT NULL)'}</div>
            </div>
        `;
    });
    
    return `
        <div class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Row to ${tableName}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="addRowForm">
                        <div class="modal-body">
                            ${formFields}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Row</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
}

// Show edit form in sidebar
function showEditForm(tableName, structure, currentData) {
    // Create or get edit sidebar
    let editSidebar = document.getElementById('editSidebar');
    if (!editSidebar) {
        editSidebar = document.createElement('div');
        editSidebar.id = 'editSidebar';
        editSidebar.className = 'edit-sidebar';
        document.body.appendChild(editSidebar);
    }
    
    // Create form fields
    let formFields = '';
    structure.forEach(column => {
        const fieldName = column.Field;
        const fieldType = column.Type;
        const isNull = column.Null === 'YES';
        const isKey = column.Key === 'PRI';
        const currentValue = currentData[fieldName] || '';
        
        let inputType = 'text';
        if (fieldType.includes('int')) inputType = 'number';
        if (fieldType.includes('decimal') || fieldType.includes('float') || fieldType.includes('double')) inputType = 'number';
        if (fieldType.includes('datetime') || fieldType.includes('timestamp')) inputType = 'datetime-local';
        if (fieldType.includes('date')) inputType = 'date';
        if (fieldType.includes('time')) inputType = 'time';
        
        // Handle NULL values in display
        let displayValue = currentValue === null ? '' : currentValue;
        
        // Handle JSON arrays - convert to string for display
        if (typeof displayValue === 'string' && displayValue.startsWith('[') && displayValue.endsWith(']')) {
            try {
                // If it's a valid JSON array, keep it as is for display
                JSON.parse(displayValue);
            } catch (e) {
                // If it's not valid JSON, it might be corrupted, show empty
                displayValue = '';
            }
        }
        
        formFields += `
            <div class="mb-3">
                <label for="${fieldName}" class="form-label">${fieldName}${isKey ? ' <span class="text-danger">*</span>' : ''}</label>
                <input type="${inputType}" class="form-control" id="${fieldName}" name="${fieldName}" value="${displayValue}" ${isKey ? 'required' : ''}>
                <div class="form-text">Type: ${fieldType}${isNull ? ' (NULL allowed)' : ' (NOT NULL)'}</div>
            </div>
        `;
    });
    
    // Create sidebar content
    editSidebar.innerHTML = `
        <div class="edit-sidebar-content">
            <div class="edit-sidebar-header">
                <h5 class="mb-0">
                    <i class="bi bi-pencil me-2"></i>Edit Row in ${tableName}
                </h5>
                <button type="button" class="btn-close" onclick="closeEditSidebar()"></button>
            </div>
            <form id="editRowForm">
                <div class="edit-sidebar-body">
                    ${formFields}
                </div>
                <div class="edit-sidebar-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditSidebar()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Row</button>
                </div>
            </form>
        </div>
    `;
    
    // Show sidebar
    editSidebar.classList.add('show');
    
    // Handle form submission
    const form = editSidebar.querySelector('#editRowForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const rowData = {};
        
        // Collect form data
        structure.forEach(column => {
            const value = formData.get(column.Field);
            
            // Handle empty values
            if (value === '') {
                rowData[column.Field] = null;
                return;
            }
            
            // Convert data types based on column type
            const columnType = column.Type.toLowerCase();
            if (columnType.includes('int')) {
                rowData[column.Field] = parseInt(value) || 0;
            } else if (columnType.includes('decimal') || columnType.includes('float') || columnType.includes('double')) {
                rowData[column.Field] = parseFloat(value) || 0;
            } else if (columnType.includes('datetime') || columnType.includes('timestamp')) {
                // Keep datetime as string, let database handle it
                rowData[column.Field] = value;
            } else {
                // Handle JSON arrays - try to parse if it looks like JSON
                if (typeof value === 'string' && value.startsWith('[') && value.endsWith(']')) {
                    try {
                        const parsed = JSON.parse(value);
                        // Keep as string if it's a valid JSON array
                        rowData[column.Field] = JSON.stringify(parsed);
                        console.log(`Form submission: Parsed JSON array for ${column.Field}:`, parsed);
                    } catch (e) {
                        // If parsing fails, keep original value
                        rowData[column.Field] = value;
                        console.log(`Form submission: Failed to parse JSON for ${column.Field}:`, value);
                    }
                } else {
                    rowData[column.Field] = value;
                }
            }
        });
        
        console.log('New row data:', rowData);
        console.log('Where condition:', currentData);
        
        try {
            // Use only primary key for WHERE condition
            const whereCondition = {};
            structure.forEach(column => {
                if (column.Key === 'PRI') {
                    whereCondition[column.Field] = currentData[column.Field];
                }
            });
            
            // If no primary key found, use first column
            if (Object.keys(whereCondition).length === 0 && structure.length > 0) {
                const firstColumn = structure[0];
                whereCondition[firstColumn.Field] = currentData[firstColumn.Field];
            }
            
            const requestData = {
                table: tableName,
                data: rowData,
                where: whereCondition
            };
            
            console.log('Sending request to edit_row.php:', requestData);
            
            const response = await fetch('api/edit_row.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            console.log('Response status:', response.status);
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON response:', parseError);
                showAlert('Invalid response from server: ' + responseText, 'danger');
                return;
            }
            
            console.log('Edit response:', result);
            
            if (result.success) {
                showAlert('Row updated successfully! Affected rows: ' + result.affected_rows, 'success');
                if (result.debug) {
                    console.log('Debug info:', result.debug);
                }
                closeEditSidebar();
                // Refresh the table
                await refreshTable(tableName);
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (error) {
            console.error('Edit error:', error);
            showAlert('Error updating row: ' + error.message, 'danger');
        }
    });
}

// Close edit sidebar
function closeEditSidebar() {
    const editSidebar = document.getElementById('editSidebar');
    if (editSidebar) {
        editSidebar.classList.remove('show');
    }
}

// Show add row form in sidebar
function showAddRowForm(tableName, structure) {
    // Create or get add row sidebar
    let addRowSidebar = document.getElementById('addRowSidebar');
    if (!addRowSidebar) {
        addRowSidebar = document.createElement('div');
        addRowSidebar.id = 'addRowSidebar';
        addRowSidebar.className = 'edit-sidebar'; // Reuse same CSS
        document.body.appendChild(addRowSidebar);
    }
    
    // Create form fields
    let formFields = '';
    structure.forEach(column => {
        const fieldName = column.Field;
        const fieldType = column.Type;
        const isNull = column.Null === 'YES';
        const isKey = column.Key === 'PRI';
        
        let inputType = 'text';
        if (fieldType.includes('int')) inputType = 'number';
        if (fieldType.includes('decimal') || fieldType.includes('float') || fieldType.includes('double')) inputType = 'number';
        if (fieldType.includes('datetime') || fieldType.includes('timestamp')) inputType = 'datetime-local';
        if (fieldType.includes('date')) inputType = 'date';
        if (fieldType.includes('time')) inputType = 'time';
        
        formFields += `
            <div class="mb-3">
                <label for="${fieldName}" class="form-label">${fieldName}${isKey ? ' <span class="text-danger">*</span>' : ''}</label>
                <input type="${inputType}" class="form-control" id="${fieldName}" name="${fieldName}" ${isKey ? 'required' : ''}>
                <div class="form-text">Type: ${fieldType}${isNull ? ' (NULL allowed)' : ' (NOT NULL)'}</div>
            </div>
        `;
    });
    
    // Create sidebar content
    addRowSidebar.innerHTML = `
        <div class="edit-sidebar-content">
            <div class="edit-sidebar-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Add New Row to ${tableName}
                </h5>
                <button type="button" class="btn-close" onclick="closeAddRowSidebar()"></button>
            </div>
            <form id="addRowForm">
                <div class="edit-sidebar-body">
                    ${formFields}
                </div>
                <div class="edit-sidebar-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddRowSidebar()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Row</button>
                </div>
            </form>
        </div>
    `;
    
    // Show sidebar
    addRowSidebar.classList.add('show');
    
    // Handle form submission
    const form = addRowSidebar.querySelector('#addRowForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const rowData = {};
        
        // Collect form data
        structure.forEach(column => {
            const value = formData.get(column.Field);
            
            // Handle empty values
            if (value === '') {
                rowData[column.Field] = null;
                return;
            }
            
            // Convert data types based on column type
            const columnType = column.Type.toLowerCase();
            if (columnType.includes('int')) {
                rowData[column.Field] = parseInt(value) || 0;
            } else if (columnType.includes('decimal') || columnType.includes('float') || columnType.includes('double')) {
                rowData[column.Field] = parseFloat(value) || 0;
            } else if (columnType.includes('datetime') || columnType.includes('timestamp')) {
                // Keep datetime as string, let database handle it
                rowData[column.Field] = value;
            } else {
                // Handle JSON arrays - try to parse if it looks like JSON
                if (typeof value === 'string' && value.startsWith('[') && value.endsWith(']')) {
                    try {
                        const parsed = JSON.parse(value);
                        // Keep as string if it's a valid JSON array
                        rowData[column.Field] = JSON.stringify(parsed);
                        console.log(`Form submission: Parsed JSON array for ${column.Field}:`, parsed);
                    } catch (e) {
                        // If parsing fails, keep original value
                        rowData[column.Field] = value;
                        console.log(`Form submission: Failed to parse JSON for ${column.Field}:`, value);
                    }
                } else {
                    rowData[column.Field] = value;
                }
            }
        });
        
        try {
            const response = await fetch('api/add_row.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    table: tableName,
                    data: rowData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('Row added successfully!', 'success');
                closeAddRowSidebar();
                // Refresh the table
                await refreshTable(tableName);
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (error) {
            showAlert('Error adding row: ' + error.message, 'danger');
        }
    });
}

// Close add row sidebar
function closeAddRowSidebar() {
    const addRowSidebar = document.getElementById('addRowSidebar');
    if (addRowSidebar) {
        addRowSidebar.classList.remove('show');
    }
}

// Tab navigation functions
let tabScrollPosition = 0;

// Update tab navigation arrows
function updateTabNavigation() {
    const wrapper = document.getElementById('tabScrollWrapper');
    const leftArrow = document.getElementById('tabNavLeft');
    const rightArrow = document.getElementById('tabNavRight');
    
    if (!wrapper || !leftArrow || !rightArrow) return;
    
    const wrapperWidth = wrapper.offsetWidth;
    const scrollWidth = wrapper.scrollWidth;
    
    // Enable/disable left arrow
    leftArrow.disabled = tabScrollPosition <= 0;
    
    // Enable/disable right arrow
    rightArrow.disabled = tabScrollPosition >= scrollWidth - wrapperWidth;
}

// Scroll tabs left or right
function scrollTabs(direction) {
    const wrapper = document.getElementById('tabScrollWrapper');
    if (!wrapper) return;
    
    const scrollAmount = 200; // Scroll by 200px
    
    if (direction === 'left') {
        tabScrollPosition = Math.max(0, tabScrollPosition - scrollAmount);
    } else {
        const maxScroll = wrapper.scrollWidth - wrapper.offsetWidth;
        tabScrollPosition = Math.min(maxScroll, tabScrollPosition + scrollAmount);
    }
    
    wrapper.style.transform = `translateX(-${tabScrollPosition}px)`;
    updateTabNavigation();
}

// Close all tabs
function closeAllTabs() {
    if (!confirm('Are you sure you want to close all tabs?')) {
        return;
    }
    
    // Get all open table names
    const tableNames = Array.from(openTables.keys());
    
    // Close each table
    tableNames.forEach(tableName => {
        openTables.delete(tableName);
        
        // Remove tab and content
        const tab = document.getElementById(`tab-${tableName}`);
        const content = document.getElementById(`content-${tableName}`);
        
        if (tab) tab.remove();
        if (content) content.remove();
    });
    
    // Reset scroll position
    tabScrollPosition = 0;
    const wrapper = document.getElementById('tabScrollWrapper');
    if (wrapper) {
        wrapper.style.transform = 'translateX(0)';
    }
    
    // Show welcome view
    showWelcomeView();
    
    // Update navigation
    updateTabNavigation();
}

// Create edit row modal HTML (keeping for reference)
function createEditRowModal(tableName, structure, currentData) {
    let formFields = '';
    structure.forEach(column => {
        const fieldName = column.Field;
        const fieldType = column.Type;
        const isNull = column.Null === 'YES';
        const isKey = column.Key === 'PRI';
        const currentValue = currentData[fieldName] || '';
        
        let inputType = 'text';
        if (fieldType.includes('int')) inputType = 'number';
        if (fieldType.includes('decimal') || fieldType.includes('float') || fieldType.includes('double')) inputType = 'number';
        if (fieldType.includes('datetime') || fieldType.includes('timestamp')) inputType = 'datetime-local';
        if (fieldType.includes('date')) inputType = 'date';
        if (fieldType.includes('time')) inputType = 'time';
        
        // Handle NULL values in display
        let displayValue = currentValue === null ? '' : currentValue;
        
        // Handle JSON arrays - convert to string for display
        if (typeof displayValue === 'string' && displayValue.startsWith('[') && displayValue.endsWith(']')) {
            try {
                // If it's a valid JSON array, keep it as is for display
                JSON.parse(displayValue);
            } catch (e) {
                // If it's not valid JSON, it might be corrupted, show empty
                displayValue = '';
            }
        }
        
        formFields += `
            <div class="mb-3">
                <label for="${fieldName}" class="form-label">${fieldName}${isKey ? ' <span class="text-danger">*</span>' : ''}</label>
                <input type="${inputType}" class="form-control" id="${fieldName}" name="${fieldName}" value="${displayValue}" ${isKey ? 'required' : ''}>
                <div class="form-text">Type: ${fieldType}${isNull ? ' (NULL allowed)' : ' (NOT NULL)'}</div>
            </div>
        `;
    });
    
    return `
        <div class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Row in ${tableName}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editRowForm">
                        <div class="modal-body">
                            ${formFields}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Row</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
}

// Export functions for global access
window.selectDatabase = selectDatabase;
window.openTable = openTable;
window.closeTable = closeTable;
window.toggleView = toggleView;
window.refreshTable = refreshTable;
window.addColumn = addColumn;
window.removeColumn = removeColumn;
window.addRow = addRow;
window.editRow = editRow;
window.deleteRow = deleteRow;
window.closeEditSidebar = closeEditSidebar;
window.closeAddRowSidebar = closeAddRowSidebar;
window.scrollTabs = scrollTabs;
window.closeAllTabs = closeAllTabs;
