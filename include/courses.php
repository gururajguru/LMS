<?php
/**
 * Course Management Class
 * Handles all course-related operations including lessons, topics, quizzes, and tests
 */

class Courses {
    private $mydb;

    public function __construct() {
        $this->mydb = new Database();
    }

    // Course Management
    public function getAllCourses() {
        $sql = "SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as enrollment_count,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                    (SELECT COUNT(DISTINCT lp.lesson_id) 
                     FROM lesson_progress lp 
                     JOIN lessons l ON l.id = lp.lesson_id 
                     WHERE l.course_id = c.id AND lp.status = 'completed') as completed_lessons
                FROM courses c 
                WHERE c.status = 'active' 
                ORDER BY c.name ASC";
        
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResultList();
        
        $courses = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $total_lessons = $row->total_lessons ?? 0;
                $completed_lessons = $row->completed_lessons ?? 0;
                
                $course = [
                    'id' => $row->id,
                    'name' => $row->name ?? 'Untitled Course',
                    'code' => $row->code ?? '',
                    'description' => $row->description ?? '',
                    'duration_weeks' => $row->duration_weeks ?? 8,
                    'level' => $row->level ?? 'beginner',
                    'status' => $row->status ?? 'active',
                    'enrollment_count' => $row->enrollment_count ?? 0,
                    'total_lessons' => $total_lessons,
                    'completed_lessons' => $completed_lessons,
                    'progress' => $total_lessons > 0 ? min(100, round(($completed_lessons / $total_lessons) * 100)) : 0
                ];
                $courses[] = (object)$course; // Convert to object for consistency
            }
        }
        
        return $courses;
        
        return $courses;
    }

    public function getCourseById($courseId) {
        $sql = "SELECT * FROM courses WHERE id = ? AND status = 'active'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function createCourse($data) {
        $sql = "INSERT INTO courses (name, code, description, duration_weeks, level, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['name'],
            $data['code'],
            $data['description'],
            $data['duration_weeks'],
            $data['level'],
            'active'
        ]);
    }

    public function updateCourse($courseId, $data) {
        $sql = "UPDATE courses SET 
                name = ?, 
                code = ?, 
                description = ?, 
                duration_weeks = ?, 
                level = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['name'],
            $data['code'],
            $data['description'],
            $data['duration_weeks'],
            $data['level'],
            $courseId
        ]);
    }

    public function getTotalCourses($search = '', $status = '') {
        $params = [];
        $sql = "SELECT COUNT(*) as total FROM courses WHERE 1=1";
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR code LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND status = 'active'";
        }
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, $params);
        $result = $this->mydb->get_result($stmt);
        $row = $result->fetch_assoc();
        
        return $row ? (int)$row['total'] : 0;
    }
    
    public function deleteCourse($courseId) {
        // Soft delete - update status instead of removing
        $sql = "UPDATE courses SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$courseId]);
    }

    public function getCourseByCode($code) {
        $sql = "SELECT * FROM courses WHERE code = ? AND status = 'active'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$code]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    /**
     * Get all students enrolled in a course
     * 
     * @param int $courseId The ID of the course
     * @return array Array of enrolled students with their details
     */
    /**
     * Get all students enrolled in a course with additional progress information
     * 
     * @param int $courseId The ID of the course
     * @return array Array of enrolled students with their details and progress
     */
    public function getEnrolledStudents($courseId) {
        $sql = "SELECT 
                    s.id,
                    s.student_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.status,
                    ce.enrollment_date,
                    ce.progress_percentage,
                    ce.status as enrollment_status,
                    (SELECT COUNT(*) FROM lesson_progress lp 
                     WHERE lp.student_id = s.id AND lp.status = 'completed' 
                     AND lp.lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)) as completed_lessons,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = ?) as total_lessons
                FROM course_enrollments ce
                JOIN students s ON ce.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE ce.course_id = ? AND ce.status = 'enrolled'
                ORDER BY u.first_name, u.last_name ASC";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId, $courseId, $courseId]);
        return $this->mydb->loadResultList();
    }

    /**
     * Get course statistics including enrollment count and progress
     * 
     * @param int $courseId The ID of the course
     * @return array Course statistics
     */
    public function getCourseStatistics($courseId) {
        $stats = [
            'total_students' => 0,
            'active_students' => 0,
            'completion_rate' => 0,
            'avg_progress' => 0,
            'total_lessons' => 0,
            'completed_lessons' => 0
        ];

        // Get total and active students
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active
                FROM course_enrollments ce
                JOIN users u ON u.id = (SELECT user_id FROM students WHERE id = ce.student_id)
                WHERE ce.course_id = ? AND ce.status = 'enrolled'";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        
        if ($result) {
            $stats['total_students'] = (int)$result['total'];
            $stats['active_students'] = (int)$result['active'];
        }

        // Get average progress
        $sql = "SELECT AVG(progress_percentage) as avg_progress 
                FROM course_enrollments 
                WHERE course_id = ? AND status = 'enrolled'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        $stats['avg_progress'] = $result ? round((float)$result['avg_progress'], 2) : 0;

        // Get completion rate (percentage of students with 100% progress)
        $sql = "SELECT 
                    (COUNT(CASE WHEN progress_percentage >= 100 THEN 1 END) * 100.0 / 
                    NULLIF(COUNT(*), 0)) as completion_rate
                FROM course_enrollments 
                WHERE course_id = ? AND status = 'enrolled'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        $stats['completion_rate'] = $result ? round((float)$result['completion_rate'], 2) : 0;

        // Get lesson statistics
        $sql = "SELECT 
                    COUNT(*) as total_lessons,
                    (SELECT COUNT(*) FROM lesson_progress lp 
                     JOIN lessons l ON l.id = lp.lesson_id 
                     WHERE l.course_id = ? AND lp.status = 'completed') as completed_lessons
                FROM lessons 
                WHERE course_id = ?";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId, $courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        
        if ($result) {
            $stats['total_lessons'] = (int)$result['total_lessons'];
            $stats['completed_lessons'] = (int)$result['completed_lessons'];
        }

        return $stats;
    }

    /**
     * Get all courses with additional statistics for the dashboard
     * 
     * @return array List of courses with statistics
     */
    public function getDashboardCourses() {
        $sql = "SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND status = 'enrolled') as enrollment_count,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count,
                    (SELECT COUNT(*) FROM quizzes WHERE course_id = c.id) as quiz_count,
                    (SELECT COUNT(*) FROM tests WHERE course_id = c.id) as test_count,
                    (SELECT AVG(progress_percentage) FROM course_enrollments WHERE course_id = c.id) as avg_progress
                FROM courses c
                WHERE c.status = 'active'
                ORDER BY c.name ASC";
        
        $this->mydb->setQuery($sql);
        $courses = $this->mydb->loadResultList();
        
        // Calculate additional statistics for each course
        foreach ($courses as &$course) {
            $course['completion_rate'] = $this->getCourseCompletionRate($course['id']);
            $course['recent_activity'] = $this->getRecentCourseActivity($course['id']);
        }
        
        return $courses;
    }

    /**
     * Get completion rate for a course
     * 
     * @param int $courseId The ID of the course
     * @return float Completion rate percentage
     */
    private function getCourseCompletionRate($courseId) {
        $sql = "SELECT 
                    (COUNT(CASE WHEN progress_percentage >= 100 THEN 1 END) * 100.0 / 
                    NULLIF(COUNT(*), 0)) as completion_rate
                FROM course_enrollments 
                WHERE course_id = ? AND status = 'enrolled'";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        
        return $result ? round((float)$result['completion_rate'], 2) : 0;
    }

    /**
     * Get recent activity for a course
     * 
     * @param int $courseId The ID of the course
     * @return array Recent activity data
     */
    private function getRecentCourseActivity($courseId) {
        $sql = "(
                    SELECT 'enrollment' as type, ce.enrollment_date as activity_date, 
                           CONCAT(u.first_name, ' ', u.last_name) as user_name,
                           'Enrolled in course' as description
                    FROM course_enrollments ce
                    JOIN students s ON s.id = ce.student_id
                    JOIN users u ON u.id = s.user_id
                    WHERE ce.course_id = ?
                    ORDER BY ce.enrollment_date DESC
                    LIMIT 5
                )
                UNION ALL
                (
                    SELECT 'lesson_completion' as type, lp.completed_at as activity_date,
                           CONCAT(u.first_name, ' ', u.last_name) as user_name,
                           CONCAT('Completed lesson: ', l.title) as description
                    FROM lesson_progress lp
                    JOIN lessons l ON l.id = lp.lesson_id
                    JOIN students s ON s.id = lp.student_id
                    JOIN users u ON u.id = s.user_id
                    WHERE l.course_id = ? AND lp.status = 'completed'
                    ORDER BY lp.completed_at DESC
                    LIMIT 5
                )
                ORDER BY activity_date DESC
                LIMIT 10";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId, $courseId]);
        return $this->mydb->loadResultList();
    }
    
    /**
     * Get course statistics including student count and average progress
     * 
     * @param int $courseId The ID of the course
     * @return array Course statistics
     */
    public function getCourseStats($courseId) {
        $stats = [
            'total_students' => 0,
            'avg_progress' => 0,
            'active_students' => 0,
            'completed_students' => 0
        ];
        
        // Get total enrolled students
        $sql = "SELECT COUNT(*) as total FROM course_enrollments 
                WHERE course_id = ? AND status = 'enrolled'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        $result = $this->mydb->get_result($stmt)->fetch_assoc();
        $stats['total_students'] = (int)$result['total'];
        
        if ($stats['total_students'] > 0) {
            // Get average progress
            $sql = "SELECT AVG(progress_percentage) as avg_progress 
                    FROM course_enrollments 
                    WHERE course_id = ? AND status = 'enrolled'";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$courseId]);
            $result = $this->mydb->get_result($stmt)->fetch_assoc();
            $stats['avg_progress'] = round((float)$result['avg_progress'], 2);
            
            // Get active students (progress > 0%)
            $sql = "SELECT COUNT(*) as active_count FROM course_enrollments 
                    WHERE course_id = ? AND status = 'enrolled' AND progress_percentage > 0";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$courseId]);
            $result = $this->mydb->get_result($stmt)->fetch_assoc();
            $stats['active_students'] = (int)$result['active_count'];
            
            // Get completed students (progress = 100%)
            $sql = "SELECT COUNT(*) as completed_count FROM course_enrollments 
                    WHERE course_id = ? AND status = 'enrolled' AND progress_percentage = 100";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$courseId]);
            $result = $this->mydb->get_result($stmt)->fetch_assoc();
            $stats['completed_students'] = (int)$result['completed_count'];
        }
        
        return $stats;
    }
    
    /**
     * Check if a student is enrolled in a course
     * 
     * @param int $studentId The ID of the student
     * @param int $courseId The ID of the course
     * @return bool True if enrolled, false otherwise
     */
    public function isStudentEnrolled($studentId, $courseId) {
        $sql = "SELECT id FROM course_enrollments 
                WHERE student_id = ? AND course_id = ? AND status = 'enrolled'";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$studentId, $courseId]);
        return $this->mydb->num_rows() > 0;
    }

    // Lesson Management
    public function getCourseLessons($courseId) {
        $sql = "SELECT * FROM lessons WHERE course_id = ? AND status = 'active' ORDER BY order_number ASC";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        return $this->mydb->get_result($stmt);
    }

    public function getAllLessons() {
        $sql = "SELECT 
                    l.*,
                    c.name as course_name,
                    c.code as course_code
                FROM lessons l
                JOIN courses c ON l.course_id = c.id
                WHERE l.status = 'active' AND c.status = 'active'
                ORDER BY c.name, l.order_number ASC";
        
        $this->mydb->setQuery($sql);
        return $this->mydb->loadResultList();
    }

    public function getLessonById($lessonId) {
        $sql = "SELECT 
                    l.*,
                    c.name as course_name,
                    c.code as course_code
                FROM lessons l
                JOIN courses c ON l.course_id = c.id
                WHERE l.id = ? AND l.status = 'active'";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$lessonId]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function createLesson($data) {
        $sql = "INSERT INTO lessons (course_id, title, description, content, video_url, order_number, duration_minutes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['course_id'],
            $data['title'],
            $data['description'],
            $data['content'],
            $data['video_url'] ?? null,
            $data['order_number'],
            $data['duration_minutes'] ?? 45,
            'active'
        ]);
    }

    public function updateLesson($lessonId, $data) {
        $sql = "UPDATE lessons SET 
                title = ?, 
                description = ?, 
                content = ?, 
                video_url = ?, 
                order_number = ?, 
                duration_minutes = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['title'],
            $data['description'],
            $data['content'],
            $data['video_url'] ?? null,
            $data['order_number'],
            $data['duration_minutes'] ?? 45,
            $lessonId
        ]);
    }

    public function deleteLesson($lessonId) {
        // Soft delete
        $sql = "UPDATE lessons SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$lessonId]);
    }

    // Test Management
    public function getCourseTests($courseId) {
        $sql = "SELECT * FROM tests WHERE course_id = ? AND status = 'active' ORDER BY title ASC";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        return $this->mydb->get_result($stmt);
    }

    public function getAllTests() {
        $sql = "SELECT 
                    t.*,
                    c.name as course_name,
                    c.code as course_code
                FROM tests t
                JOIN courses c ON t.course_id = c.id
                WHERE t.status = 'active' AND c.status = 'active'
                ORDER BY c.name, t.title ASC";
        
        $this->mydb->setQuery($sql);
        return $this->mydb->loadResultList();
    }

    public function getTestById($testId) {
        $sql = "SELECT 
                    t.*,
                    c.name as course_name,
                    c.code as course_code
                FROM tests t
                JOIN courses c ON t.course_id = c.id
                WHERE t.id = ? AND t.status = 'active'";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$testId]);
        return $this->mydb->get_result($stmt)->fetch_object();
    }

    public function createTest($data) {
        $sql = "INSERT INTO tests (course_id, title, description, duration_minutes, passing_score, max_attempts, instructions, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['course_id'],
            $data['title'],
            $data['description'],
            $data['duration_minutes'] ?? 30,
            $data['passing_score'] ?? 70.00,
            $data['max_attempts'] ?? 3,
            $data['instructions'] ?? null,
            'active'
        ]);
    }

    public function updateTest($testId, $data) {
        $sql = "UPDATE tests SET 
                title = ?, 
                description = ?, 
                duration_minutes = ?, 
                passing_score = ?, 
                max_attempts = ?, 
                instructions = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [
            $data['title'],
            $data['description'],
            $data['duration_minutes'] ?? 30,
            $data['passing_score'] ?? 70.00,
            $data['max_attempts'] ?? 3,
            $data['instructions'] ?? null,
            $testId
        ]);
    }

    public function deleteTest($testId) {
        // Soft delete
        $sql = "UPDATE tests SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$testId]);
    }

    // Enrollment Management
    public function enrollStudent($studentId, $courseId) {
        $sql = "INSERT INTO course_enrollments (student_id, course_id, status, progress_percentage) 
                VALUES (?, ?, 'enrolled', 0.00) 
                ON DUPLICATE KEY UPDATE status = 'enrolled'";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [$studentId, $courseId]);
    }

    public function getStudentCourses($studentId) {
        try {
            $sql = "SELECT c.* FROM courses c 
                    INNER JOIN course_enrollments ce ON c.id = ce.course_id 
                    WHERE ce.student_id = ? AND c.status = 'active' 
                    ORDER BY c.name ASC";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$studentId]);
            $result = $this->mydb->get_result($stmt);
            
            $courses = [];
            while ($row = $result->fetch_object()) {
                $courses[] = $row;
            }
            
            return $courses;
        } catch (Exception $e) {
            throw new Exception('Error fetching student courses: ' . $e->getMessage());
        }
    }

    public function getCourseStudents($courseId) {
        $sql = "SELECT ce.*, s.first_name, s.last_name, s.student_id 
                FROM course_enrollments ce 
                LEFT JOIN students s ON ce.student_id = s.id 
                WHERE ce.course_id = ? 
                ORDER BY s.last_name, s.first_name";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$courseId]);
        return $this->mydb->get_result($stmt);
    }

    // Dashboard Statistics
    public function getDashboardStats() {
        $stats = [];
        
        // Total courses with active status
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
                FROM courses";
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResult();
        $stats['total_courses'] = $result ? (int)$result->total : 0;
        $stats['active_courses'] = $result ? (int)$result->active : 0;
        $stats['draft_courses'] = $result ? (int)$result->draft : 0;
        $stats['archived_courses'] = $result ? (int)$result->archived : 0;
        
        // Student statistics
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    (SELECT COUNT(DISTINCT student_id) FROM course_enrollments WHERE status = 'enrolled') as enrolled
                FROM students s
                JOIN users u ON s.user_id = u.id";
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResult();
        $stats['total_students'] = $result ? (int)$result->total : 0;
        $stats['active_students'] = $result ? (int)$result->active : 0;
        $stats['inactive_students'] = $result ? (int)$result->inactive : 0;
        $stats['enrolled_students'] = $result ? (int)$result->enrolled : 0;
        
        // Lesson and content statistics
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM lessons WHERE status = 'active') as total_lessons,
                    (SELECT COUNT(*) FROM topics WHERE status = 'active') as total_topics,
                    (SELECT COUNT(*) FROM quizzes WHERE status = 'active') as total_quizzes,
                    (SELECT COUNT(*) FROM tests WHERE status = 'active') as total_tests";
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResult();
        $stats['total_lessons'] = $result ? (int)$result->total_lessons : 0;
        $stats['total_topics'] = $result ? (int)$result->total_topics : 0;
        $stats['total_quizzes'] = $result ? (int)$result->total_quizzes : 0;
        $stats['total_tests'] = $result ? (int)$result->total_tests : 0;
        
        // Enrollment statistics
        $sql = "SELECT 
                    COUNT(*) as total_enrollments,
                    SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as active_enrollments,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments,
                    SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped_enrollments,
                    (SELECT COUNT(DISTINCT student_id) FROM course_enrollments WHERE status = 'enrolled') as unique_students,
                    (SELECT COUNT(DISTINCT course_id) FROM course_enrollments WHERE status = 'enrolled') as active_courses
                FROM course_enrollments";
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResult();
        $stats['total_enrollments'] = $result ? (int)$result->total_enrollments : 0;
        $stats['active_enrollments'] = $result ? (int)$result->active_enrollments : 0;
        $stats['completed_enrollments'] = $result ? (int)$result->completed_enrollments : 0;
        $stats['dropped_enrollments'] = $result ? (int)$result->dropped_enrollments : 0;
        $stats['unique_enrolled_students'] = $result ? (int)$result->unique_students : 0;
        $stats['active_courses_count'] = $result ? (int)$result->active_courses : 0;
        
        // Course progress statistics
        $sql = "SELECT 
                    COUNT(*) as total_courses,
                    AVG(avg_progress) as avg_course_progress,
                    SUM(total_students) as total_students,
                    SUM(completed_students) as total_completed
                FROM (
                    SELECT 
                        c.id,
                        COUNT(ce.id) as total_students,
                        SUM(CASE WHEN ce.progress_percentage >= 100 THEN 1 ELSE 0 END) as completed_students,
                        AVG(ce.progress_percentage) as avg_progress
                    FROM courses c
                    LEFT JOIN course_enrollments ce ON ce.course_id = c.id AND ce.status = 'enrolled'
                    WHERE c.status = 'active'
                    GROUP BY c.id
                ) as course_stats";
        $this->mydb->setQuery($sql);
        $result = $this->mydb->loadResult();
        $stats['avg_course_progress'] = $result ? round((float)$result->avg_course_progress, 2) : 0;
        $stats['total_enrolled_students'] = $result ? (int)$result->total_students : 0;
        $stats['total_completed_students'] = $result ? (int)$result->total_completed : 0;
        $stats['completion_rate'] = $result && $result->total_students > 0 ? 
            round(($result->total_completed / $result->total_students) * 100, 2) : 0;
        
        // Recent enrollments with more details
        $sql = "SELECT 
                    ce.*, 
                    c.name as course_name, 
                    c.code as course_code,
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    u.email as student_email,
                    s.student_id as student_number,
                    DATEDIFF(NOW(), ce.enrollment_date) as days_enrolled,
                    ce.progress_percentage
                FROM course_enrollments ce
                JOIN courses c ON c.id = ce.course_id
                JOIN students s ON s.id = ce.student_id
                JOIN users u ON u.id = s.user_id
                WHERE ce.status = 'enrolled'
                ORDER BY ce.enrollment_date DESC 
                LIMIT 5";
        $this->mydb->setQuery($sql);
        $stats['recent_enrollments'] = $this->mydb->loadResultList();
        
        // Top courses by enrollment
        $sql = "SELECT 
                    c.id, 
                    c.name, 
                    c.code,
                    c.duration_weeks,
                    c.level,
                    COUNT(ce.id) as enrolled_students,
                    AVG(ce.progress_percentage) as avg_progress,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                    (SELECT COUNT(*) FROM quizzes WHERE course_id = c.id) as total_quizzes,
                    (SELECT COUNT(*) FROM tests WHERE course_id = c.id) as total_tests
                FROM courses c
                LEFT JOIN course_enrollments ce ON ce.course_id = c.id AND ce.status = 'enrolled'
                WHERE c.status = 'active'
                GROUP BY c.id, c.name, c.code, c.duration_weeks, c.level
                ORDER BY enrolled_students DESC, avg_progress DESC
                LIMIT 5";
        $this->mydb->setQuery($sql);
        $stats['top_courses'] = $this->mydb->loadResultList();
        
        // Course progress by level
        $sql = "SELECT 
                    c.level,
                    COUNT(DISTINCT c.id) as course_count,
                    COUNT(DISTINCT ce.student_id) as student_count,
                    AVG(ce.progress_percentage) as avg_progress,
                    SUM(CASE WHEN ce.progress_percentage >= 100 THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(ce.id), 0) as completion_rate
                FROM courses c
                LEFT JOIN course_enrollments ce ON ce.course_id = c.id AND ce.status = 'enrolled'
                WHERE c.status = 'active'
                GROUP BY c.level
                ORDER BY c.level";
        $this->mydb->setQuery($sql);
        $stats['progress_by_level'] = $this->mydb->loadResultList();
        
        // Recent course activities
        $sql = "(
                    SELECT 
                        'enrollment' as activity_type,
                        ce.enrollment_date as activity_date,
                        c.id as course_id,
                        c.name as course_name,
                        c.code as course_code,
                        s.id as student_id,
                        CONCAT(u.first_name, ' ', u.last_name) as student_name,
                        NULL as lesson_id,
                        NULL as lesson_title,
                        'Enrolled in course' as activity_description
                    FROM course_enrollments ce
                    JOIN courses c ON c.id = ce.course_id
                    JOIN students s ON s.id = ce.student_id
                    JOIN users u ON u.id = s.user_id
                    WHERE ce.status = 'enrolled'
                    ORDER BY ce.enrollment_date DESC
                    LIMIT 5
                )
                UNION ALL
                (
                    SELECT 
                        'lesson_completion' as activity_type,
                        lp.completed_at as activity_date,
                        l.course_id,
                        c.name as course_name,
                        c.code as course_code,
                        s.id as student_id,
                        CONCAT(u.first_name, ' ', u.last_name) as student_name,
                        l.id as lesson_id,
                        l.title as lesson_title,
                        CONCAT('Completed lesson: ', l.title) as activity_description
                    FROM lesson_progress lp
                    JOIN lessons l ON l.id = lp.lesson_id
                    JOIN courses c ON c.id = l.course_id
                    JOIN students s ON s.id = lp.student_id
                    JOIN users u ON u.id = s.user_id
                    WHERE lp.status = 'completed'
                    ORDER BY lp.completed_at DESC
                    LIMIT 5
                )
                ORDER BY activity_date DESC
                LIMIT 10";
        $this->mydb->setQuery($sql);
        $stats['recent_activities'] = $this->mydb->loadResultList();
        
        return $stats;
    }

    // Content Management
    public function uploadContent($data) {
        // This would need to be implemented based on your content storage needs
        // For now, we'll return true as a placeholder
        return true;
    }

    public function getContent($lessonId = null, $topicId = null) {
        if ($lessonId) {
            $sql = "SELECT * FROM lessons WHERE id = ? AND status = 'active'";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$lessonId]);
            return $this->mydb->get_result($stmt)->fetch_object();
        }
        return null;
    }

    // Quiz Methods
    public function createQuiz($data) {
        try {
            $sql = "INSERT INTO quizzes (course_id, title, description, question, option1, option2, option3, option4, correct_answer, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [
                $data['course_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['question'],
                $data['option1'],
                $data['option2'],
                $data['option3'] ?? null,
                $data['option4'] ?? null,
                $data['correct_answer']
            ]);
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Error creating quiz: ' . $e->getMessage());
        }
    }
    
    public function updateQuiz($id, $data) {
        try {
            $sql = "UPDATE quizzes SET 
                    course_id = ?, title = ?, description = ?, question = ?, 
                    option1 = ?, option2 = ?, option3 = ?, option4 = ?, 
                    correct_answer = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [
                $data['course_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['question'],
                $data['option1'],
                $data['option2'],
                $data['option3'] ?? null,
                $data['option4'] ?? null,
                $data['correct_answer'],
                $id
            ]);
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Error updating quiz: ' . $e->getMessage());
        }
    }
    
    public function getQuizById($id) {
        try {
            $sql = "SELECT * FROM quizzes WHERE id = ?";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$id]);
            $result = $this->mydb->get_result($stmt);
            
            return $result->fetch_object();
        } catch (Exception $e) {
            throw new Exception('Error fetching quiz: ' . $e->getMessage());
        }
    }
    
    public function getAllQuizzes() {
        try {
            $sql = "SELECT q.*, c.name as course_name FROM quizzes q 
                    LEFT JOIN courses c ON q.course_id = c.id 
                    ORDER BY q.created_at DESC";
            $this->mydb->setQuery($sql);
            return $this->mydb->loadResultList();
        } catch (Exception $e) {
            throw new Exception('Error fetching quizzes: ' . $e->getMessage());
        }
    }
    
    public function deleteQuiz($id) {
        try {
            $sql = "DELETE FROM quizzes WHERE id = ?";
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [$id]);
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Error deleting quiz: ' . $e->getMessage());
        }
    }
    
    public function getQuizzesByCourse($courseId) {
        try {
            $sql = "SELECT id, title, description, question, option1, option2, option3, option4 
                    FROM quizzes WHERE course_id = ? AND status = 'active' 
                    ORDER BY created_at ASC";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, [$courseId]);
            $result = $this->mydb->get_result($stmt);
            
            $quizzes = [];
            while ($row = $result->fetch_object()) {
                $quizzes[] = $row;
            }
            
            return $quizzes;
        } catch (Exception $e) {
            throw new Exception('Error fetching course quizzes: ' . $e->getMessage());
        }
    }
}
?>
