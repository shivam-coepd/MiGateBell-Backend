<?php
class Response {
  static function success($m, $d=[], $code=200) { 
    http_response_code($code);
    echo json_encode([
      'status' => true,
      'message' => $m,
      'data' => $d
    ]); 
    exit; 
  }
  
  static function error($m, $code=400) { 
    http_response_code($code); 
    echo json_encode([
      'status' => false,
      'message' => $m,
      'data' => null
    ]); 
    exit; 
  }
  
  static function validationError($errors) {
    http_response_code(422);
    echo json_encode([
      'status' => false,
      'message' => 'Validation failed',
      'errors' => $errors
    ]);
    exit;
  }
  
  static function unauthorized($m = "Unauthorized access") {
    http_response_code(401);
    echo json_encode([
      'status' => false,
      'message' => $m,
      'data' => null
    ]);
    exit;
  }
  
  static function forbidden($m = "Access forbidden") {
    http_response_code(403);
    echo json_encode([
      'status' => false,
      'message' => $m,
      'data' => null
    ]);
    exit;
  }
  
  static function notFound($m = "Resource not found") {
    http_response_code(404);
    echo json_encode([
      'status' => false,
      'message' => $m,
      'data' => null
    ]);
    exit;
  }
}