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
                    if ($id_sensor) {
                        $result = getDataSensorByIdSensor($conn, $id_sensor);
                    } else {
                        $result = getAllDataSensors($conn);
                    }
                    break;
                case 'POST':
                        $result = addDataSensor($conn, $data);
                    break;
                case 'DELETE':
                    if ($id_sensor) {
                        $result = deleteDataSensor($conn, $id_sensor);
                    } else {
                        $result = "Please provide an ID to delete.";
                    }
                    break;
                default:
                    $result = "Method not allowed.";
                    break;
            }
        } else {
            switch ($method) {
                case 'GET':
                    $result = $id_sensor ? getDataSensorById($database, $id_sensor) : getAllDataSensors($database);
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

function getAllDataSensors($conn) {
    $sql = "SELECT * FROM data_sensor";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

function getDataSensorByIdSensor($conn, $id_sensor) {
    $sql = "SELECT * FROM data_sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_sensor]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addDataSensor($conn, $data) {
    $required_fields = ['intensitas_cahaya', 'kelembaban_tanah', 'kualitas_udara', 'RainDrop', 'kelembaban_udara', 'suhu', 'tekanan', 'ketinggian', 'waktu_perekaman'];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            return "Field '$field' is required.";
        }
    }

    $sql = "INSERT INTO data_sensor (id_sensor, intensitas_cahaya, kelembaban_tanah, kualitas_udara, RainDrop, kelembaban_udara, suhu, tekanan, ketinggian, waktu_perekaman) VALUES (:id_sensor, :intensitas_cahaya, :kelembaban_tanah, :kualitas_udara, :RainDrop, :kelembaban_udara, :suhu, :tekanan, :ketinggian, :waktu_perekaman)";
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        $data['id_sensor'],
        $data['intensitas_cahaya'],
        $data['kelembaban_tanah'],
        $data['kualitas_udara'],
        $data['RainDrop'],
        $data['kelembaban_udara'],
        $data['suhu'],
        $data['tekanan'],
        $data['ketinggian'],
        $data['waktu_perekaman']
    ]);
    
    return $stmt->rowCount() > 0 ? "Data sensor added successfully." : "Failed to add data sensor.";
}

function deleteDataSensor($conn, $id_sensor) {
    $sql = "DELETE FROM data_sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([$id_sensor]);
    
    return $stmt->rowCount() > 0 ? "Data sensor deleted successfully." : "Failed to delete data sensor.";
}

?>
