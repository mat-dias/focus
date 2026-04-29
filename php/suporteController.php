<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/Focus.php';

$db = new MySQLClass();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/*LISTAR CHAMADOS*/
if ($method === 'GET') {

    $tickets = $db->search("
        SELECT * FROM support_tickets
        ORDER BY created_at DESC
        LIMIT 50
    ");

    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);
    exit;
}

/*ATUALIZAR STATUS*/
if ($method === 'POST' && $action === 'status') {

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $validStatus = ['open', 'in_progress', 'resolved'];

    if ($id <= 0 || !in_array($status, $validStatus)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid data'
        ]);
        exit;
    }

    $db->exec(
        "UPDATE support_tickets 
         SET status = ?, updated_at = NOW()
         WHERE id = ?",
        [$status, $id]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    exit;
}

/*CRIAR CHAMADO */
if ($method === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $category = $_POST['category'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'low';

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    if (
        strlen($name) < 2 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($subject) < 5 ||
        strlen($message) < 10
    ) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid fields'
        ]);
        exit;
    }

    $db->exec(
        "INSERT INTO support_tickets
        (name, email, category, subject, message, priority, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$name, $email, $category, $subject, $message, $priority, $ip]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Ticket created successfully'
    ]);
    exit;
}