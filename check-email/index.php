<?php
require_once '../config.php';
require "../vendor/autoload.php";
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "sm4rtf4rm";

function respond($status_code, $message) {
    http_response_code($status_code);
    echo json_encode(array("message" => $message));
    exit;
}

function getBearerToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }
    $matches = [];
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }
    return null;
}

try {
    $database = getDbConnection();
    if (!$database) {
        respond(500, "Failed to connect to database.");
    }

    $token = getBearerToken();
    if ($token === null) {
        respond(401, "Authorization header not found or invalid.");
    }

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    } catch (Exception $e) {
        respond(401, "Invalid token: " . $e->getMessage());
    }

    // Validasi email dari input
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(400, "Invalid email format.");
    }

    // Cek keunikan email
    $query = $database->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $query->execute(['email' => $email]);
    $emailExists = $query->fetchColumn() > 0;

    if ($emailExists) {
        respond(200, "Email is already in use.");
    } else {
        respond(200, "Email is available.");
    }

} catch (Exception $e) {
    respond(500, "Internal server error: " . $e->getMessage());
}
