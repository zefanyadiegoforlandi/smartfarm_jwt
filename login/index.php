<?php
require_once '../vendor/autoload.php';
require_once '../config.php';

use Firebase\JWT\JWT;

function generateJWT($id, $level, $secret_key) {
    $payload = array(
        "id" => $id,
        "level" => $level
    );
    return JWT::encode($payload, $secret_key, 'HS256'); 
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$secret_key = "sm4rtf4rm"; 

try {
    if (!empty($email) && !empty($password)) {
        $database = getDbConnection(); // Pastikan koneksi database
        $statement = $database->prepare("SELECT id, password, level FROM users WHERE email = :email");
        $statement->bindParam(':email', $email);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $id = $user['id'];
                $level = $user['level']; // Ambil nilai level dari kolom 'level' di database
                $token = generateJWT($id, $level, $secret_key);

                http_response_code(200);
                echo json_encode(array("message" => "Token berhasil dibuat", "token" => $token));
                exit; 
            }
        }
        
        http_response_code(401);
        echo json_encode(array("message" => "Email atau password salah"));
        exit; 
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Permintaan tidak valid"));
        exit; 
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => $e->getMessage()));
    exit;
}
?>
