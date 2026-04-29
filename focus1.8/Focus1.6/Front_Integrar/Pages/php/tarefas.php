<?php
session_start();
require_once 'MySQLClass.php';
$db = new MySQLClass();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["profile_id"])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão Inválida']);
    exit;
}

$profileId = $_SESSION["profile_id"];
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // ---LÓGICA DE BUSCA---
    if ($metodo === "GET") {
        $sql = "SELECT t.task_id, t.title, COALESCE(s.done, 0) as done 
                FROM tasks t
                LEFT JOIN schedulings s ON t.task_id = s.task_id
                WHERE t.profile_id = ? 
                ORDER BY t.created_at DESC";

        $resultado = $db->searchSafe($sql, [$profileId]);
        echo json_encode($resultado ?: []);
        exit;
    }

    // ---LÓGICA DE AÇÃO---
    else if ($metodo === "POST" && isset($_POST['acao'])) {

        // AÇÃO: INSERIR NOVA TAREFA
        if ($_POST['acao'] === 'inserir') {
            $titulo = trim($_POST['titulo'] ?? '');
            if (!empty($titulo)) {
                $conn = $db->getConnection();
                try {
                    $conn->begin_transaction();

                    // Verificação e criação automática de Schedule
                    $sqlCheckSched = "SELECT schedule_id FROM schedules WHERE profile_id = ? LIMIT 1";
                    $resSched = $db->searchSafe($sqlCheckSched, [$profileId]);

                    if (empty($resSched)) {
                        $db->execSafe("INSERT INTO schedules (profile_id, time) VALUES (?, NOW())", [$profileId]);
                        $sid = $db->lastInsertId();
                    } else {
                        $sid = $resSched[0]['schedule_id'];
                    }

                    // Inserção da Task e do Vínculo (FAVOR NÃO MEXER NISSO, TOMOU UM TEMPO DO K7)
                    $db->execSafe("INSERT INTO tasks (profile_id, title, priority) VALUES (?, ?, 'low')", [$profileId, $titulo]);
                    $taskId = $db->lastInsertId();

                    $db->execSafe("INSERT INTO schedulings (schedule_id, task_id, done) VALUES (?, ?, 0)", [$sid, $taskId]);

                    $conn->commit();
                    echo json_encode(['sucesso' => true]);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
                }
            }
            exit;
        }

        // AÇÃO: ALTERAR STATUS
        else if ($_POST['acao'] === 'toggle') {
            $task_id = $_POST['task_id'] ?? null;

            if ($task_id) {
                $sql = "UPDATE schedulings SET done = NOT done WHERE task_id = ?";
                $db->execSafe($sql, [$task_id]);

                echo json_encode(['sucesso' => true]);
                exit;
            }
        }
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}

exit;
