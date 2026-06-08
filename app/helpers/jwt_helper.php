<?php
function jwt_encode($payload, $secret = null){
  if ($secret === null) {
    $secret = getenv('JWT_SECRET') ?: 'supersecret';
  }
  
  // Add issued at time if not present
  if (!isset($payload['iat'])) {
    $payload['iat'] = time();
  }
  
  // Add expiration if not present
  if (!isset($payload['exp'])) {
    $payload['exp'] = time() + 86400; // 24 hours
  }
  
  $header = base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
  $payloadEncoded = base64UrlEncode(json_encode($payload));
  $signature = base64UrlEncode(hash_hmac('sha256', $header . "." . $payloadEncoded, $secret, true));
  
  return $header . "." . $payloadEncoded . "." . $signature;
}

function jwt_decode_token($token, $secret = null, $ignoreExpiration = false){
  if ($secret === null) {
    $secret = getenv('JWT_SECRET') ?: 'supersecret';
  }
  
  $parts = explode('.', $token);
  
  if (count($parts) !== 3) {
    throw new Exception('Invalid token format');
  }
  
  list($header, $payload, $signature) = $parts;
  
  // Verify signature
  $expectedSignature = base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, $secret, true));
  
  if ($signature !== $expectedSignature) {
    throw new Exception('Invalid token signature');
  }
  
  $decodedPayload = json_decode(base64UrlDecode($payload), true);
  
  // Check expiration
  if (!$ignoreExpiration && isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
    throw new Exception('Token has expired');
  }
  
  return $decodedPayload;
}

function base64UrlEncode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function jwt_validate_token($token) {
  try {
    $payload = jwt_decode_token($token);
    return $payload;
  } catch (Exception $e) {
    return false;
  }
}

function get_authorization_header() {
  $headers = null;
  if (isset($_SERVER['Authorization'])) {
    $headers = trim($_SERVER["Authorization"]);
  } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
    $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
  } elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    // Server-side fix for header name casing
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    if (isset($requestHeaders['Authorization'])) {
      $headers = trim($requestHeaders['Authorization']);
    }
  }
  return $headers;
}