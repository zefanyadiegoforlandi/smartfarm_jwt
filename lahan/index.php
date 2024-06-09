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
    $data = $_POST;
    $conn = $database;

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

        $id_lahan = $_GET['id_lahan'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];

        if ($level === 'admin') {
            switch ($method) {
                case 'GET':
                    $result = $id_lahan ? getLahanById($database, $id_lahan) : getAllLahans($database);
                    break;
                case 'POST':
                    $result = $id_lahan ? updateLahan($database, $id_lahan, $data) : addLahan($database, $data);
                    break;
                case 'DELETE':
                    $result = $id_lahan ? deleteLahan($database, $id_lahan) : respond(400, "Please provide an ID to delete.");
                    break;
                default:
                    respond(405, "Method not allowed.");
                    break;
            }
        } else {
            switch ($method) {
                case 'GET':
                    $result = $id_lahan ? getLahanById($database, $id_lahan) : getAllLahans($database);
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

function getAllLahans($conn) {
    $sql = "SELECT * FROM lahan";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLahanById($conn, $id_lahan) {
    $sql = "SELECT * FROM lahan WHERE id_lahan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_lahan]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: "Lahan not found.";
}


function addLahan($conn, $data) {
    $missing_fields = [];
    $required_fields = ['id_lahan', 'nama_lahan', 'alamat_lahan', 'luas_lahan', 'id_user'];

    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        return "Field yang wajib diisi kosong atau hilang: " . implode(', ', $missing_fields);
    }

    $sql = "INSERT INTO lahan (id_lahan, nama_lahan, alamat_lahan, luas_lahan, id_user) VALUES (:id_lahan, :nama_lahan, :alamat_lahan, :luas_lahan, :id_user)";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute([
            ':id_lahan' => $data['id_lahan'],
            ':nama_lahan' => $data['nama_lahan'],
            ':alamat_lahan' => $data['alamat_lahan'],
            ':luas_lahan' => $data['luas_lahan'],
            ':id_user' => $data['id_user']
        ]);
        return $stmt->rowCount() > 0 ? "Lahan berhasil ditambahkan." : "Gagal menambahkan lahan.";
    } catch (PDOException $e) {
        return "Kesalahan: " . $e->getMessage();
    }
}

function updateLahan($conn, $id_lahan, $data) {
    if (empty($data)) {
        return "Tidak ada data yang diberikan untuk diperbarui.";
    }

    $sql = "UPDATE lahan SET";
    $params = [];
    $allowed_fields = ['alamat_lahan', 'nama_lahan', 'luas_lahan', 'id_user'];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $sql .= " $key = :$key,";
            $params[$key] = $value;
        }
    }

    $sql = rtrim($sql, ',');
    $sql .= " WHERE id_lahan = :id_lahan";
    $params['id_lahan'] = $id_lahan;

    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute($params);
        return $stmt->rowCount() > 0 ? "Lahan berhasil diperbarui." : "Tidak ada perubahan yang dilakukan pada lahan.";
    } catch (PDOException $e) {
        return "Kesalahan: " . $e->getMessage();
    }
}

function deleteLahan($conn, $id_lahan) {
    $sql = "DELETE FROM lahan WHERE id_lahan = ?";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute([$id_lahan]);
        return $stmt->rowCount() > 0 ? "Lahan berhasil dihapus." : "Gagal menghapus lahan.";
    } catch (PDOException $e) {
        return "Kesalahan: " . $e->getMessage();
    }
}
?>
