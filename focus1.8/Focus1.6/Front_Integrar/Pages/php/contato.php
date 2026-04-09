<?php //Corrigido
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/Focus.php';

$db = new MySQLClass();

$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = $_GET['acao']   ?? '';
$listar = $_GET['listar'] ?? '';

/*LISTAR CHAMADOS*/
if ($metodo === 'GET' && $listar === '1') {

    $tickets = $db->search("
        SELECT id, assunto, categoria, prioridade, status, criado_em
        FROM chamados_suporte
        ORDER BY criado_em DESC
        LIMIT 20
    ");

    echo json_encode([
        'sucesso' => true,
        'tickets' => $tickets
    ]);
    exit;
}

/*ATUALIZAR STATUS (ADMIN)*/
if ($metodo === 'POST' && $acao === 'status') {

    $id     = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $validos = ['aberto', 'andamento', 'resolvido'];

    if ($id <= 0 || !in_array($status, $validos)) {
        http_response_code(422);
        exit(json_encode([
            'sucesso' => false,
            'mensagem' => 'Dados inválidos.'
        ]));
    }

    $linhas = $db->exec(
        "UPDATE chamados_suporte 
         SET status = ?, atualizado_em = NOW()
         WHERE id = ?",
        [$status, $id]
    );

    echo json_encode([
        'sucesso'  => $linhas > 0,
        'mensagem' => $linhas > 0 ? 'Status atualizado.' : 'Chamado não encontrado.'
    ]);
    exit;
}

/*BLOQUEIO DE MÉTODO*/
if ($metodo !== 'POST') {
    http_response_code(405);
    exit(json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]));
}

/*NOVO CHAMADO*/

$nome       = trim($_POST['nome'] ?? '');
$email      = trim($_POST['email'] ?? '');
$categoria  = trim($_POST['categoria'] ?? '');
$assunto    = trim($_POST['assunto'] ?? '');
$mensagem   = trim($_POST['mensagem'] ?? '');
$prioridade = trim($_POST['prioridade'] ?? 'baixa');
$ip         = $_SERVER['REMOTE_ADDR'] ?? null;

/* VALIDAÇÕES */
$erros = [];

if (strlen($nome) < 2)       $erros[] = 'Nome muito curto.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
if (strlen($assunto) < 5)    $erros[] = 'Assunto muito curto.';
if (strlen($mensagem) < 10)  $erros[] = 'Mensagem muito curta.';

if (!empty($erros)) {
    http_response_code(422);
    exit(json_encode([
        'sucesso' => false,
        'mensagem' => implode(' ', $erros)
    ]));
}

/*RATE LIMIT (MySQL)*/
if ($ip) {

    $check = $db->search(
        "SELECT COUNT(*) as total 
         FROM chamados_suporte
         WHERE ip = ? 
         AND criado_em > NOW() - INTERVAL 1 HOUR",
        [$ip],
        true
    );

    if ($check && $check->total >= 5) {
        http_response_code(429);
        exit(json_encode([
            'sucesso' => false,
            'mensagem' => 'Muitos pedidos. Aguarde 1 hora.'
        ]));
    }
}

/*INSERT*/
try {

    $db->exec(
        "INSERT INTO chamados_suporte 
        (nome, email, categoria, assunto, mensagem, prioridade, ip, criado_em, atualizado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [
            $nome,
            $email,
            $categoria,
            $assunto,
            $mensagem,
            $prioridade,
            $ip
        ]
    );

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Chamado aberto com sucesso!'
    ]);

} catch (PDOException $e) {

    error_log("Erro suporte: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno ao salvar chamado.'
    ]);
}