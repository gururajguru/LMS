<?php
/**
 * Tests Management Class
 * Handles all test-related operations
 */

class Test {
    private $mydb;

    public function __construct() {
        $this->mydb = new Database();
    }

    public function getAllTests($courseId = null, $search = '', $offset = 0, $limit = 10) {
        $sql = "SELECT t.*, c.name as course_name 
                FROM tests t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($courseId)) {
            $sql .= " AND t.course_id = ?";
            $params[] = (int)$courseId;
        }
        
        if (!empty($search)) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$offset;
            $params[] = (int)$limit;
        }
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, $params);
        return $this->mydb->get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function addTest($data) {
        // Start transaction
        $this->mydb->begin_transaction();
        
        try {
            error_log('Starting to add test with data: ' . print_r($data, true));
            
            // Insert test data
            $sql = "INSERT INTO tests (
                course_id, title, description, duration_minutes, 
                passing_score, max_attempts, instructions, status,
                show_correct_answers, randomize_questions, randomize_answers,
                show_progress, allow_navigation, time_limit_enabled,
                pass_message, fail_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['course_id'] ?? 0,
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['duration_minutes'] ?? 30,
                $data['passing_score'] ?? 70,
                $data['max_attempts'] ?? 3,
                $data['instructions'] ?? '',
                $data['status'] ?? 'active',
                $data['show_correct_answers'] ?? 0,
                $data['randomize_questions'] ?? 0,
                $data['randomize_answers'] ?? 0,
                $data['show_progress'] ?? 0,
                $data['allow_navigation'] ?? 0,
                $data['time_limit_enabled'] ?? 0,
                $data['pass_message'] ?? '',
                $data['fail_message'] ?? ''
            ];
            
            $stmt = $this->mydb->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->mydb->error);
            }
            
            $result = $this->mydb->execute_prepared($stmt, $params);
            if (!$result) {
                throw new Exception("Failed to execute statement: " . $this->mydb->error);
            }
            
            $testId = $this->mydb->insert_id();
            if (!$testId) {
                throw new Exception("Failed to get insert ID: " . $this->mydb->error);
            }
            
            error_log("Test created with ID: " . $testId);
            
            // If there are questions, add them
            if (!empty($data['questions']) && is_array($data['questions'])) {
                error_log("Adding questions: " . count($data['questions']));
                $this->addQuestions($testId, $data['questions']);
            } else {
                error_log("No questions to add");
            }
            
            $this->mydb->commit();
            return $testId;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error adding test: " . $e->getMessage());
            return false;
        }
    }
    
    private function addQuestions($testId, $questions) {
        $sql = "INSERT INTO test_questions (
            test_id, question_text, question_type, points, 
            option_a, option_b, option_c, option_d, correct_answer, explanation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->mydb->prepare($sql);
        
        foreach ($questions as $question) {
            $params = [
                $testId,
                $question['question_text'],
                $question['question_type'] ?? 'multiple_choice',
                $question['points'] ?? 1,
                $question['option_a'] ?? '',
                $question['option_b'] ?? '',
                $question['option_c'] ?? '',
                $question['option_d'] ?? '',
                $question['correct_answer'] ?? '',
                $question['explanation'] ?? ''
            ];
            
            $this->mydb->execute_prepared($stmt, $params);
        }
    }
    
    public function getTestById($testId) {
        $sql = "SELECT t.*, c.name as course_name 
                FROM tests t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.id = ?";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [(int)$testId]);
        return $this->mydb->get_result($stmt)->fetch_assoc();
    }

    public function updateTest($data) {
        if (empty($data['id'])) {
            throw new Exception("Test ID is required for update");
        }
        
        // Start transaction
        $this->mydb->begin_transaction();
        
        try {
            // Update test data
            $sql = "UPDATE tests SET 
                    course_id = ?,
                    title = ?,
                    description = ?,
                    duration_minutes = ?,
                    passing_score = ?,
                    max_attempts = ?,
                    instructions = ?,
                    status = ?,
                    show_correct_answers = ?,
                    randomize_questions = ?,
                    randomize_answers = ?,
                    show_progress = ?,
                    allow_navigation = ?,
                    time_limit_enabled = ?,
                    pass_message = ?,
                    fail_message = ?,
                    updated_at = NOW()
                WHERE id = ?";
            
            $params = [
                $data['course_id'],
                $data['title'],
                $data['description'] ?? '',
                $data['duration_minutes'] ?? 30,
                $data['passing_score'] ?? 70,
                $data['max_attempts'] ?? 3,
                $data['instructions'] ?? '',
                $data['status'] ?? 'active',
                $data['show_correct_answers'] ?? 0,
                $data['randomize_questions'] ?? 0,
                $data['randomize_answers'] ?? 0,
                $data['show_progress'] ?? 0,
                $data['allow_navigation'] ?? 0,
                $data['time_limit_enabled'] ?? 0,
                $data['pass_message'] ?? '',
                $data['fail_message'] ?? '',
                $data['id']
            ];
            
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, $params);
            
            // Delete existing questions
            $this->deleteQuestions($data['id']);
            
            // Add updated questions if any
            if (!empty($data['questions'])) {
                $this->addQuestions($data['id'], $data['questions']);
            }
            
            $this->mydb->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error updating test: " . $e->getMessage());
            return false;
        }
    }
    
    private function deleteQuestions($testId) {
        $sql = "DELETE FROM test_questions WHERE test_id = ?";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [$testId]);
    }
    
    public function getError() {
        return $this->mydb->error ?? 'An unknown error occurred';
    }

  public function deleteTest($testId) {
        $sql = "DELETE FROM tests WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [(int)$testId]);
    }

    public function getTotalTests($courseId = null, $search = '') {
        $sql = "SELECT COUNT(*) as total FROM tests t WHERE 1=1";
        $params = [];
        
        if (!empty($courseId)) {
            $sql .= " AND t.course_id = ?";
            $params[] = (int)$courseId;
        }
        
        if (!empty($search)) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
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
}
?>
