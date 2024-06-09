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
    if (!$database) {
        respond(500, "Failed to connect to database.");
    }

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

        $id_sensor = $_GET['id_sensor'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];

        if ($level === 'admin') {
            switch ($method) {
                case 'GET':
                    $result = $id_sensor ? getSensorById($database, $id_sensor) : getAllSensors($database);
                    break;
                case 'POST':
                    $result = addOrUpdateSensor($database, $id_sensor, $_POST);
                    break;
                case 'DELETE':
                    $result = $id_sensor ? deleteSensor($database, $id_sensor) : respond(400, "Please provide an ID to delete.");
                    break;
                default:
                    respond(405, "Method not allowed.");
                    break;
            }
        } else {
            switch ($method) {
                case 'GET':
                    $result = $id_sensor ? getSensorById($database, $id_sensor) : getAllSensors($database);
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

function getAllSensors($conn) {
    $sql = "SELECT * FROM sensor";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSensorById($conn, $id_sensor) {
    $sql = "SELECT * FROM sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_sensor]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['message' => 'Sensor not found'];
}


function addOrUpdateSensor($conn, $id_sensor, $data) {
    if ($id_sensor) {
        return updateSensor($conn, $id_sensor, $data);
    } else {
        return addSensor($conn, $data);
    }
}

function addSensor($conn, $data) {
    $required_fields = ['id_sensor','nama_sensor', 'tanggal_aktivasi', 'id_lahan'];

    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            return ["status" => "error", "message" => "Field '$field' is required."];
        }
    }

    $sql = "INSERT INTO sensor (id_sensor, nama_sensor, id_lahan, tanggal_aktivasi) VALUES (:id_sensor, :nama_sensor, :id_lahan, :tanggal_aktivasi)";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute([
            ':id_sensor' => $data['id_sensor'],
            ':id_lahan' => $data['id_lahan'],
            ':tanggal_aktivasi' => $data['tanggal_aktivasi'],
            ':nama_sensor' => $data['nama_sensor']
        ]);
        return $stmt->rowCount() > 0 ? ["status" => "success", "message" => "Sensor added successfully."] : ["status" => "error", "message" => "Failed to add sensor."];
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Error: " . $e->getMessage()];
    }
}

function updateSensor($conn, $id_sensor, $data) {
    if (empty($data)) {
        return ["status" => "error", "message" => "No data provided"];
    }

    $allowed_fields = ['id_lahan', 'nama_sensor', 'tanggal_aktivasi'];
    $update_fields = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $update_fields[] = "$key = :$key";
        }
    }

    if (empty($update_fields)) {
        return ["status" => "error", "message" => "No valid fields provided for update."];
    }

    $update_fields_str = implode(", ", $update_fields);
    $sql = "UPDATE sensor SET $update_fields_str WHERE id_sensor = :id_sensor";
    $stmt = $conn->prepare($sql);

    $data['id_sensor'] = $id_sensor;

    try {
        $stmt->execute($data);
        return $stmt->rowCount() > 0 ? ["status" => "success", "message" => "Sensor updated successfully."] : ["status" => "error", "message" => "No changes made to the sensor."];
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Error: " . $e->getMessage()];
    }
}

function deleteSensor($conn, $id_sensor) {
    $sql = "DELETE FROM sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$id_sensor]);
        return $stmt->rowCount() > 0 ? ["status" => "success", "message" => "Sensor deleted successfully."] : ["status" => "error", "message" => "Failed to delete sensor."];
    } catch (PDOException $e) {
        return ["status" => "error", "message" => "Error: " . $e->getMessage()];
    }
}
?>
