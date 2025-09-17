# Learning Management System (LMS)

A comprehensive, fully functional Learning Management System built with PHP, MySQL, and modern web technologies. This system provides complete admin and student portals with full database integration and CRUD operations.

## Features

### Admin Portal
- **Dashboard** - Overview with statistics and quick actions
- **Courses Management** - Create, edit, delete courses
- **Students Management** - Add, edit, delete students with course enrollment
- **Lessons Management** - Create and organize course content
- **Tests Management** - Create assessments with questions
- **Users Management** - Manage admin, instructor, and student accounts
- **Reports & Analytics** - Visual charts and progress tracking
- **System Settings** - Configuration and preferences

### Student Portal
- **Dashboard** - Personal learning overview and progress
- **My Courses** - View enrolled courses with progress tracking
- **Lessons** - Access course materials and content
- **Assessments** - Take tests and view results
- **Progress Tracking** - Visual progress charts and achievements
- **Profile Management** - Personal information and preferences
- **Support System** - Help resources and contact options

### Database Features
- **Relational Design** - Proper foreign key relationships
- **User Management** - Secure authentication and authorization
- **Course Enrollments** - Student-course relationships
- **Progress Tracking** - Lesson completion and test results
- **Data Integrity** - Soft deletes and validation

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Charts**: Chart.js
- **Database**: PDO with prepared statements

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependency management)

### Step 1: Database Setup
1. Create a new MySQL database:
   ```sql
   CREATE DATABASE lms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Update database configuration in `db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'lms_db');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

### Step 2: File Setup
1. Upload all files to your web server directory
2. Ensure proper file permissions (755 for directories, 644 for files)
3. Make sure the web server can write to the uploads directory

### Step 3: Database Initialization
1. Run the database initialization script:
   ```
   http://your-domain.com/init_db.php
   ```
2. This will create all necessary tables and insert sample data
3. Delete or rename `init_db.php` after successful execution

### Step 4: Access the System
- **Admin Portal**: `http://your-domain.com/admin/`
- **Student Portal**: `http://your-domain.com/student/`
- **Main Page**: `http://your-domain.com/`

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: password

**Important**: Change the default password after first login!

## Database Structure

### Core Tables
- `users` - User accounts and authentication
- `students` - Student profiles and information
- `courses` - Course information and metadata
- `lessons` - Course lessons and content
- `tests` - Assessments and quizzes
- `test_questions` - Test questions and options
- `course_enrollments` - Student-course relationships
- `lesson_progress` - Student lesson completion tracking
- `test_attempts` - Student test results

### Relationships
- One student can enroll in many courses
- One course can have many lessons
- One course can have many tests
- One lesson can have many students (progress tracking)
- One test can have many students (attempts)

## API Endpoints

### Admin APIs
- `admin/api/courses.php` - Course CRUD operations
- `admin/api/students.php` - Student CRUD operations
- `admin/api/lessons.php` - Lesson CRUD operations
- `admin/api/tests.php` - Test CRUD operations
- `admin/api/users.php` - User CRUD operations

### HTTP Methods Supported
- **GET** - Retrieve data
- **POST** - Create new records
- **PUT** - Update existing records
- **DELETE** - Soft delete records

## Security Features

- **Session Management** - Secure user sessions
- **Authentication** - Password hashing with bcrypt
- **Authorization** - Role-based access control
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Input sanitization
- **CSRF Protection** - Form token validation

## File Structure

```
LMS/
├── admin/                 # Admin portal
│   ├── api/              # Admin API endpoints
│   ├── assets/           # Admin CSS/JS files
│   └── index.php         # Admin main page
├── student/              # Student portal
│   ├── assets/           # Student CSS/JS files
│   └── index.php         # Student main page
├── include/              # Shared PHP classes
├── uploads/              # File uploads directory
├── db.php                # Database connection
├── database_migration.sql # Database schema
├── init_db.php           # Database initialization
├── index.php             # Main landing page
├── login.php             # Login page
├── logout.php            # Logout handler
└── README.md             # This file
```

## Usage Guide

### For Administrators
1. **Login** to admin portal with admin credentials
2. **Create Courses** - Add new courses with descriptions and settings
3. **Add Students** - Create student accounts and enroll them in courses
4. **Create Lessons** - Add content to courses with lessons
5. **Create Tests** - Build assessments with questions
6. **Monitor Progress** - Track student performance and course completion

### For Students
1. **Login** to student portal with student credentials
2. **View Courses** - See enrolled courses and progress
3. **Access Lessons** - Read course materials and watch videos
4. **Take Tests** - Complete assessments and view results
5. **Track Progress** - Monitor learning achievements
6. **Update Profile** - Manage personal information

## Customization

### Adding New Features
- Extend the database schema in `database_migration.sql`
- Create new API endpoints in `admin/api/`
- Add new pages to admin and student portals
- Update CSS styles in respective asset files

### Styling
- Admin styles: `admin/assets/css/admin-styles.css`
- Student styles: `student/assets/css/student-styles.css`
- Modify CSS variables for color schemes
- Add custom components and layouts

### JavaScript Functionality
- Admin scripts: `admin/assets/js/admin-script.js`
- Student scripts: `student/assets/js/student-script.js`
- Extend with new features and interactions

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `db.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **Permission Denied**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server can write to uploads directory

3. **Page Not Found**
   - Verify .htaccess configuration
   - Check web server URL rewriting settings

4. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check session storage permissions

### Debug Mode
Enable error reporting in PHP for development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Performance Optimization

### Database
- Use indexes on frequently queried columns
- Optimize complex queries with EXPLAIN
- Consider query caching for read-heavy operations

### Frontend
- Minify CSS and JavaScript files
- Optimize images and media files
- Enable browser caching

### Server
- Use PHP OPcache for production
- Enable MySQL query cache
- Consider CDN for static assets

## Backup and Maintenance

### Database Backup
```bash
mysqldump -u username -p lms_db > lms_backup.sql
```

### File Backup
- Backup uploads directory
- Backup configuration files
- Backup custom modifications

### Regular Maintenance
- Monitor database performance
- Clean up old session files
- Update system components

## Support and Contributing

### Getting Help
- Check the troubleshooting section
- Review error logs for specific issues
- Test with sample data first

### Contributing
- Fork the repository
- Create feature branches
- Submit pull requests with detailed descriptions

## License

This project is open source and available under the MIT License.

## Version History

- **v1.0.0** - Initial release with complete admin and student portals
- Full CRUD operations for all entities
- Secure authentication and authorization
- Responsive design and modern UI
- Comprehensive database integration

---

**Note**: This is a production-ready LMS system. Always backup your data before making changes and test thoroughly in a development environment first.

