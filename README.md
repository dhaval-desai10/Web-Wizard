# Student Portal with Role Management

A comprehensive PHP-based student portal system with admin and student access, featuring secure authentication, profile management, and notification systems.

## Features

### Student Features
- **Registration & Login**: Secure student registration with department selection and comprehensive profile details
- **Profile Management**: Update personal information, father details, and education records
- **Notifications**: View notifications from admin (department-specific or general)
- **Messages**: Receive messages from admin
- **Dashboard**: Overview of profile and recent updates
- **Remember Me**: Login convenience with secure cookies

### Admin Features  
- **Student Management**: View, search, edit, and delete student records
- **Notification System**: Send notifications with file attachments (PDF/images) to all students or specific departments
- **Message System**: Send text messages to all students or specific departments
- **Dashboard**: Statistics and system overview
- **Advanced Search**: Filter students by department and search terms

### Security Features
- **SQL Injection Protection**: All database queries use prepared statements
- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Session Management**: Secure session handling
- **Input Validation**: Both client-side (JavaScript) and server-side (PHP) validation
- **File Upload Security**: Secure file uploads with type and size validation
- **CSRF Protection**: Form validation and sanitization

## Requirements

- **XAMPP** (Apache, PHP 7.4+, MySQL)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **5MB** minimum free disk space

## 🎥 Project Demo

[![Watch the Demo Video](./images/demo-thumbnail.png)](https://drive.google.com/file/d/1NulzpwqEcAbQ2WC47GZn_a3S5ql-BbnJ/view?usp=drive_link)

> 🎬 Click the image above to watch the full project demo video on Google Drive.

## Installation Instructions

### Step 1: Setup XAMPP
1. Start **XAMPP Control Panel**
2. Start **Apache** and **MySQL** services
3. Ensure both services are running (green status)

### Step 2: Database Setup
1. Open your web browser and go to `http://localhost/phpmyadmin`
2. Click on **"New"** to create a new database
3. Enter database name: `student_portal`
4. Click **"Create"**
5. Select the `student_portal` database
6. Click on **"Import"** tab
7. Click **"Choose File"** and select `database_setup.sql` from the project folder
8. Click **"Go"** to import the database structure and default data

**Alternative Method:**
You can also copy the SQL content from `database_setup.sql` and run it directly in the SQL tab of phpMyAdmin.

### Step 3: Project Setup
1. Copy the entire `student-portal` folder to your XAMPP's `htdocs` directory
   - Default path: `C:\xampp\htdocs\` (Windows)
   - Your project should be at: `C:\xampp\htdocs\student-portal\`

2. Verify folder permissions (uploads folder should be writable)

### Step 4: Configuration
1. Open `includes/config.php`
2. Verify database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'student_portal');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
3. If your MySQL has a password, update `DB_PASS` accordingly

### Step 5: Access the Application
Open your web browser and navigate to:
```
http://localhost/student-portal/
```

## Default Login Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin123`

### Student Access
Students need to register first using the registration form. After registration, they can login using their Student ID and password.

## File Structure

```
student-portal/
├── admin/                          # Admin interface files
│   ├── dashboard.php              # Admin dashboard
│   ├── students.php               # Student management
│   ├── student_detail.php         # Student details view
│   ├── edit_student.php           # Edit student form
│   ├── notifications.php          # Notification management
│   └── messages.php               # Message management
├── student/                        # Student interface files
│   ├── dashboard.php              # Student dashboard
│   ├── profile.php                # Profile management
│   ├── notifications.php          # View notifications
│   └── messages.php               # View messages
├── includes/                       # Core system files
│   ├── config.php                 # Database configuration
│   └── functions.php              # Utility functions
├── assets/                         # Static resources
│   ├── css/style.css              # Main stylesheet
│   └── js/validation.js           # Form validation
├── uploads/                        # File upload directory
│   └── notifications/             # Notification attachments
├── index.php                      # Main login page
├── register.php                   # Student registration
├── logout.php                     # Logout handler
├── database_setup.sql             # Database structure
└── README.md                      # This file
```

## Usage Guide

### For Students

1. **Registration**:
   - Go to the registration page
   - Fill in all required fields including personal details, father details, and education information
   - Select your department
   - Create a strong password
   - Submit the form

2. **Login**:
   - Enter your Student ID and password
   - Optionally check "Remember Me" for convenience
   - Access your dashboard

3. **Profile Management**:
   - View and update personal information
   - Modify father details and education records
   - Save changes

4. **Notifications & Messages**:
   - Check dashboard for recent notifications and messages
   - View detailed notifications with attachments
   - Read messages from admin

### For Administrators

1. **Login**:
   - Use `admin` as username and `admin123` as password
   - Access the admin dashboard

2. **Student Management**:
   - View all registered students
   - Search students by name, ID, email, or department
   - Edit student information
   - Delete student records (with confirmation)
   - View detailed student profiles

3. **Sending Notifications**:
   - Create notifications with title and message
   - Attach PDF or image files
   - Send to all departments or specific departments
   - View all sent notifications

4. **Sending Messages**:
   - Create messages with subject and content
   - Send to all departments or specific departments
   - View message history

## Database Schema

### Tables
- **departments**: Department information
- **students**: Student records with personal, father, and education details
- **notifications**: Admin notifications with file attachments
- **messages**: Admin messages to students

### Key Fields
- All passwords are hashed using PHP's `password_hash()`
- File uploads are validated for type and size
- Timestamps track creation and modification dates
- Foreign key relationships maintain data integrity

## Security Measures

1. **Input Sanitization**: All user inputs are sanitized using `htmlspecialchars()`
2. **Prepared Statements**: All database queries use PDO prepared statements
3. **Password Security**: Passwords are hashed using `PASSWORD_DEFAULT`
4. **File Upload Security**: File type and size validation
5. **Session Security**: Proper session management and regeneration
6. **CSRF Protection**: Form validation and nonce tokens where applicable

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify XAMPP MySQL is running
   - Check database credentials in `config.php`
   - Ensure `student_portal` database exists

2. **File Upload Issues**:
   - Check `uploads/notifications/` folder permissions
   - Verify PHP upload settings in `php.ini`
   - Ensure file size is under 5MB

3. **Login Issues**:
   - Verify admin credentials: `admin` / `admin123`
   - For students, ensure registration was successful
   - Check browser cookies if using "Remember Me"

4. **Page Not Found**:
   - Verify project is in correct XAMPP htdocs folder
   - Check Apache is running in XAMPP
   - Ensure correct URL: `http://localhost/student-portal/`

### Error Logs
- Check browser console for JavaScript errors
- PHP errors will be displayed on pages (in development)
- Check XAMPP error logs for server issues

## Customization

### Adding New Departments
1. Access phpMyAdmin
2. Navigate to `student_portal` database
3. Open `departments` table
4. Insert new department records

### Modifying Admin Credentials
1. Edit `includes/config.php`
2. Update `ADMIN_USERNAME` and `ADMIN_PASSWORD` constants

### UI Customization
- Modify `assets/css/style.css` for styling changes
- Update color scheme by changing gradient values
- Modify layout by editing CSS grid and flexbox properties

## Version Information
- **Version**: 1.0
- **PHP Version**: 7.4+
- **MySQL Version**: 5.7+
- **License**: MIT

## Support
For issues or questions, please check the troubleshooting section or review the code comments for implementation details.
