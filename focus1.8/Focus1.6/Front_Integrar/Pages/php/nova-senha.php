<?php //Corrigido
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/Focus.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);
    exit;
}

$token = trim($_POST['token'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (empty($token) || strlen($senha) < 6) {
    http_response_code(422);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Dados inválidos.'
    ]);
    exit;
}

try {

    $db = new MySQLClass();

    /* BUSCAR USUÁRIO PELO TOKEN */
    $usuario = $db->search(
        "SELECT id 
         FROM user
         WHERE reset_token = ?
         AND reset_token_exp > NOW()
         LIMIT 1",
        [$token],
        true
    );

    if (!$usuario) {
        http_response_code(400);
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Link inválido ou expirado.'
        ]);
        exit;
    }

    $id = $usuario->id;

    /* GERAR HASH NOVO */
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    /* ATUALIZAR SENHA */
    $db->exec(
        "UPDATE user
         SET password = ?,
             reset_token = NULL,
             reset_token_exp = NULL
         WHERE id = ?",
        [$senhaHash, $id]
    );

    echo json_encode([
        'sucesso'  => true,
        'mensagem' => 'Senha alterada com sucesso!'
    ]);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        'sucesso'  => false,
        'mensagem' => 'Erro interno no servidor.'
    ]);
}