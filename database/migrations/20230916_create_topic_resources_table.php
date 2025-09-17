<?php

class CreateTopicResourcesTable {
    public function up() {
        $db = new Database();
        $sql = "CREATE TABLE IF NOT EXISTS topic_resources (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            resource_type ENUM('video', 'pdf', 'link') NOT NULL,
            content TEXT NOT NULL,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
    }

    public function down() {
        $db = new Database();
        $db->query("DROP TABLE IF EXISTS topic_resources");
    }
}
?>
