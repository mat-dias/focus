<?php
header('Content-Type: application/json; charset=utf-8');
//Corrigido

require_once __DIR__ . "/MySQLClass.php"; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$senha = trim($_POST['senha'] ?? '');

// Validação básica
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token ausente.']);
    exit;
}

if (strlen($senha) < 6) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter no mínimo 6 caracteres.']);
    exit;
}

try {
    $db = new MySQLClass();

    // Verificar se o token existe e não expirou
    $usuario = $db->search(
        "SELECT id FROM users WHERE reset_token = ? AND reset_token_exp > NOW() LIMIT 1",
        [$token],
        true
    );

    if (!$usuario) {
        http_response_code(400);
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Este link de recuperação é inválido ou já expirou.'
        ]);
        exit;
    }

    // Gerar o hash seguro
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Atualizar a senha e LIMPAR o token
    $db->exec(
        "UPDATE users SET password = ?, reset_token = NULL, reset_token_exp = NULL WHERE id = ?",
        [$senhaHash, $usuario->id]
    );

    echo json_encode([
        'sucesso'  => true,
        'mensagem' => 'Senha alterada com sucesso! Redirecionando...'
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao processar sua solicitação.']);
}