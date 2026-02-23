<?php
session_start();

include __DIR__ . '/../../app/classes/Sql.php';
include __DIR__ . '/../../app/classes/StrBuilder.php';

$comando = new StrBuilder();
$sql = new Sql();

// Pegando dados da sessão
$dados = $_SESSION["login"];

// Calculando rendimento e total
$capital = $_SESSION["capital"];
$taxa = $_SESSION["taxa"] / 100;
$tempo = $_SESSION["tempo"];

$rendimento = $capital * pow(1 + $taxa, $tempo) - $capital;
$total = $capital + $rendimento;

// Formatando valores
$capital_fmt = number_format($capital, 2, ',', '.');
$rendimento_fmt = number_format($rendimento, 2, ',', '.');
$total_fmt = number_format($total, 2, ',', '.');

// Salvando em sessão
$_SESSION["rendimento"] = $rendimento;
$_SESSION["total"] = $total;
$_SESSION["nome"] = $dados->nome_user;
$_SESSION["cpf"] = $dados->cpf_user;
$_SESSION["id"] = $dados->id_user;
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedido</title>
    <link rel="stylesheet" href="../../public/css/pedido.css">
</head>
<body>
<div class="container">
    <h3>Dados do pedido:</h3>
    <p>Nome: <?= $dados->nome_user ?></p>
    <p>CPF: <?= $dados->cpf_user ?></p>
    <p>Usuário: <?= $dados->email_user ?></p>
    <p>Banco: <?= $_SESSION["banco"] ?></p>
    <p>Conta: <?= $_SESSION["conta"] ?></p>
    <p>Taxa ao mês: <?= $_SESSION["taxa"] ?>%</p>
    <p>Tempo em meses: <?= $_SESSION["tempo"] ?></p>
    <p>Capital aplicado: R$ <?= $capital_fmt ?></p>
    <p>Rendimento: R$ <?= $rendimento_fmt ?></p>
    <p>Total disponível após o pedido: R$ <?= $total_fmt ?></p>

    <form action="AddPedido.php" method="post">
        <input name="botao" type="submit" value="Registrar">
    </form>
</div>
</body>
</html>
