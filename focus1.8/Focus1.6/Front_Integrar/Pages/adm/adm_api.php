<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../php/MySQLClass.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db   = new MySQLClass();
    $conn = $db->getConnection();

    if ($action === 'me' && $method === 'GET') {
        $uid  = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sessão inválida']);
            exit;
        }
        $stmt = $conn->prepare("SELECT username, photo FROM profiles WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_object();
        $nomeReal = $resultado->username ?? 'Administrador';
        $fotoReal = $resultado->photo ?? null;
        echo json_encode([
            'success' => true,
            'nome' => $nomeReal,
            'foto' => $fotoReal
        ]);
        exit;
    }

    if ($action === 'stats' && $method === 'GET') {
        $totalUsers = (int)($conn->query("SELECT COUNT(*) AS c FROM users")->fetch_object()->c ?? 0);

        /* created_at pode não existir — trata silenciosamente */
        $newToday = 0;
        try {
            $r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE DATE(created_at) = CURDATE()");
            $newToday = (int)($r->fetch_object()->c ?? 0);
        } catch (Throwable $e) {
        }

        $openTickets  = 0;
        $totalTickets = 0;
        try {
            $openTickets  = (int)($conn->query("SELECT COUNT(*) AS c FROM calls WHERE status = 'pending'")->fetch_object()->c ?? 0);
            $totalTickets = (int)($conn->query("SELECT COUNT(*) AS c FROM calls")->fetch_object()->c ?? 0);
        } catch (Throwable $e) {
        }

        echo json_encode([
            'success' => true,
            'stats'   => [
                'total_users'   => $totalUsers,
                'new_today'     => $newToday,
                'open_tickets'  => $openTickets,
                'total_tickets' => $totalTickets,
            ],
        ]);
        exit;
    }

    if ($action === 'recent' && $method === 'GET') {
        $recentUsers = $conn->query("
            SELECT c.call_id AS id, c.code, p.username AS name, u.email, c.subject, c.status, c.priority, c.created_at
                FROM calls c
                LEFT JOIN profiles p ON c.profile_id = p.profile_id
                LEFT JOIN users u ON p.user_id = u.user_id
                ORDER BY c.created_at DESC
                LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);

        $recentTickets = [];
        try {
            $recentTickets = $conn->query("
                SELECT c.call_id AS id, c.code, p.username AS name, u.email, c.subject, c.status, c.priority, c.created_at
                FROM calls c
                LEFT JOIN profiles p ON c.profile_id = p.profile_id
                LEFT JOIN users u ON p.user_id = u.user_id
                ORDER BY c.created_at DESC
                LIMIT 5
            ")->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable $e) {
        }

        echo json_encode([
            'success'        => true,
            'recent_users'   => $recentUsers,
            'recent_tickets' => $recentTickets,
        ]);
        exit;
    }

    if ($action === 'users' && $method === 'GET') {
        $search = trim($_GET['q'] ?? '');

        if ($search !== '') {
            $stmt = $conn->prepare("
                SELECT u.user_id, u.email, p.username, p.photo, p.xp, p.streak,
                       IF(a.adm_id IS NOT NULL, 1, 0) AS is_admin
                FROM users u
                LEFT JOIN profiles p ON u.user_id = p.user_id
                LEFT JOIN admins   a ON u.user_id = a.user_id
                WHERE p.username LIKE ? OR u.email LIKE ?
                ORDER BY u.user_id DESC
                LIMIT 80
            ");
            $like = "%{$search}%";
            $stmt->bind_param('ss', $like, $like);
        } else {
            $stmt = $conn->prepare("
                SELECT u.user_id, u.email, p.username, p.photo, p.xp, p.streak,
                       IF(a.adm_id IS NOT NULL, 1, 0) AS is_admin
                FROM users u
                LEFT JOIN profiles p ON u.user_id = p.user_id
                LEFT JOIN admins   a ON u.user_id = a.user_id
                ORDER BY u.user_id DESC
                LIMIT 80
            ");
        }

        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    if ($action === 'tickets' && $method === 'GET') {
        $status = $_GET['status'] ?? '';
        $valid  = ['pending', 'replied', 'closed']; // Corresponde aos data-status do HTML

        if ($status && in_array($status, $valid, true)) {
            $stmt = $conn->prepare("
            SELECT c.call_id AS id, c.code, p.username AS name, u.email, c.subject, c.message,
                   c.reply, c.status, c.priority, c.created_at, c.updated_at
            FROM calls c
            LEFT JOIN profiles p ON c.profile_id = p.profile_id
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE c.status = ?
            ORDER BY c.created_at DESC
            LIMIT 120
        ");
            $stmt->bind_param('s', $status);
        } else {
            // Se for 'all' ou vazio, traz tudo
            $stmt = $conn->prepare("
            SELECT c.call_id AS id, c.code, p.username AS name, u.email, c.subject, c.message,
                   c.reply, c.status, c.priority, c.created_at, c.updated_at
            FROM calls c
            LEFT JOIN profiles p ON c.profile_id = p.profile_id
            LEFT JOIN users u ON p.user_id = u.user_id
            ORDER BY c.created_at DESC
            LIMIT 120
        ");
        }

        $stmt->execute();
        $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'tickets' => $tickets]);
        exit;
    }

    if ($action === 'update_ticket' && $method === 'POST') {
        $id     = intval($_POST['id']    ?? 0);
        $status = trim($_POST['status'] ?? '');
        $valid  = ['pending', 'replied', 'closed'];

        if ($id <= 0 || !in_array($status, $valid, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE calls SET status = ?, updated_at = NOW() WHERE call_id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
        exit;
    }

    if ($action === 'delete_user' && $method === 'POST') {
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Usuário deletado com sucesso']);
        exit;
    }

    if ($action === 'reply_ticket' && $method === 'POST') {
        $id    = intval($_POST['id']    ?? 0);
        $reply = trim($_POST['reply']   ?? '');

        if ($id <= 0 || empty($reply)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }

        // BUSCAR O E-MAIL, NOME DO USUÁRIO E O ASSUNTO ORIGINAL DO CHAMADO
        $stmtUser = $conn->prepare("
            SELECT u.email, p.username, c.subject, c.message 
            FROM calls c
            LEFT JOIN profiles p ON c.profile_id = p.profile_id
            LEFT JOIN users u ON u.user_id = p.user_id
            WHERE c.call_id = ? 
            LIMIT 1
        ");
        $stmtUser->bind_param('i', $id);
        $stmtUser->execute();
        $userData = $stmtUser->get_result()->fetch_object();

        // Se o email não for encontrado, barramos ANTES de atualizar o banco de dados
        if (!$userData || empty($userData->email)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Não foi possível encontrar o e-mail deste usuário no banco. Verifique as relações entre as tabelas users e profiles.'
            ]);
            exit;
        }

        // DISPARAR O E-MAIL PRIMEIRO
        try {
            $pathException = __DIR__ . '/../PHPMailer/Exception.php';
            $pathPHPMailer = __DIR__ . '/../PHPMailer/PHPMailer.php';
            $pathSMTP      = __DIR__ . '/../PHPMailer/SMTP.php';
            $pathSenha     = __DIR__ . '/senha_email.php';

            if (!file_exists($pathException) || !file_exists($pathPHPMailer) || !file_exists($pathSMTP)) {
                throw new \Exception("Arquivos da biblioteca PHPMailer não foram encontrados.");
            }
            if (!file_exists($pathSenha)) {
                throw new \Exception("Arquivo senha_email.php não encontrado.");
            }

            require_once $pathException;
            require_once $pathPHPMailer;
            require_once $pathSMTP;
            require_once $pathSenha; 

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = USER;
            $mail->Password = PWD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false, 
                    'verify_peer_name' => false, 
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom(USER, 'Suporte - Focus Study');
            
            $nomeDestinatario = htmlspecialchars($userData->username ?? "Usuário");
            $emailDestinatario = $userData->email;
            $assuntoOriginal = htmlspecialchars($userData->subject);
            $mensagemOriginal = nl2br(htmlspecialchars($userData->message));
            $respostaAdmin = nl2br(htmlspecialchars($reply));

            $mail->addAddress($emailDestinatario, $nomeDestinatario);
            $mail->isHTML(true);
            $mail->Subject = "RE: " . $assuntoOriginal . " - Focus Study";

            $mail->Body = "
            <div style='background-color: #0b1120; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; min-height: 100%;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 500px; background-color: #151f32; border: 1.5px solid #22314d; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border-collapse: separate;'>
                <tr>
                    <td style='padding: 40px 32px;'>
                        <div style='text-align: center; margin-bottom: 24px;'>
                            <div style='margin: 0 auto; width: 56px; height: 56px; background-color: rgba(6, 182, 212, 0.1); border: 1.5px solid rgba(6, 182, 212, 0.3); border-radius: 16px; text-align: center;'>
                                <span style='font-size: 28px; line-height: 54px; color: #06b6d4;'>💬</span>
                            </div>
                            <h2 style='color: #ffffff; font-size: 22px; font-weight: 800; margin: 16px 0 4px 0; letter-spacing: -0.02em;'>
                                Chamado Respondido!
                            </h2>
                            <p style='color: #64748b; font-size: 13px; margin: 0;'>Protocolo do Chamado: #{$id}</p>
                        </div>

                        <p style='color: #ffffff; font-size: 15px; font-weight: 700; margin: 0 0 8px 0;'>Olá, {$nomeDestinatario},</p>
                        <p style='color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px 0;'>
                            A equipe de suporte do <strong>Focus Study</strong> enviou uma resposta para a sua solicitação. Veja os detalhes abaixo:
                        </p>

                        <div style='background-color: rgba(6, 182, 212, 0.05); border-left: 4px solid #06b6d4; padding: 16px; border-radius: 4px 12px 12px 4px; margin-bottom: 24px;'>
                            <strong style='color: #06b6d4; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;'>Resposta do Suporte:</strong>
                            <p style='color: #e2e8f0; font-size: 14px; line-height: 1.6; margin: 0;'>{$respostaAdmin}</p>
                        </div>

                        <div style='background-color: #1e293b; padding: 16px; border-radius: 12px; border: 1px solid #2d3748;'>
                            <strong style='color: #94a3b8; font-size: 12px; display: block; margin-bottom: 6px;'>Sua mensagem original ({$assuntoOriginal}):</strong>
                            <p style='color: #64748b; font-size: 13px; line-height: 1.5; margin: 0; font-style: italic;'>\"{$mensagemOriginal}\"</p>
                        </div>

                        <hr style='border: 0; border-top: 1px solid #22314d; margin: 32px 0 20px 0;'>

                        <p style='font-size: 12px; color: #64748b; line-height: 1.5; text-align: center; margin: 0;'>
                            Se precisar de mais ajuda, basta responder diretamente a este e-mail ou abrir um novo ticket pelo painel.
                        </p>
                    </td>
                </tr>
            </table>
            </div>";

            $mail->send();
            
            // ATUALIZAR O CHAMADO NO BANCO APENAS SE O EMAIL FOR ENVIADO COM SUCESSO
            $stmt = $conn->prepare("UPDATE calls SET reply = ?, replied_at = NOW(), status = 'replied', updated_at = NOW() WHERE call_id = ?");
            $stmt->bind_param('si', $reply, $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Resposta gravada e e-mail enviado com sucesso!']);
            
        } catch (PHPMailerException $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'O chamado não foi respondido. Falha na autenticação do e-mail (SMTP): ' . $mail->ErrorInfo
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'O chamado não foi respondido. Erro interno: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Ação não encontrada']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
