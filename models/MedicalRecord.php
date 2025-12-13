<?php
require_once __DIR__ . '/../config/database.php';

class MedicalRecord {
    private $conn;
    private $table = "medical_records";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $created_by) {
        $query = "INSERT INTO " . $this->table . " 
                  (patient_name, patient_age, diagnosis, treatment, doctor_name, record_date, created_by) 
                  VALUES (:patient_name, :patient_age, :diagnosis, :treatment, :doctor_name, :record_date, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":patient_name", $patient_name);
        $stmt->bindParam(":patient_age", $patient_age);
        $stmt->bindParam(":diagnosis", $diagnosis);
        $stmt->bindParam(":treatment", $treatment);
        $stmt->bindParam(":doctor_name", $doctor_name);
        $stmt->bindParam(":record_date", $record_date);
        $stmt->bindParam(":created_by", $created_by);
        
        return $stmt->execute();
    }

    public function getAll($user_id = null, $is_admin = false) {
        if ($is_admin) {
            $query = "SELECT r.*, u.username as creator_name 
                      FROM " . $this->table . " r 
                      LEFT JOIN users u ON r.created_by = u.id 
                      ORDER BY r.record_date DESC, r.created_at DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE created_by = :user_id ORDER BY record_date DESC, created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $user_id = null, $is_admin = false) {
        if ($is_admin) {
            $query = "SELECT r.*, u.username as creator_name 
                      FROM " . $this->table . " r 
                      LEFT JOIN users u ON r.created_by = u.id 
                      WHERE r.id = :id LIMIT 1";
        } else {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND created_by = :user_id LIMIT 1";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if (!$is_admin) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function search($search_term, $user_id = null, $is_admin = false) {
        $search = "%" . $search_term . "%";
        
        if ($is_admin) {
            $query = "SELECT r.*, u.username as creator_name 
                      FROM " . $this->table . " r 
                      LEFT JOIN users u ON r.created_by = u.id 
                      WHERE patient_name LIKE :search 
                      OR diagnosis LIKE :search 
                      OR doctor_name LIKE :search 
                      OR treatment LIKE :search
                      ORDER BY r.record_date DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE created_by = :user_id 
                      AND (patient_name LIKE :search 
                      OR diagnosis LIKE :search 
                      OR doctor_name LIKE :search 
                      OR treatment LIKE :search)
                      ORDER BY record_date DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->bindParam(":search", $search);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $user_id = null, $is_admin = false) {
        if (!$is_admin) {
            $query = "UPDATE " . $this->table . " 
                      SET patient_name = :patient_name, 
                          patient_age = :patient_age, 
                          diagnosis = :diagnosis, 
                          treatment = :treatment, 
                          doctor_name = :doctor_name, 
                          record_date = :record_date 
                      WHERE id = :id AND created_by = :user_id";
        } else {
            $query = "UPDATE " . $this->table . " 
                      SET patient_name = :patient_name, 
                          patient_age = :patient_age, 
                          diagnosis = :diagnosis, 
                          treatment = :treatment, 
                          doctor_name = :doctor_name, 
                          record_date = :record_date 
                      WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":patient_name", $patient_name);
        $stmt->bindParam(":patient_age", $patient_age);
        $stmt->bindParam(":diagnosis", $diagnosis);
        $stmt->bindParam(":treatment", $treatment);
        $stmt->bindParam(":doctor_name", $doctor_name);
        $stmt->bindParam(":record_date", $record_date);
        
        if (!$is_admin) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        return $stmt->execute();
    }

    public function delete($id, $user_id = null, $is_admin = false) {
        if (!$is_admin) {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND created_by = :user_id";
        } else {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if (!$is_admin) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        return $stmt->execute();
    }

    public function getTotalCount($user_id = null, $is_admin = false) {
        if ($is_admin) {
            $query = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE created_by = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>

