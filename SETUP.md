# SmartGrade System - Setup Instructions

## 📋 Prerequisites

Before setting up SmartGrade, ensure you have:

- **XAMPP** (Apache + MySQL + PHP 7.4 or higher)
- Web browser (Chrome, Firefox, Edge recommended)
- Text editor (VS Code, Sublime, etc.) for any customizations

---

## 🚀 Installation Steps

### Step 1: Copy Project to XAMPP htdocs

1. Copy the `smartgrade-v` folder to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\smartgrade-v\
   ```

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both are running (green indicators)

### Step 3: Create Database

#### Option A: Using phpMyAdmin (Recommended)

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click **"Import"** tab
3. Click **"Choose File"** and select:
   ```
   C:\xampp\htdocs\smartgrade-v\smartgrade_db.sql
   ```
4. Click **"Go"** button at the bottom
5. Wait for success message

#### Option B: Using MySQL Command Line

```bash
mysql -u root -p < smartgrade_db.sql
```

### Step 4: Verify Database Creation

1. In phpMyAdmin, check if `smartgrade_db` database exists
2. Verify it contains tables:
   - users
   - students
   - teachers
   - subjects
   - grades
   - school_years
   - grading_periods
   - audit_logs
   - etc.

### Step 5: Access the System

1. Open browser and go to:
   ```
   http://localhost/smartgrade-v/public/
   ```
2. You should see the login page

---

## 👤 Default User Accounts

### Administrator (ICT Coordinator)

- **Username:** `admin`
- **Password:** `admin123`
- **Access:** Full system control

### Teacher Account

- **Username:** `jdelacruz`
- **Password:** `teacher123`
- **Access:** Grade entry, SF9/SF10 generation

### Student Account

- **Username:** `2024001`
- **Password:** `student123`
- **Access:** View own grades and records

---

## 🔧 Configuration (if needed)

### Database Connection Settings

If you have custom MySQL credentials, edit:

```
app/config/database.php
```

Change these values:

```php
private $host = 'localhost';
private $db_name = 'smartgrade_db';
private $username = 'root';
private $password = ''; // Your MySQL password
```

### Base URL Configuration

If your folder name is different, edit:

```
app/config/config.php
```

Change:

```php
define('BASE_URL', 'http://localhost/smartgrade-v/');
```

---

## 📁 Project Structure

```
smartgrade-v/
│
├── app/
│   ├── config/
│   │   ├── database.php       # Database connection
│   │   └── config.php         # System configuration
│   │
│   ├── controllers/           # Business logic (future expansion)
│   │
│   ├── models/               # Database models (future expansion)
│   │
│   ├── views/
│   │   ├── admin/            # Admin dashboard & pages
│   │   │   └── dashboard.php
│   │   ├── teacher/          # Teacher dashboard & pages
│   │   │   └── dashboard.php
│   │   └── student/          # Student dashboard & pages
│   │       └── dashboard.php
│   │
│   ├── middleware/           # RBAC and authentication checks
│   │
│   └── helpers/
│       ├── security.php      # Authentication & security functions
│       ├── utils.php         # Utility functions
│       └── grade_helper.php  # Grade computation functions
│
├── auth/
│   ├── login.php            # Login page
│   └── logout.php           # Logout handler
│
├── public/
│   ├── index.php            # Main entry point
│   └── assets/
│       ├── css/             # Custom stylesheets
│       ├── js/              # JavaScript files
│       └── images/          # Images and logos
│
├── database/
│   └── (future migrations)
│
├── smartgrade_db.sql        # Database schema
└── readme.md               # Project requirements
```

---

## ✅ Testing the Installation

### 1. Test Database Connection

- Login with any account
- If login succeeds, database is connected

### 2. Test Admin Dashboard

- Login as: `admin` / `admin123`
- Should see statistics and recent activity

### 3. Test Teacher Dashboard

- Login as: `jdelacruz` / `teacher123`
- Should see assigned subjects and classes

### 4. Test Student Dashboard

- Login as: `2024001` / `student123`
- Should see student info and grades

---

## 🔐 Security Notes

### For Production Deployment:

1. **Change all default passwords** immediately
2. **Enable HTTPS** (change `cookie_secure` to 1 in config.php)
3. **Set strong database password**
4. **Change environment** to 'production' in config.php
5. **Disable error display** (already configured for production)
6. **Regular backups** of database

### Password Security:

- All passwords are hashed using PHP's `password_hash()`
- Uses BCrypt algorithm (PASSWORD_DEFAULT)
- Never stored in plain text

---

## 📊 Features Implemented

### Core Features ✅

- ✅ Role-Based Access Control (Admin, Teacher, Student)
- ✅ Secure Authentication System
- ✅ Session Management
- ✅ Dashboard for all user roles
- ✅ Grade Computation (DepEd K-12 Formula)
- ✅ Audit Logging
- ✅ Database Schema (Normalized)

### Upcoming Features 🚧

- 🚧 Grade Entry Interface
- 🚧 SF9 Generation (Report Card)
- 🚧 SF10 Generation (Learner's Progress Report)
- 🚧 Certificate Generation (Single & Bulk)
- 🚧 Honor Student Identification
- 🚧 Student Archiving System
- 🚧 Export to PDF/Excel
- 🚧 User Management (Admin)
- 🚧 Subject Management
- 🚧 School Year Management

---

## 🆘 Troubleshooting

### Problem: "Database Connection Error"

**Solution:**

- Make sure MySQL is running in XAMPP
- Verify database name is `smartgrade_db`
- Check username/password in `config/database.php`

### Problem: "Page Not Found" or 404 Errors

**Solution:**

- Verify Apache is running
- Check that folder is in `htdocs`
- Use correct URL: `http://localhost/smartgrade-v/public/`

### Problem: "Cannot modify header information"

**Solution:**

- Ensure no output before `session_start()`
- Check for BOM in PHP files
- Remove spaces/newlines before `<?php`

### Problem: Login doesn't work

**Solution:**

- Verify database was imported correctly
- Check users table has data
- Try re-importing `smartgrade_db.sql`

---

## 📞 Support

For issues or questions related to this project:

1. Check the **readme.md** file for project requirements
2. Review the **troubleshooting** section above
3. Check XAMPP error logs:
   - Apache: `C:\xampp\apache\logs\error.log`
   - PHP: `C:\xampp\php\logs\php_error_log`

---

## 📝 Notes for Developers

### Grade Computation Formula

- Written Works: 30%
- Performance Tasks: 50%
- Quarterly Assessment: 20%
- Uses DepEd transmutation table (60-100 scale)

### Honors Criteria

- **With Honors:** General Average ≥ 90
- **With High Honors:** General Average ≥ 95
- **With Highest Honors:** General Average ≥ 98

### Database Relationships

- All foreign keys properly defined
- Cascade deletes where appropriate
- Indexed for performance

---

## 🎓 Academic Use Notice

This system was developed as a **Software Engineering 2 (CSC 107)** project for Ampayon Senior High School. It demonstrates:

- MVC Architecture
- Secure Authentication
- RBAC Implementation
- Database Design & Normalization
- PHP Best Practices
- Bootstrap 5 UI/UX

---

**Last Updated:** December 20, 2025

**Version:** 1.0.0 (Beta)
