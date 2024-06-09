<?php
require_once 'config.php';
require "vendor/autoload.php";
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
    $conn = $database;
    $data = $_POST;

    $token = getBearerToken();
    if ($token === null) {
        respond(401, "Authorization header not found or invalid.");
    }

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        $id = $decoded->id;
        $level = $decoded->level;

        $statement = $database->prepare("SELECT * FROM users WHERE id = :id");
        $statement->bindParam(':id', $id);
        $statement->execute();
        $user = $statement->fetch();

        if (!$user) {
            respond(401, "User not found.");
        }

        $id = $_GET['id'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $result = getAllData($conn);
                break;
            default:
                $result = "Method not allowed.";
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($result);

    } catch (Exception $e) {
        respond(401, "Invalid token: " . $e->getMessage());
    }
} catch (Exception $e) {
    respond(500, "Internal server error: " . $e->getMessage());
}

function getAllData($conn) {
    // Get all tables from the database
    $sql = "SHOW TABLES";
    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $data = [];
    foreach ($tables as $table) {
        if ($table !== 'data_sensor') {
            $sql = "SELECT * FROM $table";
            $stmt = $conn->query($sql);
            $data[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return $data;
}
?>
