<?php
class Database {
  private static $db;
  
  public static function connect($c){
    if(!self::$db){
      try {
        self::$db = new PDO(
          "mysql:host={$c['host']};dbname={$c['db']};charset=utf8mb4",
          $c['user'],$c['pass'],
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
          ]
        );
        // Align connection collation with modern defaults (avoids ER_CANT_AGGREGATE_2COLLATIONS vs legacy utf8mb4_general_ci columns when combined with explicit COLLATE in queries).
        self::$db->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
      } catch(PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
      }
    }
    return self::$db;
  }
  
  public static function getConnection() {
    return self::$db;
  }
}