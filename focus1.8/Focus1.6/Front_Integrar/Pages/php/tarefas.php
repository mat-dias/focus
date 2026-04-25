<?php //corrigido
session_start();
require_once 'MySQLClass.php';
$db = new MySQLClass();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["profile_id"])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão Inválida']);
    exit;
}

$user = $_SESSION['user_id'];
$profileId = $_SESSION['profile_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === "GET") {
    $sql = "SELECT task_id, title, done FROM tasks WHERE profile_id = ? ORDER BY created_at DESC";
    $resultado = $db->searchSafe($sql, [$profileId]);

    if (!$resultado) {
        echo json_encode([]);
    } else {
        echo json_encode($resultado);
    }
    exit;
    } else if ($metodo === "POST" && isset($_POST['acao']) && $_POST['acao'] === 'inserir') {
        $titulo = trim($_POST['titulo'] ?? '');
        if (!empty($titulo)) {
            $sql = "INSERT INTO tasks (profile_id, title, done) VALUES (?, ?, 0)";
            $db->execSafe($sql, [$profileId, $titulo]);
            echo json_encode(['sucesso' => true]);
        }
    } else if ($metodo === "POST" && isset($_POST['acao']) && $_POST['acao'] === 'toggle') {
        $id = $_POST['task_id'] ?? null;
        if ($id) {
            $sql = "UPDATE tasks SET done = NOT done WHERE task_id = ? AND profile_id = ?";
            $db->execSafe($sql, [$id, $profileId]);
            echo json_encode(['sucesso' => true]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
exit;
