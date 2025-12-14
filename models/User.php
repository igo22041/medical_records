<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = "users";

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if ($this->conn === null) {
                throw new PDOException("Не удалось получить подключение к базе данных");
            }
        } catch(PDOException $e) {
            error_log("User model connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table . " (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);
        
        try {
            $stmt->execute();
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function login($username, $password) {
        $query = "SELECT id, username, email, password, role FROM " . $this->table . " WHERE username = :username OR email = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        
        return false;
    }

    public function getUserById($id) {
        $query = "SELECT id, username, email, role, created_at FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers() {
        $query = "SELECT u.id, u.username, u.email, u.role, u.created_at, 
                         COUNT(r.id) as record_count
                  FROM " . $this->table . " u
                  LEFT JOIN medical_records r ON u.id = r.created_by
                  GROUP BY u.id, u.username, u.email, u.role, u.created_at
                  ORDER BY u.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteUser($id, $current_user_id) {
        // Нельзя удалить самого себя
        if ($id == $current_user_id) {
            return false;
        }
        
        // Удаляем пользователя (записи остаются, но created_by может стать NULL или остаться как есть)
        // В зависимости от структуры БД, можно установить created_by в NULL или оставить как есть
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND id != :current_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":current_user_id", $current_user_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function updateUser($id, $username, $email, $role) {
        $query = "UPDATE " . $this->table . " 
                  SET username = :username, email = :email, role = :role 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":role", $role, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
}

