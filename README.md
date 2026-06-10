# Enterprise Task Management System

A full-featured task management system with role-based access control, user management, reporting, notifications, and more.

## Technology Stack

- **Backend:** PHP 8.1+
- **Frontend:** Bootstrap 5, JavaScript, jQuery
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Libraries:** PHPMailer, PhpSpreadsheet, TCPDF

## Features

- User authentication with login, logout, and password reset
- Role-based access control (RBAC)
- User management (CRUD, status, unlock)
- Task management with assign, reassign, complete, archive, clone
- Task comments and attachments
- Notifications (read, mark read, delete)
- Reports with export (PDF, Excel, CSV)
- Activity logging
- Database backup and restore
- Profile management with login/activity history
- System settings (general, email, security)
- CSRF protection
- AJAX support
- Responsive design

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/your-repo/task-ms.git
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Create a MySQL database and import the schema:
   ```
   mysql -u root -p your_database < database/schema.sql
   ```

4. Copy the database config and update credentials:
   ```
   cp config/database.example.php config/database.php
   ```
   Edit `config/database.php` with your database connection details.

5. Configure your web server to point to the `public/` directory as the document root.

6. Set storage permissions:
   ```
   chmod -R 775 storage/
   ```
   The `storage/` directory must be writable by the web server.

## Default Credentials

- **Username:** admin
- **Password:** password

**Important:** Change the default password immediately after first login.

## System Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled (or equivalent URL rewriting)
- Composer
- PHP extensions: PDO, MySQL, mbstring, zip, gd, openssl

## Directory Structure

```
task-ms/
├── app/
│   ├── Controllers/
│   ├── Helpers/
│   ├── Middleware/
│   ├── Models/
│   └── Services/
├── config/
├── database/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── index.php
│   └── .htaccess
├── storage/
│   ├── backups/
│   ├── logs/
│   └── uploads/
├── vendor/
├── composer.json
└── README.md
```

## License

This project is licensed under the MIT License.
