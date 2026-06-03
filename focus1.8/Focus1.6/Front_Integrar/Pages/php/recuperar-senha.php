<?php
//corrigido
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

try {
    require_once __DIR__ . '/../PHPMailer/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/SMTP.php';
    require_once __DIR__ . '/senha_email.php';
    require_once __DIR__ . '/MySQLClass.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        throw new Exception('Método inválido.');

    $email = trim($_POST['email'] ?? '');
    if (empty($email)) throw new Exception('E-mail é obrigatório.');

    $db = new MySQLClass();


    $resultados = $db->searchSafe(
        "SELECT u.user_id, p.username 
         FROM users u 
         LEFT JOIN profiles p ON u.user_id = p.user_id 
         WHERE u.email = ? LIMIT 1",
        [$email]
    );
    error_log(print_r($resultados, true));

    if (!$resultados || count($resultados) === 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Se o e-mail existir, você receberá o link.']);
        exit;
    }

    $usuario = $resultados[0];

    $userId = $usuario['user_id']; // Pegando do array associativo
    $nomeExibicao = $usuario['username'] ?? "Usuário";

    if (empty($userId)) {
        throw new Exception("Erro interno: ID do usuário não encontrado nos dados recuperados.");
    }

    $token = bin2hex(random_bytes(32));

    $db->execSafe("DELETE FROM tokens WHERE user_id = ?", [$userId]);

    $db->execSafe(
        "INSERT INTO tokens (user_id, content, sent_at) VALUES (?, ?, NOW())",
        [$userId, $token]
    );

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = USER;
    $mail->Password = PWD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    /*
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    */
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->SMTPOptions = [
        'ssl' => [
        'verify_peer' => false, 
        'verify_peer_name' => false, 
        'allow_self_signed' => true
        ]];
    //quando lançar no web, derrubar esse SMTOptions, ele vai mandar o email no spam
    $mail->setFrom(USER, 'Focus Study');
    $nomeExibicao = htmlspecialchars($usuario['username'] ?? "Usuario");
    $mail->addAddress($email, $nomeExibicao);
    $mail->isHTML(true);
    $mail->Subject = "Recuperacao de Senha - Focus Study";
    //quando mudar para server Web, alterar esse link
    $link = "colocarURLnovadosite?token=" . $token;

    // BOTÃO NO E-MAIL
    $mail->Body = "
    <div style='background-color: #0b1120; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; min-height: 100%;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 460px; background-color: #151f32; border: 1.5px solid #22314d; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border-collapse: separate;'>
        <tr>
            <td style='padding: 40px 32px; text-align: center;'>
                
                <div style='margin: 0 auto 24px auto; width: 56px; height: 56px; background-color: rgba(6, 182, 212, 0.1); border: 1.5px solid rgba(6, 182, 212, 0.3); border-radius: 16px; display: inline-block; vertical-align: middle; text-align: center;'>
                    <span style='font-size: 28px; line-height: 54px; color: #06b6d4;'>🔑</span>
                </div>

                <h2 style='color: #ffffff; font-size: 24px; font-weight: 800; margin: 0 0 12px 0; letter-spacing: -0.02em;'>
                    Olá, {$nomeExibicao}!
                </h2>
                
                <p style='color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 32px 0;'>
                    Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para escolher uma nova credencial:
                </p>

                <div style='margin: 30px 0;'>
                    <a href='{$link}' style='background-color: #06b6d4; background-image: linear-gradient(135deg, #06b6d4 0%, #ec4899 100%); color: #ffffff; padding: 15px 32px; text-decoration: none; border-radius: 12px; font-size: 15px; font-weight: 700; display: inline-block; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); letter-spacing: 0.03em;'>
                        REDEFINIR SENHA
                    </a>
                </div>

                <hr style='border: 0; border-top: 1px solid #22314d; margin: 32px 0 20px 0;'>

                <p style='font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;'>
                    Se você não solicitou essa alteração, nenhuma ação é necessária. Por segurança, ignore e delete este e-mail.
                </p>
                
            </td>
        </tr>
    </table>
    
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 460px; text-align: center;'>
        <tr>
            <td style='padding-top: 24px;'>
                <p style='font-size: 12px; color: #475569; margin: 0;'>
                    Mensagem automática enviada pelo sistema de autenticação.
                </p>
            </td>
        </tr>
    </table>
</div>";

    $mail->send();
    echo json_encode(['sucesso' => true, 'mensagem' => 'E-mail enviado com sucesso, verifique sua caixa de E-mail!']);
} catch (PHPMailerException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => "Erro de Conexão SMTP: " . $mail->ErrorInfo]);
} catch (Throwable $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => "Erro: " . $e->getMessage()]);
}


//http://localhost/focus1.8/focus1.6/Pages/nova-senha.html?token=
