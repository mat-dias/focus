<?php
session_start();

if (isset($_POST["botao"])) {
    if ($_POST["botao"] === "Ver pedidos") {
        echo "<script>
                alert('Pedido cadastrado com sucesso!');
                window.location.href = '../Gerenciar.php';
              </script>";
        exit;
    } elseif ($_POST["botao"] === "Home") {
        echo "<script>
                alert('Voltando para a Home!');
                window.location.href = '../../public/index.php';
              </script>";
        exit;
    }
}
?>

<form method="post" action="">
    <input type="submit" name="botao" value="Ver pedidos">
    <input type="submit" name="botao" value="Home">
</form>
