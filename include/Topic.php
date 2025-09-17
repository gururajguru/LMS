<?php
class Topic {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getByCourseId($courseId) {
        // First get all lessons for this course
        $sql = "SELECT id FROM lessons WHERE course_id = " . $this->db->escape_string($courseId);
        $this->db->setQuery($sql);
        $lessons = $this->db->loadResultList();
        
        if (empty($lessons)) {
            return [];
        }
        
        // Get topic IDs for all lessons
        $lessonIds = array_map(function($lesson) {
            return $lesson->id;
        }, $lessons);
        
        $placeholders = rtrim(str_repeat('?,', count($lessonIds)), ',');
        $sql = "SELECT t.*, l.title as lesson_title 
                FROM topics t 
                JOIN lessons l ON t.lesson_id = l.id 
                WHERE t.lesson_id IN ($placeholders) 
                ORDER BY l.order_number, t.order_number, t.created_at";
                
        $this->db->setQuery($sql, $lessonIds);
        return $this->db->loadResultList();
    }
    
    public function getByLessonId($lessonId) {
        $sql = "SELECT t.*, l.title as lesson_title 
                FROM topics t 
                JOIN lessons l ON t.lesson_id = l.id 
                WHERE t.lesson_id = " . $this->db->escape_string($lessonId) . " 
                ORDER BY t.order_number, t.created_at";
                
        $this->db->setQuery($sql);
        return $this->db->loadResultList();
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM topics WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->loadSingleResult();
    }
    
    public function create($data) {
        if (empty($data['lesson_id'])) {
            throw new Exception('Lesson ID is required');
        }
        
        $sql = "INSERT INTO topics (lesson_id, title, description, display_order, created_at) VALUES (
            " . $this->db->escape_string($data['lesson_id']) . ",
            '" . $this->db->escape_string($data['title']) . "',
            '" . $this->db->escape_string($data['description'] ?? '') . "',
            " . (int)($data['display_order'] ?? 0) . ",
            NOW()
        )";
        
        $this->db->setQuery($sql);
        if ($this->db->execute()) {
            return $this->db->insert_id();
        }
        return false;
    }
    
    public function update($id, $data) {
        $sql = "UPDATE topics SET 
            title = '" . $this->db->escape_string($data['title']) . "',
            description = '" . $this->db->escape_string($data['description'] ?? '') . "',
            display_order = " . (int)($data['display_order'] ?? 0) . "
            WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->execute();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM topics WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->execute();
    }
}
?>
