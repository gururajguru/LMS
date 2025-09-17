<?php
class TopicResource {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getByTopicId($topicId) {
        $sql = "SELECT * FROM topic_resources WHERE topic_id = " . $this->db->escape_string($topicId) . " ORDER BY display_order, created_at";
        $this->db->setQuery($sql);
        return $this->db->loadResultList();
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM topic_resources WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->loadSingleResult();
    }
    
    public function create($data) {
        $sql = "INSERT INTO topic_resources (topic_id, title, resource_type, content, display_order) VALUES (
            " . $this->db->escape_string($data['topic_id']) . ",
            '" . $this->db->escape_string($data['title']) . "',
            '" . $this->db->escape_string($data['resource_type']) . "',
            '" . $this->db->escape_string($data['content']) . "',
            " . (int)($data['display_order'] ?? 0) . "
        )";
        $this->db->setQuery($sql);
        if ($this->db->execute()) {
            return $this->db->insert_id();
        }
        return false;
    }
    
    public function update($id, $data) {
        $sql = "UPDATE topic_resources SET 
            title = '" . $this->db->escape_string($data['title']) . "',
            resource_type = '" . $this->db->escape_string($data['resource_type']) . "',
            content = '" . $this->db->escape_string($data['content']) . "',
            display_order = " . (int)($data['display_order'] ?? 0) . "
            WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->execute();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM topic_resources WHERE id = " . $this->db->escape_string($id);
        $this->db->setQuery($sql);
        return $this->db->execute();
    }
    
    public function getByCourseId($courseId) {
        $sql = "SELECT tr.* FROM topic_resources tr 
                JOIN topics t ON tr.topic_id = t.id 
                WHERE t.course_id = " . $this->db->escape_string($courseId) . " 
                ORDER BY t.display_order, tr.display_order";
        $this->db->setQuery($sql);
        return $this->db->loadResultList();
    }
}
?>
