<?php
set_time_limit(120);
ini_set('mysql.connect_timeout', 60);
ini_set('default_socket_timeout', 60);
ob_start();
header('Content-Type: application/json; charset=utf-8');
//corrigido
require_once __DIR__ . "/MySQLClass.php";

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método inválido.");
    }

    $nome = trim($_POST["nome"] ?? "");
    $sobrenome = trim($_POST["sobrenome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (empty($nome) || empty($email) || strlen($senha) < 6) {
        throw new Exception("Dados insuficientes. A senha deve ter pelo menos 6 caracteres.");
    }

    /* LÓGICA DE UPLOAD */
    $avatar = null;
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
        $pasta_destino = __DIR__ . "/uploads/"; 
        
        if (!is_dir($pasta_destino)) {
            mkdir($pasta_destino, 0755, true);
        }

        $avatar = "user_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $pasta_destino . $avatar);
    }

    $mysql = new MySQLClass();
    $conn = $mysql->getConnection(); 

    $conn->begin_transaction();

    $sqlUser = "INSERT INTO users (name, email, password, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sqlUser);
    if (!$stmt) throw new Exception("Erro no prepare users: " . $conn->error);

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    // "sss" significa 3 Strings
    $stmt->bind_param("sss", $nome, $email, $senhaHash);
    $stmt->execute();

    $userId = $conn->insert_id; 

    if (!$userId) throw new Exception("Falha ao gerar ID do usuário.");

    $sqlProfile = "INSERT INTO profiles (user_id, username, photo, created_at, updated_at) 
                   VALUES (?, ?, ?, NOW(), NOW())";
    
    $stmtP = $conn->prepare($sqlProfile);
    if (!$stmtP) throw new Exception("Erro no prepare profiles: " . $conn->error);

    $nomeCompleto = $nome . " " . $sobrenome;
    
    // "iss": Int, String, String
    $stmtP->bind_param("iss", $userId, $nomeCompleto, $avatar);
    $stmtP->execute();

    $conn->commit();
    
    ob_clean();
    echo json_encode(["sucesso" => true, "mensagem" => "Cadastro realizado com sucesso!"]);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    
    ob_clean();
    echo json_encode([
        "sucesso" => false, 
        "mensagem" => "Erro no cadastro: " . $e->getMessage()
    ]);
}
exit;