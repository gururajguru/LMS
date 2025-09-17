<?php
/**
 * Quizzes Management Class
 * Handles all quiz-related operations
 */

if (!class_exists('Quiz')) {
    
class Quiz {
    private $mydb;

    public function __construct() {
        $this->mydb = new Database();
    }

    /**
     * Get quizzes with filters and pagination
     * 
     * @param string $search Search term for quiz title/description
     * @param int|null $courseId Filter by course ID
     * @param string $status Filter by status (active/inactive)
     * @param int $offset Pagination offset
     * @param int $limit Number of records to return
     * @return array Array of quizzes with additional data
     */
    public function getAllQuizzes($search = '', $courseId = null, $status = '', $offset = 0, $limit = 10) {
        // Handle legacy parameter order (courseId, search, offset, limit)
        if (is_numeric($search) && $courseId === '') {
            // This handles the case where the first parameter is courseId
            $temp = $search;
            $search = '';
            $courseId = $temp;
        }
        
        $sql = "SELECT q.*, c.name as course_name, 
                       (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
                       (SELECT COUNT(DISTINCT user_id) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as attempt_count,
                       (SELECT AVG(score) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as average_score
                FROM quizzes q
                LEFT JOIN courses c ON q.course_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (q.title LIKE ? OR q.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($courseId)) {
            $sql .= " AND q.course_id = ?";
            $params[] = (int)$courseId;
        }
        
        if ($status === 'active') {
            $sql .= " AND q.status = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND q.status = 0";
        }
        
        $sql .= " ORDER BY q.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$offset;
            $params[] = (int)$limit;
        }
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, $params);
        return $this->mydb->get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function getQuizById($quizId) {
        $sql = "SELECT q.*, c.name as course_name 
                FROM quizzes q
                LEFT JOIN courses c ON q.course_id = c.id
                WHERE q.id = ?";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [(int)$quizId]);
        return $this->mydb->get_result($stmt)->fetch_assoc();
    }

    public function createQuiz($data) {
        $this->mydb->begin_transaction();
        
        try {
            $sql = "INSERT INTO quizzes (
                        course_id, 
                        title, 
                        description, 
                        duration_minutes,
                        passing_score,
                        max_attempts,
                        show_correct_answers,
                        randomize_questions,
                        randomize_answers,
                        show_progress,
                        allow_navigation,
                        time_limit_enabled,
                        status,
                        instructions,
                        pass_message,
                        fail_message,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [
                (int)$data['course_id'],
                $data['title'],
                $data['description'] ?? '',
                (int)($data['duration_minutes'] ?? 30),
                (float)($data['passing_score'] ?? 70),
                (int)($data['max_attempts'] ?? 0),
                (int)($data['show_correct_answers'] ?? 1),
                (int)($data['randomize_questions'] ?? 0),
                (int)($data['randomize_answers'] ?? 0),
                (int)($data['show_progress'] ?? 1),
                (int)($data['allow_navigation'] ?? 1),
                (int)($data['time_limit_enabled'] ?? 1),
                (int)($data['status'] ?? 1),
                $data['instructions'] ?? '',
                $data['pass_message'] ?? 'Congratulations! You have passed the quiz.',
                $data['fail_message'] ?? 'You did not pass the quiz. Please try again.'
            ]);
            
            if (!$result) {
                throw new Exception("Failed to create quiz: " . $this->mydb->get_error());
            }
            
            $quizId = $this->mydb->insert_id();
            
            // Add questions if any
            if (!empty($data['questions']) && is_array($data['questions'])) {
                $this->addQuestions($quizId, $data['questions']);
            }
            
            $this->mydb->commit();
            return $quizId;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error creating quiz: " . $e->getMessage());
            return false;
        }
    }

    public function updateQuiz($data) {
        if (empty($data['id'])) {
            return false;
        }
        
        $quizId = (int)$data['id'];
        $this->mydb->begin_transaction();
        
        try {
            $sql = "UPDATE quizzes SET 
                        course_id = ?,
                        title = ?,
                        description = ?,
                        duration_minutes = ?,
                        passing_score = ?,
                        max_attempts = ?,
                        show_correct_answers = ?,
                        randomize_questions = ?,
                        randomize_answers = ?,
                        show_progress = ?,
                        allow_navigation = ?,
                        time_limit_enabled = ?,
                        status = ?,
                        instructions = ?,
                        pass_message = ?,
                        fail_message = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [
                (int)$data['course_id'],
                $data['title'],
                $data['description'] ?? '',
                (int)($data['duration_minutes'] ?? 30),
                (float)($data['passing_score'] ?? 70),
                (int)($data['max_attempts'] ?? 0),
                (int)($data['show_correct_answers'] ?? 1),
                (int)($data['randomize_questions'] ?? 0),
                (int)($data['randomize_answers'] ?? 0),
                (int)($data['show_progress'] ?? 1),
                (int)($data['allow_navigation'] ?? 1),
                (int)($data['time_limit_enabled'] ?? 1),
                (int)($data['status'] ?? 1),
                $data['instructions'] ?? '',
                $data['pass_message'] ?? 'Congratulations! You have passed the quiz.',
                $data['fail_message'] ?? 'You did not pass the quiz. Please try again.',
                $quizId
            ]);
            
            if (!$result) {
                throw new Exception("Failed to update quiz: " . $this->mydb->get_error());
            }
            
            // Update questions if any
            if (!empty($data['questions'])) {
                $this->updateQuestions($quizId, $data['questions']);
            }
            
            $this->mydb->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error updating quiz: " . $e->getMessage());
            return false;
        }
    }

    public function deleteQuiz($quizId) {
        $quizId = (int)$quizId;
        $this->mydb->begin_transaction();
        
        try {
            // First delete all questions and answers
            $this->deleteQuestions($quizId);
            
            // Then delete the quiz
            $sql = "DELETE FROM quizzes WHERE id = ?";
            $stmt = $this->mydb->prepare($sql);
            $result = $this->mydb->execute_prepared($stmt, [$quizId]);
            
            if (!$result) {
                throw new Exception("Failed to delete quiz");
            }
            
            $this->mydb->commit();
            return true;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error deleting quiz: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add questions to a quiz
     */
    private function addQuestions($quizId, $questions) {
        if (empty($quizId) || empty($questions)) {
            return false;
        }
        
        $sql = "INSERT INTO quiz_questions (
                    quiz_id, 
                    question_text, 
                    option_a, 
                    option_b, 
                    option_c, 
                    option_d, 
                    correct_answer, 
                    points, 
                    explanation,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->mydb->prepare($sql);
        
        foreach ($questions as $question) {
            $result = $this->mydb->execute_prepared($stmt, [
                (int)$quizId,
                $question['question_text'],
                $question['option_a'],
                $question['option_b'],
                $question['option_c'] ?? '',
                $question['option_d'] ?? '',
                $question['correct_answer'],
                (int)($question['points'] ?? 1),
                $question['explanation'] ?? ''
            ]);
            
            if (!$result) {
                throw new Exception("Failed to add question: " . $this->mydb->get_error());
            }
        }
        
        return true;
    }
    
    /**
     * Update questions for a quiz
     */
    private function updateQuestions($quizId, $questions) {
        if (empty($quizId) || empty($questions)) {
            return false;
        }
        
        // First, get existing question IDs to determine which ones to keep
        $existingQuestions = $this->getQuestions($quizId);
        $existingIds = array_column($existingQuestions, 'id');
        $newIds = [];
        
        // Update or insert questions
        foreach ($questions as $question) {
            if (!empty($question['id']) && in_array($question['id'], $existingIds)) {
                // Update existing question
                $this->updateQuestion($quizId, $question);
                $newIds[] = $question['id'];
            } else {
                // Insert new question
                $this->addQuestions($quizId, [$question]);
            }
        }
        
        // Delete questions that were removed
        $idsToDelete = array_diff($existingIds, $newIds);
        if (!empty($idsToDelete)) {
            $placeholders = rtrim(str_repeat('?,', count($idsToDelete)), ',');
            $sql = "DELETE FROM quiz_questions WHERE id IN ($placeholders) AND quiz_id = ?";
            $params = array_merge($idsToDelete, [$quizId]);
            
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, $params);
        }
        
        return true;
    }
    
    /**
     * Update a single question (internal use)
     */
    private function updateQuestionData($quizId, $questionData) {
        if (empty($questionData['id']) || empty($quizId)) {
            return false;
        }
        
        $sql = "UPDATE quiz_questions SET 
                    question_text = ?,
                    option_a = ?,
                    option_b = ?,
                    option_c = ?,
                    option_d = ?,
                    correct_answer = ?,
                    points = ?,
                    explanation = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND quiz_id = ?";
        
        $stmt = $this->mydb->prepare($sql);
        $params = [
            $questionData['question_text'],
            $questionData['option_a'],
            $questionData['option_b'],
            $questionData['option_c'] ?? null,
            $questionData['option_d'] ?? null,
            $questionData['correct_answer'],
            $questionData['points'] ?? 1,
            $questionData['explanation'] ?? '',
            $questionData['id'],
            $quizId
        ];
        
        return $this->mydb->execute_prepared($stmt, $params);
    }
    
    /**
     * Delete all questions for a quiz
     */
    private function deleteQuestions($quizId) {
        $sql = "DELETE FROM quiz_questions WHERE quiz_id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [(int)$quizId]);
    }
    
    public function getTotalQuizzes($search = '', $courseId = null, $status = '') {
        $sql = "SELECT COUNT(*) as total FROM quizzes q WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (q.title LIKE ? OR q.description LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($courseId)) {
            $sql .= " AND q.course_id = ?";
            $params[] = (int)$courseId;
        }
        
        if ($status === 'active') {
            $sql .= " AND q.status = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND q.status = 0";
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
    
    
    /**
     * Update quiz status
     */
    public function updateQuizStatus($id, $status) {
        $sql = "UPDATE quizzes SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->mydb->prepare($sql);
        return $this->mydb->execute_prepared($stmt, [(int)$status, (int)$id]);
    }
    
    /**
     * Get quiz statistics for dashboard
     */
    public function getQuizStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_quizzes,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_quizzes,
                    (SELECT COUNT(DISTINCT quiz_id) FROM quiz_questions) as quizzes_with_questions,
                    (SELECT COUNT(DISTINCT quiz_id) FROM quiz_attempts) as attempted_quizzes,
                    (SELECT COUNT(DISTINCT user_id) FROM quiz_attempts) as unique_attempters
                FROM quizzes";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt);
        return $this->mydb->get_result($stmt)->fetch_assoc();
    }
    
    /**
     * Get recent quiz attempts
     * 
     * @param int $limit Number of recent attempts to return
     * @return array Array of recent quiz attempts with user and quiz details
     */
    public function getRecentAttempts($limit = 5) {
        $sql = "SELECT 
                    qa.*, 
                    q.title as quiz_title,
                    u.username,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    c.name as course_name
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                JOIN users u ON qa.user_id = u.id
                LEFT JOIN courses c ON q.course_id = c.id
                ORDER BY qa.completed_at DESC
                LIMIT ?";
        
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [(int)$limit]);
        return $this->mydb->get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Save quiz data and its questions
     * 
     * @param array $quizData Quiz data
     * @param array $questions Array of questions
     * @return int|bool Quiz ID on success, false on failure
     */
    public function saveQuiz($quizData, $questions = []) {
        $this->mydb->begin_transaction();
        
        try {
            if (empty($quizData['id'])) {
                // Create new quiz
                $quizId = $this->createQuiz($quizData);
            } else {
                // Update existing quiz
                $quizId = $quizData['id'];
                $this->updateQuiz($quizId, $quizData);
            }
            
            if ($quizId && !empty($questions)) {
                $this->saveQuestions($quizId, $questions);
            }
            
            $this->mydb->commit();
            return $quizId;
            
        } catch (Exception $e) {
            $this->mydb->rollback();
            error_log("Error saving quiz: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save questions for a quiz
     * 
     * @param int $quizId Quiz ID
     * @param array $questions Array of questions
     * @return bool True on success, false on failure
     */
    public function saveQuestions($quizId, $questions) {
        if (empty($quizId) || empty($questions)) {
            return false;
        }
        
        // Get existing question IDs to track which ones to delete
        $existingQuestions = $this->getQuestions($quizId);
        $existingIds = array_column($existingQuestions, 'id');
        $newIds = [];
        
        foreach ($questions as $question) {
            $questionData = [
                'quiz_id' => $quizId,
                'question_text' => $question['question_text'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'] ?? null,
                'option_d' => $question['option_d'] ?? null,
                'correct_answer' => $question['correct_answer'],
                'points' => $question['points'] ?? 1,
                'explanation' => $question['explanation'] ?? ''
            ];
            
            if (!empty($question['id']) && in_array($question['id'], $existingIds)) {
                // Update existing question
                $this->updateQuestion($question['id'], $questionData);
                $newIds[] = $question['id'];
            } else {
                // Add new question
                $questionId = $this->addQuestion($questionData);
                if ($questionId) {
                    $newIds[] = $questionId;
                }
            }
        }
        
        // Delete questions that were removed
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            $placeholders = rtrim(str_repeat('?,', count($toDelete)), ',');
            $sql = "DELETE FROM quiz_questions WHERE id IN ($placeholders)";
            $stmt = $this->mydb->prepare($sql);
            $this->mydb->execute_prepared($stmt, $toDelete);
        }
        
        return true;
    }
    
    /**
     * Add a new question to a quiz
     * 
     * @param array $questionData Question data
     * @return int|bool Question ID on success, false on failure
     */
    public function addQuestion($questionData) {
        $sql = "INSERT INTO quiz_questions (
                    quiz_id, question_text, option_a, option_b, option_c, 
                    option_d, correct_answer, points, explanation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->mydb->prepare($sql);
        $params = [
            $questionData['quiz_id'],
            $questionData['question_text'],
            $questionData['option_a'],
            $questionData['option_b'],
            $questionData['option_c'] ?? null,
            $questionData['option_d'] ?? null,
            $questionData['correct_answer'],
            $questionData['points'] ?? 1,
            $questionData['explanation'] ?? ''
        ];
        
        if ($this->mydb->execute_prepared($stmt, $params)) {
            return $this->mydb->insert_id();
        }
        
        return false;
    }
    
    /**
     * Update an existing question
     * 
     * @param int $questionId Question ID
     * @param array $questionData Question data
     * @return bool True on success, false on failure
     */
    public function updateQuestion($questionId, $questionData) {
        $sql = "UPDATE quiz_questions SET 
                    question_text = ?,
                    option_a = ?,
                    option_b = ?,
                    option_c = ?,
                    option_d = ?,
                    correct_answer = ?,
                    points = ?,
                    explanation = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->mydb->prepare($sql);
        $params = [
            $questionData['question_text'],
            $questionData['option_a'],
            $questionData['option_b'],
            $questionData['option_c'] ?? null,
            $questionData['option_d'] ?? null,
            $questionData['correct_answer'],
            $questionData['points'] ?? 1,
            $questionData['explanation'] ?? '',
            $questionId
        ];
        
        return $this->mydb->execute_prepared($stmt, $params);
    }
    
    /**
     * Get all questions for a quiz
     * 
     * @param int $quizId Quiz ID
     * @return array Array of questions
     */
    public function getQuestions($quizId) {
        $sql = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC";
        $stmt = $this->mydb->prepare($sql);
        $this->mydb->execute_prepared($stmt, [(int)$quizId]);
        return $this->mydb->get_result($stmt)->fetch_all(MYSQLI_ASSOC);
    }
}
} // End of class_exists check
?>
