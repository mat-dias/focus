<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluindo os arquivos do PHPMailer moderno
require_once __DIR__ . '/../../app/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../app/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../app/phpmailer/src/Exception.php';

// Incluindo classe Sql para acessar o banco
include __DIR__ . '/../../app/classes/Sql.php';

// Configurações do email remetente
define("EMAIL_REMETENTE", "slender.adobe@gmail.com");
define("SENHA_REMETENTE", "yhrm uton fdpp apnc");

if (isset($_POST["email"])) {

    $emailUsuario = $_POST["email"];
    $sql = new Sql();

    // Verifica se o email existe no banco
    $resultado = $sql->select(
        "SELECT * FROM tb_usuario WHERE email_user = ?",
        [$emailUsuario],
        true
    );

    if ($resultado) {
        // Link para redefinir a senha (pode incluir token para maior segurança)
        $link = "http://localhost/projeto/views/RecSenha/RedefinirSenha.php?email=" . urlencode($emailUsuario);

        $mail = new PHPMailer(true);

        try {
            // Configuração do SMTP
            $mail->isSMTP();
            $mail->SMTPDebug = 0; // Debug: 0 = desligado
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = "tls";
            $mail->Host = "smtp.gmail.com";
            $mail->Port = 587;
            $mail->Username = EMAIL_REMETENTE;
            $mail->Password = SENHA_REMETENTE;

            // Remetente e destinatário
            $mail->setFrom(EMAIL_REMETENTE, "Slender");
            $mail->addAddress($emailUsuario);

            // Conteúdo do email
            $mail->isHTML(true);
            $mail->Subject = "Redefinir senha";
            $mail->Body = "Clique no link para redefinir sua senha:<br>
                           <a href='$link'>$link</a>";

            $mail->send();
            echo "<script>alert('Mensagem enviada ao email $emailUsuario');</script>";

        } catch (Exception $e) {
            echo "<script>alert('Erro ao enviar email: {$mail->ErrorInfo}');</script>";
        }

    } else {
        echo "<script>alert('Email não encontrado!');</script>";
    }
}
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Esqueceu a Senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px #aaa;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        input[type="email"], input[type="submit"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <form method="post" action="">
        <label>Email:</label>
        <input type="email" name="email" placeholder="Digite seu email" required>
        <input type="submit" value="Enviar">
    </form>
</body>
</html>
