<?php
include __DIR__ . "/../../app/classes/StrBuilder.php";
include __DIR__ . "/../../app/classes/Sql.php";

if (isset($_POST["botao"])) {
    $nova_senha = trim($_POST["nova_senha"] ?? '');
    $email = trim($_POST["email"] ?? '');

    if (empty($nova_senha) || empty($email)) {
        echo "<script>alert('Por favor, preencha todos os campos!');</script>";
    } else {
        $sql = new Sql();

        // Verifica se o email existe
        $usuario = $sql->select(
            "SELECT * FROM tb_usuario WHERE email_user = ?", 
            array($email), 
            true
        );

        if ($usuario) {
            // Cria hash seguro da nova senha
            $senha_hashed = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Prepara comando update
            $comando = new StrBuilder();
            $comando->appendLine("UPDATE tb_usuario");
            $comando->appendLine("SET senha_user = ?");
            $comando->appendLine("WHERE email_user = ?");

            try {
                $resultado = $sql->query($comando->getStr(), array($senha_hashed, $email));

                if ($resultado) {
                    echo "<script>alert('Senha alterada com sucesso!');</script>";
                } else {
                    echo "<script>alert('Erro ao alterar a senha!');</script>";
                }
            } catch (PDOException $e) {
                echo "Erro PDO: " . $e->getMessage();
            } catch (Exception $e) {
                echo "Erro: " . $e->getMessage();
            }
        } else {
            echo "<script>alert('Email não encontrado!');</script>";
        }
    }
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha</title>
</head>
<body>
    <form method="post" action="">
        <label>Email:</label><br>
        <input type="email" name="email" placeholder="Digite seu email" required><br>

        <label>Nova senha:</label><br>
        <input type="password" name="nova_senha" placeholder="Digite a nova senha" required><br><br>

        <input type="submit" name="botao" value="Enviar">
    </form>
</body>
</html>
