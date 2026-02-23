<?php
include __DIR__ . '/../app/classes/StrBuilder.php';
include __DIR__ . '/../app/classes/Sql.php';
session_start();

$login = $_SESSION["login"] ?? null;

if (isset($_POST["botao"])) {
    $nome = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $email = $_POST["usuario"];
    $senha = $_POST["senha"];
    $conf_senha = $_POST["conf_senha"];

    if ($senha !== $conf_senha) {
        echo "<script>alert('As senhas não conferem!');</script>";
        exit;
    }

    $comando = new StrBuilder();
    $sql = new Sql();

    if (empty($login)) {
        $comando->appendLine("INSERT INTO tb_usuario");
        $comando->appendLine("(nome_user, cpf_user, email_user, senha_user)");
        $comando->appendLine("VALUES");
        $comando->appendLine("(?,?,?,?)");
        $sql->query($comando->getStr(), array($nome, $cpf, $email, $senha));
    } else {
        $comando->appendLine("UPDATE tb_usuario");
        $comando->appendLine("SET nome_user = ?, cpf_user = ?, email_user = ?, senha_user = ?");
        $comando->appendLine("WHERE email_user = ? AND senha_user = ?");
        $sql->query(
            $comando->getStr(),
            array($nome, $cpf, $email, $senha, $login->email_user, $login->senha_user)
        );
    }

    $login = $sql->select(
        "SELECT * FROM tb_usuario WHERE email_user = ? AND senha_user = ?",
        array($email, $senha),
        true
    );
    $_SESSION["login"] = $login;

    header("location: ../public/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/cadastro.css">
</head>
<body>
<fieldset>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="nome" value="<?= $login->nome_user ?? '' ?>" placeholder="Digite seu nome" required>

        <label>CPF:</label>
        <input type="text" name="cpf" value="<?= $login->cpf_user ?? '' ?>" placeholder="Digite seu CPF" required maxlength="14">

        <label>E-mail:</label>
        <input type="email" name="usuario" value="<?= $login->email_user ?? '' ?>" placeholder="Digite seu e-mail" required>

        <label>Senha:</label>
        <input type="password" name="senha" value="<?= $login->senha_user ?? '' ?>" placeholder="Digite sua senha" required>

        <label>Confirmar senha:</label>
        <input type="password" name="conf_senha" placeholder="Confirme sua senha" required>

        <input type="submit" name="botao" value="Confirmar dados">
    </form>
</fieldset>
</body>
</html>
