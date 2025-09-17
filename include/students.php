<?php
class Students {
    private $mydb;

    public function __construct() {
        $this->mydb = new Database();
    }

    // Student Management
    public function getTotalStudents($search = '', $status = '') {
        $sql = "SELECT COUNT(DISTINCT s.id) as total 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.status != 'deleted'";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($status)) {
            $sql .= " AND u.status = ?";
            $params[] = $status === 'active' ? 'active' : 'inactive';
        }
        
        $stmt = $this->mydb->prepare($sql);
        if (!empty($params)) {
            $this->mydb->execute_prepared($stmt, $params);
        } else {
            $this->mydb->execute_prepared($stmt);
        }
        
        $result = $this->mydb->get_result($stmt)->fetch_object();
        return $result->total ?? 0;
    }
    
    public function getAllStudents($search = '', $status = '', $offset = 0, $limit = 10) {
        // First, get the student IDs with pagination
        $sql = "SELECT s.id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.status != 'deleted'";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($status)) {
            $sql .= " AND u.status = ?";
            $params[] = $status === 'active' ? 'active' : 'inactive';
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        // Add pagination
        if ($limit > 0) {
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$offset;
            $params[] = (int)$limit;
        }
        
        // Execute the query to get student IDs
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, $params);
        $result = $this->mydb->get_result($stmt);
        $studentIds = [];
        while ($row = $result->fetch_assoc()) {
            $studentIds[] = $row['id'];
        }
        $result->free();
        
        if (empty($studentIds)) {
            return [];
        }
        
        // Now get full student data with course info in a separate query
        $placeholders = rtrim(str_repeat('?,', count($studentIds)), ',');
        $sql = "SELECT 
                    s.*,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.status as user_status,
                    COUNT(ce.id) as enrolled_courses,
                    COALESCE(AVG(ce.progress_percentage), 0) as avg_progress
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN course_enrollments ce ON s.id = ce.student_id AND ce.status = 'enrolled'
                WHERE s.id IN ($placeholders)
                GROUP BY s.id
                ORDER BY FIELD(s.id, " . implode(',', $studentIds) . ")";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, $studentIds);
        $result = $this->mydb->get_result($stmt);
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $result->free();
        
        return $students;
    }

    public function getStudentById($studentId) {
        // First, get student basic info
        $sql = "SELECT 
                    s.*,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.status as user_status
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? AND u.status != 'deleted'";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$studentId]);
        $result = $this->mydb->get_result($stmt);
        $student = $result->fetch_object();
        
        // Free the result set
        $result->free();
        
        if ($student) {
            // Get enrolled courses in a separate query to avoid sync issues
            $sql = "SELECT 
                        c.*,
                        ce.enrollment_date,
                        ce.progress_percentage,
                        ce.status as enrollment_status
                    FROM course_enrollments ce
                    JOIN courses c ON ce.course_id = c.id
                    WHERE ce.student_id = ? AND ce.status = 'enrolled'
                    ORDER BY ce.enrollment_date DESC";
            
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$studentId]);
            $enrolledCourses = $this->mydb->get_result($stmt);
            
            $student->enrolled_courses = $enrolledCourses;
        }
        
        return $student;
    }

    public function createStudent($data) {
        // Start transaction
        $this->mydb->conn->begin_transaction();
        
        try {
            // Check if username already exists
            $checkUsername = $this->mydb->prepare("SELECT id FROM users WHERE username = ? AND status != 'deleted'");
            $this->mydb->execute_prepared($checkUsername, [$data['username']]);
            $usernameResult = $this->mydb->get_result($checkUsername);
            
            if ($usernameResult->num_rows > 0) {
                $usernameResult->free();
                throw new Exception('Username already exists');
            }
            $usernameResult->free();
            
            // Check if email already exists
            $checkEmail = $this->mydb->prepare("SELECT id FROM users WHERE email = ? AND status != 'deleted'");
            $this->mydb->execute_prepared($checkEmail, [$data['email']]);
            $emailResult = $this->mydb->get_result($checkEmail);
            
            if ($emailResult->num_rows > 0) {
                $emailResult->free();
                throw new Exception('Email already exists');
            }
            $emailResult->free();
            
            // Create user account
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $userStmt = $this->mydb->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, user_type, status) 
                VALUES (?, ?, ?, ?, ?, 'Student', 'active')
            ");
            
            $this->mydb->execute_prepared($userStmt, [
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name']
            ]);
            
            $userId = $this->mydb->insert_id();
            
            // Create student profile
            $studentStmt = $this->mydb->prepare("
                INSERT INTO students (user_id, student_id, phone, address, date_of_birth, gender) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Generate student ID (you can customize this format)
            $studentId = 'STU' . str_pad($userId, 5, '0', STR_PAD_LEFT);
            
            $this->mydb->execute_prepared($studentStmt, [
                $userId,
                $studentId,
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null
            ]);
            
            $studentDbId = $this->mydb->insert_id();
            
            // Enroll in courses if specified
            if (!empty($data['courses']) && is_array($data['courses'])) {
                $enrollStmt = $this->mydb->prepare("
                    INSERT INTO course_enrollments (student_id, course_id, status) 
                    VALUES (?, ?, 'enrolled')
                ");
                
                foreach ($data['courses'] as $courseId) {
                    $this->mydb->execute_prepared($enrollStmt, [$studentDbId, $courseId]);
                    $this->mydb->get_result($enrollStmt); // Consume the result
                }
                $enrollStmt->close();
            }
            
            $this->mydb->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->conn->rollback();
            throw $e;
        }
    }

    public function updateStudent($studentId, $data) {
        // Start transaction
        $this->mydb->conn->begin_transaction();
        
        try {
            // Get user ID for this student
            $stmt = $this->mydb->prepare("SELECT user_id FROM students WHERE id = ?");
            $this->mydb->execute_prepared($stmt, [$studentId]);
            $result = $this->mydb->get_result($stmt);
            $student = $result->fetch_object();
            $result->free();
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            $userId = $student->user_id;
            
            // Check if email already exists (excluding current user)
            if (!empty($data['email'])) {
                $checkEmail = $this->mydb->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND status != 'deleted'");
                $this->mydb->execute_prepared($checkEmail, [$data['email'], $userId]);
                $emailResult = $this->mydb->get_result($checkEmail);
                
                if ($emailResult->num_rows > 0) {
                    $emailResult->free();
                    throw new Exception('Email already exists');
                }
                $emailResult->free();
            }
            
            // Update user account
            $userStmt = $this->mydb->prepare("
                UPDATE users 
                SET email = ?, first_name = ?, last_name = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $this->mydb->execute_prepared($userStmt, [
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $userId
            ]);
            
            // Update student profile
            $studentStmt = $this->mydb->prepare("
                UPDATE students 
                SET phone = ?, address = ?, date_of_birth = ?, gender = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $this->mydb->execute_prepared($studentStmt, [
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $studentId
            ]);
            
            // Update course enrollments if specified
            if (isset($data['courses']) && is_array($data['courses'])) {
                // Remove existing enrollments
                $deleteStmt = $this->mydb->prepare("DELETE FROM course_enrollments WHERE student_id = ?");
                $this->mydb->execute_prepared($deleteStmt, [$studentId]);
                $this->mydb->get_result($deleteStmt); // Consume the result
                $deleteStmt->close();
                
                // Add new enrollments
                if (!empty($data['courses'])) {
                    $enrollStmt = $this->mydb->prepare("
                        INSERT INTO course_enrollments (student_id, course_id, status) 
                        VALUES (?, ?, 'enrolled')
                    ");
                    
                    foreach ($data['courses'] as $courseId) {
                        $this->mydb->execute_prepared($enrollStmt, [$studentId, $courseId]);
                        $this->mydb->get_result($enrollStmt); // Consume the result
                    }
                    $enrollStmt->close();
                }
            }
            
            $this->mydb->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->conn->rollback();
            throw $e;
        }
    }

    public function deleteStudent($studentId) {
        // Start transaction
        $this->mydb->conn->begin_transaction();
        
        try {
            // Get user ID for this student
            $stmt = $this->mydb->prepare("SELECT user_id FROM students WHERE id = ?");
            $this->mydb->execute_prepared($stmt, [$studentId]);
            $student = $this->mydb->get_result($stmt)->fetch_object();
            
            if (!$student) {
                throw new Exception('Student not found');
            }
            
            $userId = $student->user_id;
            
            // Check if student has active course enrollments
            $stmt = $this->mydb->prepare("SELECT COUNT(*) as count FROM course_enrollments WHERE student_id = ? AND status = 'enrolled'");
            $this->mydb->execute_prepared($stmt, [$studentId]);
        $result = $this->mydb->get_result($stmt)->fetch_object();
        
            if ($result->count > 0) {
                throw new Exception('Cannot delete student with active course enrollments. Please drop all courses first.');
            }
            
            // Soft delete user account
            $stmt = $this->mydb->prepare("UPDATE users SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $this->mydb->execute_prepared($stmt, [$userId]);
            
            // Drop all course enrollments
            $stmt = $this->mydb->prepare("UPDATE course_enrollments SET status = 'dropped', updated_at = CURRENT_TIMESTAMP WHERE student_id = ?");
            $this->mydb->execute_prepared($stmt, [$studentId]);
            
            $this->mydb->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->conn->rollback();
            throw $e;
        }
    }

    // Course Enrollment Management
    public function enrollStudentInCourse($studentId, $courseId) {
        $sql = "INSERT INTO course_enrollments (student_id, course_id, status, progress_percentage) 
                VALUES (?, ?, 'enrolled', 0.00) 
                ON DUPLICATE KEY UPDATE status = 'enrolled'";
        
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$studentId, $courseId]);
    }

    public function getStudentCourses($studentId) {
        $sql = "SELECT 
                    ce.*,
                    c.name as course_name,
                    c.description as course_description,
                    c.duration_weeks,
                    c.level
                FROM course_enrollments ce
                LEFT JOIN courses c ON ce.course_id = c.id
                WHERE ce.student_id = ? AND ce.status = 'enrolled'
                ORDER BY ce.enrollment_date DESC";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$studentId]);
        return $this->mydb->get_result($stmt);
    }

    public function updateStudentProgress($studentId, $courseId, $progressPercentage) {
        $sql = "UPDATE course_enrollments 
                SET progress_percentage = ?, updated_at = CURRENT_TIMESTAMP
                WHERE student_id = ? AND course_id = ?";
        
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$progressPercentage, $studentId, $courseId]);
    }

    // Dashboard Statistics
    public function getStudentStats() {
        $stats = [];
        
        // Total students
        $this->mydb->setQuery("SELECT COUNT(*) as count FROM students s JOIN users u ON s.user_id = u.id WHERE u.status != 'deleted'");
        $result = $this->mydb->loadSingleResult();
        $stats['total_students'] = $result ? $result->count : 0;
        
        // Active students (enrolled in at least one course)
        $this->mydb->setQuery("SELECT COUNT(DISTINCT student_id) as count FROM course_enrollments WHERE status = 'enrolled'");
        $result = $this->mydb->loadSingleResult();
        $stats['active_students'] = $result ? $result->count : 0;
        
        // Average progress
        $this->mydb->setQuery("SELECT AVG(progress_percentage) as avg_progress FROM course_enrollments WHERE status = 'enrolled'");
        $result = $this->mydb->loadSingleResult();
        $stats['avg_progress'] = $result ? round($result->avg_progress, 2) : 0;
        
        return $stats;
    }
}
?>

