<?php
require_once __DIR__ . '/../config/database.php';

class MedicalRecord {
    private $conn;
    private $table = "medical_records";

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if ($this->conn === null) {
                throw new PDOException("Не удалось получить подключение к базе данных");
            }
        } catch(PDOException $e) {
            error_log("MedicalRecord model connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function create($patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $status, $created_by, $attachment_file = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (patient_name, patient_age, diagnosis, treatment, doctor_name, record_date, status, created_by, attachment_file) 
                  VALUES (:patient_name, :patient_age, :diagnosis, :treatment, :doctor_name, :record_date, :status, :created_by, :attachment_file)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":patient_name", $patient_name);
        $stmt->bindParam(":patient_age", $patient_age);
        $stmt->bindParam(":diagnosis", $diagnosis);
        $stmt->bindParam(":treatment", $treatment);
        $stmt->bindParam(":doctor_name", $doctor_name);
        $stmt->bindParam(":record_date", $record_date);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":created_by", $created_by);
        $stmt->bindParam(":attachment_file", $attachment_file);
        
        return $stmt->execute();
    }

    public function getAll($user_id = null, $is_admin = false, $status_filter = null) {
        $where_clauses = [];
        $params = [];
        
        if (!$is_admin) {
            $where_clauses[] = "created_by = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if ($status_filter) {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        if ($is_admin) {
            $query = "SELECT r.*, u.username as creator_name 
                      FROM " . $this->table . " r 
                      LEFT JOIN users u ON r.created_by = u.id 
                      " . $where_sql . "
                      ORDER BY r.record_date DESC, r.created_at DESC";
        } else {
            $query = "SELECT * FROM " . $this->table . " " . $where_sql . " ORDER BY record_date DESC, created_at DESC";
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
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

    public function update($id, $patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $status, $user_id = null, $is_admin = false, $attachment_file = null, $clear_file = false) {
        if (!$is_admin) {
            $query = "UPDATE " . $this->table . " 
                      SET patient_name = :patient_name, 
                          patient_age = :patient_age, 
                          diagnosis = :diagnosis, 
                          treatment = :treatment, 
                          doctor_name = :doctor_name, 
                          record_date = :record_date,
                          status = :status";
        } else {
            $query = "UPDATE " . $this->table . " 
                      SET patient_name = :patient_name, 
                          patient_age = :patient_age, 
                          diagnosis = :diagnosis, 
                          treatment = :treatment, 
                          doctor_name = :doctor_name, 
                          record_date = :record_date,
                          status = :status";
        }
        
        // Если нужно очистить файл или установить новое значение
        if ($clear_file) {
            $query .= ", attachment_file = NULL";
        } elseif ($attachment_file !== null) {
            $query .= ", attachment_file = :attachment_file";
        }
        
        $query .= $is_admin ? " WHERE id = :id" : " WHERE id = :id AND created_by = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":patient_name", $patient_name);
        $stmt->bindParam(":patient_age", $patient_age);
        $stmt->bindParam(":diagnosis", $diagnosis);
        $stmt->bindParam(":treatment", $treatment);
        $stmt->bindParam(":doctor_name", $doctor_name);
        $stmt->bindParam(":record_date", $record_date);
        $stmt->bindParam(":status", $status);
        
        if ($attachment_file !== null && !$clear_file) {
            $stmt->bindParam(":attachment_file", $attachment_file);
        }
        
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

    // Статистика для администратора
    public function getStatsByStatus() {
        $query = "SELECT status, COUNT(*) as count 
                  FROM " . $this->table . " 
                  GROUP BY status 
                  ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatsByUser() {
        $query = "SELECT u.username, u.id, COUNT(r.id) as record_count 
                  FROM users u 
                  LEFT JOIN " . $this->table . " r ON u.id = r.created_by 
                  GROUP BY u.id, u.username 
                  ORDER BY record_count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatsByDate($days = 30) {
        $query = "SELECT DATE(record_date) as date, COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(record_date) 
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatsByAgeGroup() {
        $query = "SELECT 
                    CASE 
                        WHEN patient_age < 18 THEN 'Дети (0-17)'
                        WHEN patient_age BETWEEN 18 AND 30 THEN 'Молодежь (18-30)'
                        WHEN patient_age BETWEEN 31 AND 50 THEN 'Взрослые (31-50)'
                        WHEN patient_age BETWEEN 51 AND 70 THEN 'Средний возраст (51-70)'
                        ELSE 'Пожилые (70+)'
                    END as age_group,
                    COUNT(*) as count
                  FROM " . $this->table . " 
                  GROUP BY age_group 
                  ORDER BY count DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentRecords($limit = 10) {
        $query = "SELECT r.*, u.username as creator_name 
                  FROM " . $this->table . " r 
                  LEFT JOIN users u ON r.created_by = u.id 
                  ORDER BY r.record_date DESC, r.created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Метод для пагинации
    public function getAllPaginated($user_id = null, $is_admin = false, $status_filter = null, $page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        $where_clauses = [];
        $params = [];
        
        if (!$is_admin) {
            $where_clauses[] = "created_by = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if ($status_filter) {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        if ($is_admin) {
            $query = "SELECT r.*, u.username as creator_name 
                      FROM " . $this->table . " r 
                      LEFT JOIN users u ON r.created_by = u.id 
                      " . $where_sql . "
                      ORDER BY r.record_date DESC, r.created_at DESC 
                      LIMIT :limit OFFSET :offset";
        } else {
            $query = "SELECT * FROM " . $this->table . " " . $where_sql . " 
                      ORDER BY record_date DESC, created_at DESC 
                      LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить общее количество записей для пагинации
    public function getTotalCountForPagination($user_id = null, $is_admin = false, $status_filter = null) {
        $where_clauses = [];
        $params = [];
        
        if (!$is_admin) {
            $where_clauses[] = "created_by = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if ($status_filter) {
            $where_clauses[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " " . $where_sql;
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}

