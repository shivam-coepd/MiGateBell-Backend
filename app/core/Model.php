<?php
class Model {
  protected $db;
  
  public function __construct(){
    try {
      $this->db = Database::connect(require __DIR__.'/../config/database.php');
    } catch(Exception $e) {
      error_log("Model construction failed: " . $e->getMessage());
      throw $e;
    }
  }
  
  // Generic method to execute SELECT queries
  protected function select($sql, $params = []) {
    try {
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll();
    } catch(PDOException $e) {
      error_log("Select query failed: " . $e->getMessage());
      throw new Exception("Query execution failed: " . $e->getMessage());
    }
  }
  
  // Generic method to execute INSERT queries
  protected function insert($table, $data) {
    try {
      $columns = implode(',', array_keys($data));
      $placeholders = ':' . implode(', :', array_keys($data));
      $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
      
      $stmt = $this->db->prepare($sql);
      $stmt->execute($data);
      
      return $this->db->lastInsertId();
    } catch(PDOException $e) {
      error_log("Insert query failed: " . $e->getMessage());
      throw new Exception("Insert operation failed: " . $e->getMessage());
    }
  }
  
  // Generic method to execute UPDATE queries
  protected function update($table, $data, $where, $whereParams = []) {
    try {
      $setParts = [];
      foreach(array_keys($data) as $column) {
        $setParts[] = "{$column} = :{$column}";
      }
      $setClause = implode(', ', $setParts);
      
      $whereClause = '';
      if (!empty($where)) {
        $whereClause = " WHERE {$where}";
      }
      
      $sql = "UPDATE {$table} SET {$setClause} {$whereClause}";
      
      $stmt = $this->db->prepare($sql);
      
      // Merge data and where parameters
      $allParams = array_merge($data, $whereParams);
      $stmt->execute($allParams);
      
      return $stmt->rowCount();
    } catch(PDOException $e) {
      error_log("Update query failed: " . $e->getMessage());
      throw new Exception("Update operation failed: " . $e->getMessage());
    }
  }
  
  // Generic method to execute DELETE queries
  protected function delete($table, $where, $params = []) {
    try {
      $sql = "DELETE FROM {$table} WHERE {$where}";
      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);
      return $stmt->rowCount();
    } catch(PDOException $e) {
      error_log("Delete query failed: " . $e->getMessage());
      throw new Exception("Delete operation failed: " . $e->getMessage());
    }
  }
  
  // Method to begin transaction
  protected function beginTransaction() {
    return $this->db->beginTransaction();
  }
  
  // Method to commit transaction
  protected function commit() {
    return $this->db->commit();
  }
  
  // Method to rollback transaction
  protected function rollback() {
    return $this->db->rollback();
  }
}