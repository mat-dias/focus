<?php
session_start();

include __DIR__ . '/../app/classes/Sql.php';
include __DIR__ . '/../app/classes/StrBuilder.php';

$login = $_SESSION["login"] ?? null;

if (!$login) {
    header("location: ../views/TelaCadastro.php");
    exit;
}

$login = (object) $login;

$comando = new StrBuilder();
$sql = new Sql();

$comando->appendLine("SELECT nome_pedido, cpf_pedido, banco_pedido,");
$comando->appendLine("conta_pedido, taxa_pedido, meses_pedido,");
$comando->appendLine("capital_pedido, rendimento_pedido, total_pedido");
$comando->appendLine("FROM tb_pedido");
$comando->appendLine("WHERE id_user = ?");

$select = $sql->select($comando->getStr(), array($login->id_user)) ?? [];

if (isset($_POST["botao"])) {
    try {
        $botao = $_POST["botao"];

        switch ($botao) {
            case "Alterar":
                $nome = $_POST["nome"];
                $email = $_POST["email"];

                $comando->clear();
                $comando->appendLine("UPDATE tb_usuario");
                $comando->appendLine("SET nome_user = ?, email_user = ?");
                $comando->appendLine("WHERE email_user = ?");
                $sql->query($comando->getStr(), array($nome, $email, $login->email_user));

                $login = $sql->select("SELECT * FROM tb_usuario WHERE email_user = ?", [$email], true);
                $_SESSION["login"] = $login;

                echo "<script>alert('Dados atualizados com sucesso!'); window.location = 'Gerenciar.php';</script>";
                break;
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
?>

<!doctype html>

<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/gerenciar.css">
</head>
<body>

<header>
    <div class="logo" onclick="location.href='../public/index.php'">💠 MinhaLogo</div>
</header>

<main>
    <fieldset class="form-fieldset">
        <form method="post">
            <label>Nome:</label>
            <input type="text" name="nome" placeholder="Altere seu nome" 
                   value="<?= htmlspecialchars($login->nome_user ?? '') ?>" required>

        <label>Usuário:</label>
        <input type="email" name="email" placeholder="Altere seu email" 
               value="<?= htmlspecialchars($login->email_user ?? '') ?>" required><br><br>

        <input type="submit" name="botao" value="Alterar">
    </form>
</fieldset>

<form action="Pedido/IndexBanco.php" method="get">
    <button type="submit" class="add-pedido-btn">Adicionar Pedido</button>
</form>

<h3>Seus pedidos:</h3>
<table>
    <tr>
        <th>Nome pedido</th>
        <th>CPF</th>
        <th>Banco</th>
        <th>Conta</th>
        <th>Taxa</th>
        <th>Meses</th>
        <th>Capital</th>
        <th>Rendimento</th>
        <th>Total</th>
    </tr>
    <?php foreach ($select as $pedido): ?>
    <tr>
        <td><?= $pedido->nome_pedido ?></td>
        <td><?= $pedido->cpf_pedido ?></td>
        <td><?= $pedido->banco_pedido ?></td>
        <td><?= $pedido->conta_pedido ?></td>
        <td><?= $pedido->taxa_pedido ?></td>
        <td><?= $pedido->meses_pedido ?></td>
        <td><?= $pedido->capital_pedido ?></td>
        <td><?= $pedido->rendimento_pedido ?></td>
        <td><?= $pedido->total_pedido ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</main>

</body>
</html>
