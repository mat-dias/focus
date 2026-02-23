<?php
session_start();

if (isset($_POST["nome_banco"], $_POST["conta_banco"], $_POST["capital_banco"], $_POST["taxa_banco"], $_POST["tempo_banco"])) {
    $_SESSION["banco"] = $_POST["nome_banco"];
    $_SESSION["conta"] = $_POST["conta_banco"];
    $_SESSION["capital"] = $_POST["capital_banco"];
    $_SESSION["taxa"] = $_POST["taxa_banco"];
    $_SESSION["tempo"] = $_POST["tempo_banco"];

    $capital = $_POST["capital_banco"];
    $taxa = ($_POST["taxa_banco"] / 100) + 1;
    $tempo = $_POST["tempo_banco"];
    $_SESSION["rendimento"] = $capital * pow($taxa, $tempo);

    header("Location: Pedido.php");
    exit;
}
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Banco</title>
    <link rel="stylesheet" href="../../public/css/indexbanco.css">
</head>
<body>
<form name="form1" action="" method="post">
    <label>Banco:</label>
    <select name="nome_banco" id="nome_banco" required>
        <option value="">Selecione seu banco</option>
        <option value="Banco do Brasil">Banco do Brasil</option>
        <option value="Caixa">Caixa</option>
        <option value="Itau">Itaú</option>
        <option value="Santander">Santander</option>
        <option value="Bradesco">Bradesco</option>
    </select>

    <label>Conta:</label>
    <input type="text" name="conta_banco" required>

    <label>Capital:</label>
    <input type="number" name="capital_banco" step="0.01" required>

    <label>Taxa (%):</label>
    <select name="taxa_banco" required>
        <option value="">Selecione a taxa</option>
        <option value="0.50">0,50%</option>
        <option value="0.75">0,75%</option>
        <option value="1.00">1,00%</option>
        <option value="1.50">1,50%</option>
        <option value="1.75">1,75%</option>
        <option value="2.00">2,00%</option>
        <option value="2.50">2,50%</option>
        <option value="3.00">3,00%</option>
        <option value="3.50">3,50%</option>
        <option value="4.00">4,00%</option>
        <option value="4.50">4,50%</option>
        <option value="5.00">5,00%</option>
    </select>

    <label>Tempo (meses):</label>
    <select name="tempo_banco" required>
        <option value="">Selecione o tempo</option>
        <option value="1">1 mês</option>
        <option value="2">2 meses</option>
        <option value="3">3 meses</option>
        <option value="4">4 meses</option>
        <option value="5">5 meses</option>
        <option value="6">6 meses</option>
        <option value="12">1 ano</option>
        <option value="24">2 anos</option>
        <option value="36">3 anos</option>
        <option value="48">4 anos</option>
        <option value="60">5 anos</option>
    </select>

    <input type="submit" value="Enviar">
</form>
</body>
</html>
