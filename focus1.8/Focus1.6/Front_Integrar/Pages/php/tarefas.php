<?php
session_start();
require_once 'MySQLClass.php';
$db = new MySQLClass(); //corrigido

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["profile_id"])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão Inválida']);
    exit;
}

$profileId = $_SESSION["profile_id"];
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === "GET") {
        $sql = "SELECT 
            t.task_id, 
            t.title, 
            COALESCE(sd.done, 0) as done 
        FROM tasks t
        LEFT JOIN schedulings sd ON t.task_id = sd.task_id
        LEFT JOIN schedules s ON sd.schedule_id = s.schedule_id
        WHERE t.profile_id = ? 
        AND (DATE(s.start_time) = ? OR s.start_time IS NULL)
        ORDER BY t.created_at DESC";

        $dataHoje = date('Y-m-d');
        $resultado = $db->searchSafe($sql, [$profileId, $dataHoje]);
        echo json_encode($resultado ?: []);
        exit;
    }

    // --- LÓGICA DE AÇÃO (POST) ---
    else if ($metodo === "POST" && isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        $task_id = $_POST['task_id'] ?? null;

        // Verifica se o task_id existe antes de tentar qualquer ação
        if (!$task_id) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID da tarefa não fornecido.']);
            exit;
        }

        if ($acao === 'toggle') {
            // A. Descobrir se a tarefa HOJE está marcada como done ou não
            $sqlCheckState = "SELECT sch.scheduling_id, sch.done 
                              FROM schedulings sch
                              INNER JOIN schedules s ON sch.schedule_id = s.schedule_id
                              WHERE sch.task_id = ? AND (DATE(s.start_time) = CURDATE() OR s.start_time IS NULL)
                              LIMIT 1";
            $stateRes = $db->searchSafe($sqlCheckState, [$task_id]);
            
            if (!$stateRes) {
                echo json_encode(['sucesso' => false, 'erro' => 'Agendamento para hoje não encontrado para esta tarefa.']);
                exit;
            }
            
            $scheduling_id = $stateRes[0]['scheduling_id'];
            $statusAtual = (int)$stateRes[0]['done'];

            // B. Contar se existem OUTRAS tarefas que ele já concluiu hoje no sistema
            $sqlContagemHoje = "SELECT COUNT(*) AS total 
                                FROM schedulings sch
                                INNER JOIN schedules s ON sch.schedule_id = s.schedule_id
                                WHERE s.profile_id = ? 
                                  AND sch.done = 1 
                                  AND DATE(sch.updated_at) = CURDATE()
                                  AND sch.scheduling_id != ?";
            $resHoje = $db->searchSafe($sqlContagemHoje, [$profileId, $scheduling_id]);
            $jaConcluiuOutraHoje = (int)($resHoje[0]['total'] ?? 0);

            // C. Inverter o status da tarefa no banco de dados e atualizar o timestamp
            $sqlToggle = "UPDATE schedulings SET done = IF(done = 1, 0, 1), updated_at = NOW() WHERE scheduling_id = ?";
            $db->execSafe($sqlToggle, [$scheduling_id]);

            // D. Bônus de XP padrão do seu sistema
            $db->execSafe("UPDATE profiles SET xp = xp + 5 WHERE profile_id = ?", [$profileId]);

            // E. LÓGICA DO STREAK (Apenas 1 ponto por dia único)
            if ($statusAtual === 0) {
                // Tarefa foi concluída agora. Se for a primeira concluída do dia, processa o streak
                if ($jaConcluiuOutraHoje === 0) {
                    $sqlContagemOntem = "SELECT COUNT(*) AS total 
                                         FROM schedulings sch
                                         INNER JOIN schedules s ON sch.schedule_id = s.schedule_id
                                         WHERE s.profile_id = ? 
                                           AND sch.done = 1 
                                           AND DATE(sch.updated_at) = SUBDATE(CURDATE(), 1)";
                    $resOntem = $db->searchSafe($sqlContagemOntem, [$profileId]);
                    $fezOntem = (int)($resOntem[0]['total'] ?? 0);

                    if ($fezOntem > 0) {
                        $db->execSafe("UPDATE profiles SET streak = streak + 1 WHERE profile_id = ?", [$profileId]);
                    } else {
                        $db->execSafe("UPDATE profiles SET streak = 1 WHERE profile_id = ?", [$profileId]);
                    }
                }
            } else {
                // Tarefa foi desmarcada agora. Se o usuário não tiver mais nenhuma tarefa feita hoje, perde o streak do dia
                if ($jaConcluiuOutraHoje === 0) {
                    $db->execSafe("UPDATE profiles SET streak = GREATEST(0, streak - 1) WHERE profile_id = ?", [$profileId]);
                }
            }

            echo json_encode(['sucesso' => true]);
            exit;
        }
    }
}
// Fechamento do bloco try principal que deve envolver todo o código acima
catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}

exit;
