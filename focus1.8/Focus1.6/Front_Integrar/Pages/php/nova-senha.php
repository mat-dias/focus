<?php
//corrigido
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

require_once __DIR__ . "/MySQLClass.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (empty($token)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token ausente.']);
    exit;
}

if (strlen($senha) < 6) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 6 caracteres.']);
    exit;
}


//verifica a existencia de um token
try {
    $db = new MySQLClass();
    $tokenData = $db->searchSafe(
        "SELECT user_id FROM tokens WHERE content = ? AND sent_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1",
        [$token]
    );

    //Verifica se o token existe e é válido
    if (!$tokenData || count($tokenData) === 0) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Link inválido ou expirado. Solicite uma nova recuperação.'
        ]);
        exit;
    }

    $userId = $tokenData[0]['user_id'];

    //  Gera o hash seguro
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Atualiza a senha 
    $db->execSafe(
        "UPDATE users SET password = ? WHERE user_id = ?",
        [$senhaHash, $userId]
    );

    $db->execSafe("DELETE FROM tokens WHERE content = ?", [$token]);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Senha alterada com sucesso! Redirecionando...'
    ]);
} catch (Exception $e) {
    error_log("Erro ao resetar senha: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno no servidor.']);
}
