<?php
include __DIR__ . '/../app/classes/Sql.php';
include __DIR__ . '/../app/classes/StrBuilder.php';

session_start();
unset($_SESSION["login"]);

if (isset($_POST["botao"])) {
    $botao = $_POST["botao"];
    $user = $_POST["user"] ?? "";
    $pass = $_POST["pass"] ?? "";

    switch ($botao) {

        case "Cadastro":
            header("location: ../views/TelaCadastro.php");
            exit;

        case "Login":
            if (empty($user) || empty($pass)) {
                echo "<script>
                        alert('Preencha todos os campos!');
                        window.location = 'index.php';
                      </script>";
                exit;
            }

            $comando = new StrBuilder();
            $select = new Sql();

            $comando->appendLine("SELECT * FROM tb_usuario");
            $comando->appendLine("WHERE email_user = ? AND senha_user = ?");

            $dados = $select->select(
                $comando->getStr(),
                array($user, $pass),
                true
            );

            if ($dados) {
                $_SESSION["login"] = $dados;
                header("location: ../views/Gerenciar.php");
                exit;
            } else {
                echo "<script>alert('Login não encontrado');</script>";
            }
            break;

        case "Esqueci minha senha":
            header("location: ../views/RecSenha/EsqueceuSenha.php");
            exit;

        default:
            header("location: index.php");
            exit;
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <fieldset>
        <form method="post" action="index.php">

            <div class="form-group">
                <label>Usuário:</label>
                <input type="email" name="user" placeholder="Digite seu email">
            </div>

            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="pass" placeholder="Digite sua senha">
            </div>

            <div class="btn-group">
                <input type="submit" name="botao" value="Cadastro">
                <input type="submit" name="botao" value="Login">
                <input type="submit" name="botao" value="Esqueci minha senha">
            </div>

        </form>
    </fieldset>
</body>
</html>

