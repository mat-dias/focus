<?php
session_start(); //corrigido
header('Content-Type: application/json; charset=utf-8');

// Carrega classe do banco MySQL
require_once __DIR__ . 'conexao.php';

// Rejeita requisições que não sejam do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

// Captura e limpa dados de login enviados
$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['password'] ?? '');

// Verifica se os campos obrigatórios foram preenchidos
if (empty($email) || empty($senha)) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha e-mail e senha.']);
    exit;
}

try {
    $sql = "SELECT id, name, email, password, tipo FROM user WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    /*VALIDAÇÃO DE SENHA */
    if (!$usuario || !password_verify($senha, $usuario["password"])) {
        http_response_code(401);
        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Usuário ou senha inválidos"
        ]);
        exit;
    }

    session_regenerate_id(true);

    /* SESSÃO */
    $_SESSION["id"] = $usuario["id"];
    $_SESSION["nome"] = $usuario["name"];
    $_SESSION["tipo"] = $usuario["tipo"];

    /* REDIRECIONAMENTO */
    $redirect = ($usuario["tipo"] === "admin")
        ? "../admin/dashboard.php"
        : "../dashboard/dashboard.php";

    echo json_encode([
        "sucesso" => true,
        "nome" => $usuario["name"],
        "redirect" => $redirect
    ]);
} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro Eterno."
    ]);
}
