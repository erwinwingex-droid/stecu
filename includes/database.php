<?php
/**
 * Database Connection Class for Adin Laundry
 * Handles all database operations with PDO
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'adin_laundry';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $pdo;
    private $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $this->error = "Connection failed: " . $e->getMessage();
            error_log($this->error);
            throw new Exception("Database connection error. Please try again later.");
        }
    }

    // Generic query method
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->error = "Query failed: " . $e->getMessage();
            error_log($this->error . " - SQL: " . $sql);
            throw new Exception("Database query error.");
        }
    }

    // Get single row
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Get multiple rows
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Insert data
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    // Update data
    public function update($table, $data, $where, $where_params = []) {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "{$key} = :{$key}, ";
        }
        $set = rtrim($set, ', ');

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge($data, $where_params);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // Check if record exists
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->getRow($sql, $params);
        return $result['count'] > 0;
    }

    // Count records
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->getRow($sql, $params);
        return $result['count'];
    }

    // Get paginated results
    public function getPaginated($sql, $params = [], $page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT {$per_page} OFFSET {$offset}";
        
        return [
            'data' => $this->getRows($sql, $params),
            'total' => $this->countTotal($sql, $params),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($this->countTotal($sql, $params) / $per_page)
        ];
    }

    private function countTotal($sql, $params) {
        // Remove SELECT clause and add COUNT
        $count_sql = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $sql);
        // Remove ORDER BY, LIMIT, OFFSET
        $count_sql = preg_replace('/ORDER BY.*/i', '', $count_sql);
        $count_sql = preg_replace('/LIMIT.*/i', '', $count_sql);
        
        $result = $this->getRow($count_sql, $params);
        return $result['total'];
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->pdo->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->pdo->rollback();
    }

    // Get last insert ID
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    // Get error
    public function getError() {
        return $this->error;
    }

    // Close connection
    public function close() {
        $this->pdo = null;
    }
}

// Create global database instance
try {
    $database = new Database();
    $pdo = $database->pdo;
} catch (Exception $e) {
    // Show friendly error message in production
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        die("Database Error: " . $e->getMessage());
    } else {
        error_log("Database Error: " . $e->getMessage());
        die("System maintenance in progress. Please try again later.");
    }
}
?>