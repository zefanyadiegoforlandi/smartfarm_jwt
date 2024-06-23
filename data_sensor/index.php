<?php
require_once '../config.php';
require "../vendor/autoload.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function respond($status_code, $message) {
    http_response_code($status_code);
    echo json_encode(array("message" => $message));
    exit;
}

try {
    $database = getDbConnection();
    $conn = $database;
    $data = $_POST;

    if (!$database) {
        respond(500, "Failed to connect to database.");
    }

    $id_sensor = $_GET['id_sensor'] ?? null;
    $method = $_SERVER['REQUEST_METHOD'];

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
            respond(405, "Method not allowed.");
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    respond(500, "Internal server error: " . $e->getMessage());
}

function getAllDataSensors($conn) {
    $sql = "SELECT * FROM data_sensor";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDataSensorByIdSensor($conn, $id_sensor) {
    $sql = "SELECT * FROM data_sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_sensor]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addDataSensor($conn, $data) {
    $required_fields = ['id_sensor','Light', 'PersentaseKelembapanTanah', 'AirQuality', 'RainDrop', 'H', 'T', 'Temperature', 'Pressure', 'ApproxAltitude', 'TimeAdded'];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            return ["error" => "Field '$field' is required."];
        }
    }

    $sql = "INSERT INTO data_sensor (id_sensor,Light, PersentaseKelembapanTanah, AirQuality, RainDrop, H, T, Temperature, Pressure, ApproxAltitude, TimeAdded) VALUES (:id_sensor, :Light, :PersentaseKelembapanTanah, :AirQuality, :RainDrop, :H, :T, :Temperature, :Pressure, :ApproxAltitude, :TimeAdded)";
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([
        'id_sensor' => $data['id_sensor'],
        'Light' => $data['Light'],
        'PersentaseKelembapanTanah' => $data['PersentaseKelembapanTanah'],
        'AirQuality' => $data['AirQuality'],
        'RainDrop' => $data['RainDrop'],
        'H' => $data['H'],
        'T' => $data['T'],
        'Temperature' => $data['Temperature'],
        'Pressure' => $data['Pressure'],
        'ApproxAltitude' => $data['ApproxAltitude'],
        'TimeAdded' => $data['TimeAdded']
    ]);
    
    return $stmt->rowCount() > 0 ? ["message" => "Data sensor added successfully."] : ["error" => "Failed to add data sensor."];
}

function deleteDataSensor($conn, $id_sensor) {
    $sql = "DELETE FROM data_sensor WHERE id_sensor = ?";
    $stmt = $conn->prepare($sql);
    
    $stmt->execute([$id_sensor]);
    
    return $stmt->rowCount() > 0 ? ["message" => "Data sensor deleted successfully."] : ["error" => "Failed to delete data sensor."];
}
?>
