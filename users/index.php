<?php
require_once '../config.php';
require "../vendor/autoload.php";
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

        if ($level === 'admin') {
            switch ($method) {
                case 'GET':
                    $result = $id ? getUserById($conn, $id) : getAllUsers($conn);
                    break;
                case 'POST':
                    $result = $id ? updateUser($conn, $id, $data) : addUser($conn, $data);
                    break;
                case 'DELETE':
                    $result = $id ? deleteUser($conn, $id) : respond(400, "Please provide an ID to delete.");
                    break;
                default:
                    respond(405, "Method not allowed.");
                    break;
            }
        } else {
            switch ($method) {
                case 'GET':
                    $result = $id ? getUserById($conn, $id) : getAllUsers($conn);
                    break;
                case 'POST':
                case 'DELETE':
                    respond(401, "Unauthorized. Only administrators can perform this action.");
                    break;
                default:
                    respond(405, "Method not allowed.");
                    break;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);

    } catch (Exception $e) {
        respond(401, "Invalid token: " . $e->getMessage());
    }
} catch (Exception $e) {
    respond(500, "Internal server error: " . $e->getMessage());
}

function getAllUsers($conn) {
    $sql = "SELECT * FROM users";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($conn, $id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addUser($conn, $data) {
    $required_fields = ['name', 'email', 'password', 'level', 'alamat_user'];

    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            respond(400, "Required field '$field' is missing.");
        }
    }

    $sql = "INSERT INTO users (name, email, password, level, alamat_user) VALUES (:name, :email, :password, :level, :alamat_user)";
    $stmt = $conn->prepare($sql);

    $params = [
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ':level' => $data['level'],
        ':alamat_user' => $data['alamat_user']
    ];

    // Debugging: print parameters
    error_log(print_r($params, true));

    try {
        $stmt->execute($params);
        return $stmt->rowCount() > 0 ? "User added successfully." : "Failed to add user.";
    } catch (PDOException $e) {
        respond(500, "Error: " . $e->getMessage());
    }
}


function updateUser($conn, $id, $data) {
    if (empty($data)) {
        respond(400, "No data provided for update.");
    }

    $allowed_fields = ['name', 'email', 'password', 'level', 'alamat_user'];
    $update_fields = [];
    $params = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            if ($key === 'password') {
                $value = password_hash($value, PASSWORD_BCRYPT); // Menggunakan hash untuk password
            }
            $update_fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }

    if (empty($update_fields)) {
        respond(400, "No valid fields provided for update.");
    }

    $params[':id'] = $id;
    $update_fields_str = implode(", ", $update_fields);

    $sql = "UPDATE users SET $update_fields_str WHERE id = :id";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute($params);
        return $stmt->rowCount() > 0 ? "User updated successfully." : "No changes made to the user.";
    } catch (PDOException $e) {
        respond(500, "Error: " . $e->getMessage());
    }
}

function deleteUser($conn, $id) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0 ? "User deleted successfully." : "Failed to delete user.";
    } catch (PDOException $e) {
        respond(500, "Error: " . $e->getMessage());
    }
}
?>
