# Database Management System

A modern, web-based database management application similar to phpMyAdmin, built with PHP, Bootstrap 5.3, and JavaScript. This application allows users to connect to MySQL/MariaDB databases and manage them through an intuitive web interface.

## Features

### 🔐 Authentication
- Secure login interface using MySQL/MariaDB credentials
- Session-based authentication
- Support for connecting to any MySQL/MariaDB server

### 🗄️ Database Management
- View all databases on the server
- Create new databases
- Select and switch between databases
- Database information and statistics

### 📊 Table Management
- View all tables in selected database
- Create new tables with custom columns
- View table structure and data
- Table statistics (row count, column count)

### 🔍 Data Viewing & Editing
- View table data with pagination
- Table structure analysis
- Sample SQL queries for common operations
- Copy-to-clipboard functionality for SQL queries

### 💻 SQL Query Interface
- Execute custom SQL queries
- View query results in formatted tables
- Support for SELECT, INSERT, UPDATE, DELETE operations
- Error handling and result display

### 🎨 Modern UI
- Responsive design with Bootstrap 5.3
- Beautiful gradient backgrounds
- Intuitive navigation with tabs
- Bootstrap Icons for better UX
- Mobile-friendly interface

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB server
- Web server (Apache/Nginx)
- PDO MySQL extension enabled
- Modern web browser

## Installation

1. **Clone or download the project files** to your web server directory
2. **Ensure PHP PDO MySQL extension is enabled** in your php.ini
3. **Set up your web server** to serve the files
4. **Access the application** through your web browser

### File Structure
```
dbsys/
├── index.php              # Login page
├── dashboard.php          # Main dashboard
├── view_table.php         # Table viewer
├── select_database.php    # Database selection handler
├── js/
│   └── dashboard.js       # Dashboard JavaScript functionality
├── api/
│   ├── execute_sql.php    # SQL execution API
│   ├── create_database.php # Database creation API
│   └── create_table.php   # Table creation API
└── README.md              # This file
```

## Usage

### 1. Login
- Open the application in your web browser
- Enter your MySQL/MariaDB server details:
  - **Host**: Usually `localhost` or your server IP
  - **Port**: Usually `3306` for MySQL
  - **Username**: Your MySQL username
  - **Password**: Your MySQL password
  - **Database**: (Optional) Specific database to connect to

### 2. Dashboard Navigation
After successful login, you'll see the main dashboard with:
- **Sidebar**: Navigation menu and connection info
- **Databases Tab**: View and manage databases
- **Tables Tab**: View and manage tables (when database is selected)
- **SQL Query Tab**: Execute custom SQL queries
- **Users Tab**: User management (placeholder for future features)

### 3. Database Operations
- **View Databases**: All databases are displayed as cards
- **Create Database**: Click "Create Database" button and enter name
- **Select Database**: Click on any database card to select it

### 4. Table Operations
- **View Tables**: After selecting a database, view all tables
- **Create Table**: Click "Create Table" and define columns
- **View Table Data**: Click on any table to view its contents

### 5. SQL Queries
- Navigate to the SQL Query tab
- Enter your SQL query in the textarea
- Click "Execute Query" to run it
- View results in a formatted table

## Security Features

- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Uses PDO prepared statements
- **Session Management**: Secure session handling
- **Error Handling**: Graceful error handling without exposing sensitive information

## API Endpoints

The application includes RESTful API endpoints for AJAX operations:

- `POST /api/execute_sql.php` - Execute SQL queries
- `POST /api/create_database.php` - Create new databases
- `POST /api/create_table.php` - Create new tables

All API endpoints require authentication and return JSON responses.

## Customization

### Styling
- Modify CSS in the `<style>` sections of each PHP file
- Update Bootstrap classes for different themes
- Customize colors and gradients

### Functionality
- Add new features by extending the JavaScript files
- Create additional API endpoints for new operations
- Implement user management features

## Troubleshooting

### Quick Test
Before using the application, test your database connection by visiting:
```
http://your-domain/test_connection.php
```

This will help identify common connection issues.

### Common Issues

1. **Connection Failed**
   - **MySQL not running**: Start MySQL service
   - **XAMPP**: Open XAMPP Control Panel → Start MySQL
   - **Laragon**: Open Laragon → Start MySQL
   - **WAMP**: Start WAMP → MySQL should start automatically
   - **Wrong credentials**: Try username 'root' with empty password

2. **PDO MySQL Extension Missing**
   - Enable PDO MySQL extension in php.ini
   - Uncomment: `extension=pdo_mysql`
   - Restart web server

3. **Permission Denied**
   - Verify MySQL user has appropriate permissions
   - Check database and table access rights
   - Try connecting with root user first

4. **Page Not Found**
   - Ensure web server is properly configured
   - Check file permissions (should be 644 for files, 755 for directories)
   - Verify URL routing

### Common Connection Settings

| Environment | Host | Port | Username | Password |
|-------------|------|------|----------|----------|
| XAMPP | localhost | 3306 | root | (empty) |
| Laragon | localhost | 3306 | root | (empty) |
| WAMP | localhost | 3306 | root | (empty) |
| MAMP | localhost | 8889 | root | root |
| Remote Server | server-ip | 3306 | your-username | your-password |

### Error Messages
- All errors are displayed in user-friendly format
- Check browser console for JavaScript errors
- Review server error logs for PHP issues
- Use `test_connection.php` for detailed diagnostics

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Contributing

Feel free to contribute to this project by:
- Reporting bugs
- Suggesting new features
- Submitting pull requests
- Improving documentation

## License

This project is open source and available under the MIT License.

## Support

For support or questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Test with a simple database first

---

**Note**: This application is designed for development and testing environments. For production use, consider additional security measures such as HTTPS, stronger authentication, and access controls.
