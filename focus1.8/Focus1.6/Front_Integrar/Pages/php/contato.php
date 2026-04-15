<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
//Corrigido

require_once __DIR__ . '/MySQLClass.php';
$db = new MySQLClass();

$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = $_GET['acao'] ?? '';
$listar = $_GET['listar'] ?? '';

/* LISTAR CHAMADOS */
if ($metodo === 'GET' && $listar === '1') {
    $tickets = $db->search("SELECT id, name, email, category, subject, message, priority, status, created_at FROM support_tickets ORDER BY created_at DESC LIMIT 20");
    echo json_encode(['sucesso' => true, 'tickets' => $tickets ?: []]);
    exit;
}

/* LOGIN ADMINISTRATIVO */
if ($metodo === 'POST' && $acao === 'login') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $usuario = $db->search("SELECT id, password, role FROM users WHERE email = ? LIMIT 1", [$email]);

    if ($usuario && count($usuario) > 0) {
        $user = $usuario[0];

        $hash  = isset($user->password) ? $user->password : $user['password'];
        $role  = isset($user->role) ? $user->role : $user['role'];
        $uid   = isset($user->id) ? $user->id : $user['id'];

        if (password_verify($senha, $hash)) {
            if ($role === 'admin') {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['admin_id'] = $uid;
                $_SESSION['role']     = 'admin';

                echo json_encode([
                    'sucesso' => true,
                    'role'    => 'admin',
                    'mensagem' => 'Acesso autorizado!'
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado: privilégios insuficientes.']);
            }
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
    }
    exit;
}

/* ATUALIZAR STATUS */
if ($metodo === 'POST' && $acao === 'status') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $db->exec("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

/* SEGURANÇA */
if ($metodo !== 'POST') {
    http_response_code(405);
    exit(json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']));
}

/* NOVO CHAMADO  */
$nome       = trim($_POST['nome'] ?? '');
$email      = trim($_POST['email'] ?? '');
$mensagem   = trim($_POST['mensagem'] ?? '');

if (strlen($nome) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit(json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']));
}

try {
    $db->exec(
        "INSERT INTO support_tickets (name, email, category, subject, message, priority, status, ip_address, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'aberto', ?, NOW(), NOW())",
        [$nome, $email, $_POST['categoria'], $_POST['assunto'], $mensagem, $_POST['prioridade'], $_SERVER['REMOTE_ADDR']]
    );
    echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado aberto com sucesso!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao salvar.']);
}