<?php
require_once 'database.php';

class Users {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function authenticate($username, $password, $userType) {
        try {
            // Normalize user type for comparison
            $userType = strtolower(trim($userType));
            
            // Determine expected user type (case-insensitive)
            if ($userType === 'admin' || $userType === 'administrator') {
                $expectedType = 'Administrator';
            } else {
                $expectedType = 'Student';
            }
            
            // Always query the users table for authentication
            $sql = "SELECT * FROM users WHERE username = ? AND user_type = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $this->db->execute_prepared($stmt, [$username, $expectedType]);
            $result = $this->db->get_result($stmt);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_object();
                if (password_verify($password, $user->password)) {
                    return $user;
                }
            }
            
            return false;
        } catch (Exception $e) {
            throw new Exception('Authentication error: ' . $e->getMessage());
        }
    }
    
    public function getUserById($id, $userType = 'admin') {
        try {
            // Always query the users table for user data
            $sql = "SELECT * FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $this->db->execute_prepared($stmt, [$id]);
            $result = $this->db->get_result($stmt);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_object();
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception('Error fetching user: ' . $e->getMessage());
        }
    }
    
    public function getAllUsers() {
        try {
            $sql = "SELECT id, username, email, first_name, last_name, user_type, status, created_at FROM users WHERE status != 'deleted' ORDER BY created_at DESC";
            $this->db->setQuery($sql);
            return $this->db->loadResultList();
        } catch (Exception $e) {
            throw new Exception('Error fetching users: ' . $e->getMessage());
        }
    }
    
    public function createUser($data) {
        try {
            $sql = "INSERT INTO users (username, password, email, first_name, last_name, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $this->db->execute_prepared($stmt, [
                $data['username'],
                $hashedPassword,
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $data['user_type'] ?? 'Student',
                $data['status'] ?? 'active'
            ]);
            
            return $this->db->insert_id();
        } catch (Exception $e) {
            throw new Exception('Error creating user: ' . $e->getMessage());
        }
    }
    
    public function updateUser($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'password') {
                    $fields[] = "{$key} = ?";
                    $values[] = $value;
                }
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $values[] = $id;
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $this->db->execute_prepared($stmt, $values);
        } catch (Exception $e) {
            throw new Exception('Error updating user: ' . $e->getMessage());
        }
    }
    
    public function deleteUser($id) {
        try {
            $sql = "UPDATE users SET status = 'deleted' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $this->db->execute_prepared($stmt, [$id]);
        } catch (Exception $e) {
            throw new Exception('Error deleting user: ' . $e->getMessage());
        }
    }
    
    public function updateLastActivity($userId, $userType = 'admin') {
        try {
            if ($userType === 'admin') {
                $table = 'users';
            } else {
                $table = 'students';
            }
            
            $sql = "UPDATE {$table} SET updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $this->db->execute_prepared($stmt, [$userId]);
        } catch (Exception $e) {
            throw new Exception('Error updating last activity: ' . $e->getMessage());
        }
    }
}
?>