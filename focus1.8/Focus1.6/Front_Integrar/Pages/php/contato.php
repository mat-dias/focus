<?php
ob_start();
session_start(); //corrigido
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/MySQLClass.php';


try {
    $db = new MySQLClass();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $acao   = $_GET['acao'] ?? '';

    // LOGIN DO MODAL ADMIN
    if ($metodo === 'POST' && $acao === 'login') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $sql = "SELECT u.user_id, u.password, a.adm_id 
                FROM users u 
                INNER JOIN admins a ON u.user_id = a.user_id 
                WHERE u.email = ? LIMIT 1";

        $usuario = $db->search($sql, [$email], false);

        if ($usuario && password_verify($senha, $usuario->password)) {
            $_SESSION['admin_id'] = $usuario->adm_id;
            $_SESSION['user_id']  = $usuario->user_id;
            $_SESSION['role']     = 'admin';

            if (ob_get_length()) ob_clean();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Admin autorizado!']);
        } else {
            throw new Exception('Acesso negado. Credenciais incorretas.');
        }
        exit;
    }

    // NOVO CHAMADO
    if ($metodo === 'POST' && empty($acao)) {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            throw new Exception("Você precisa estar logado para abrir um chamado.");
        }

        // Busca o perfil real do usuário logado
        $resPerfil = $db->search("SELECT profile_id FROM profiles WHERE user_id = ?", [$userId], false);
        $profileId = $resPerfil->profile_id ?? null;

        if (!$profileId) throw new Exception("Perfil de usuário não encontrado.");

        $assunto   = trim($_POST['assunto'] ?? '');
        $mensagem  = trim($_POST['mensagem'] ?? '');

        // MAPEAMENTO
        $mapPrio = [
            'baixa' => 'low',
            'media' => 'medium',
            'alta'  => 'high'
        ];

        $prioPT = $_POST['prioridade'] ?? 'baixa';
        $prioridade = $mapPrio[$prioPT] ?? 'low'; // Traduz para o banco

        $codigo = "CALL-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $sql = "INSERT INTO calls (code, profile_id, subject, message, priority, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";

        $sucesso = $db->exec($sql, [$codigo, $profileId, $assunto, $mensagem, $prioridade]);

        if ($sucesso) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['sucesso' => true, 'mensagem' => "Chamado $codigo enviado!"]);
        } else {
            throw new Exception("Erro ao gravar chamado no banco.");
        }
        exit;
    }

    // LISTAR CHAMADOS
    if ($metodo === 'GET' && isset($_GET['listar'])) {
        $sql = "SELECT code, subject, priority, status, created_at 
                FROM calls ORDER BY created_at DESC LIMIT 10";
        $tickets = $db->search($sql);

        if (ob_get_length()) ob_clean();
        echo json_encode(['sucesso' => true, 'tickets' => $tickets ?: []]);
        exit;
    }
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    // Tratamento para o erro 2002 de conexão remota
    $msg = (strpos($e->getMessage(), '2002') !== false)
        ? "Erro de conexão com o banco remoto."
        : $e->getMessage();

    echo json_encode(['sucesso' => false, 'mensagem' => $msg]);
}
