<?php
session_start();//Exemplo para corrigir depois (nada aqui faz sentido, apenas usar de base)

require_once __DIR__ . "/classes/PostgreSQLClass.php";

/* ===============================
   VERIFICA LOGIN
=================================*/

if (!isset($_SESSION["login"])) {
    header("Location: index.php");
    exit;
}

$login = (object) $_SESSION["login"];

/* ===============================
   FLASH MESSAGE
=================================*/

$mensagem = $_SESSION["mensagem"] ?? "";
$tipo = $_SESSION["tipo"] ?? "";
unset($_SESSION["mensagem"], $_SESSION["tipo"]);

/* ===============================
   BUSCAR TAREFAS
=================================*/

$db = new PostgreSQLClass();

$sql = "
SELECT 
    t.id_tarefa,
    t.titulo_tarefa,
    t.descricao_tarefa,
    t.duracao_tarefa,
    t.dificuldade_tarefa,
    t.id_projeto,
    p.nome_projeto
FROM tarefa t
JOIN projeto p ON p.id_projeto = t.id_projeto
WHERE t.id_usuario = :id_usuario
ORDER BY t.id_tarefa
";

$tarefas = $db->search($sql, [
    ":id_usuario" => $login->id_usuario
]);
?>

<!doctype html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerenciar Tarefas</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/Gerenciar.css">
</head>

<body>

<header onclick="location.href='index.php'">
<div class="logo">💠 MinhaLogo</div>
</header>

<main>

<?php if ($mensagem): ?>
<div id="mensagem" class="<?= htmlspecialchars($tipo) ?>">
<?= htmlspecialchars($mensagem) ?>
</div>
<?php endif; ?>

<a href="tarefa/NovaTarefa.php" class="add-pedido-btn">
+ Adicionar nova tarefa
</a>

<?php if ($tarefas && count($tarefas) > 0): ?>

<table>

<thead>
<tr>
<th>ID</th>
<th>Título</th>
<th>Descrição</th>
<th>Duração</th>
<th>Dificuldade</th>
<th>ID Projeto</th>
<th>Projeto</th>
<th>Ações</th>
</tr>
</thead>

<tbody>

<?php foreach ($tarefas as $tarefa): ?>

<tr>

<td><?= htmlspecialchars($tarefa->id_tarefa) ?></td>
<td><?= htmlspecialchars($tarefa->titulo_tarefa) ?></td>
<td><?= htmlspecialchars($tarefa->descricao_tarefa) ?></td>
<td><?= htmlspecialchars($tarefa->duracao_tarefa) ?> min</td>
<td><?= htmlspecialchars($tarefa->dificuldade_tarefa) ?></td>
<td><?= htmlspecialchars($tarefa->id_projeto) ?></td>
<td><?= htmlspecialchars($tarefa->nome_projeto) ?></td>

<td>

<a href="tarefa/EditarTarefa.php?id_tarefa=<?= $tarefa->id_tarefa ?>">
<button type="button">Editar</button>
</a>

<form method="post"
action="tarefa/DeletarTarefa.php"
onsubmit="return confirm('Deseja realmente deletar esta tarefa?');"
style="display:inline;">

<input type="hidden"
name="id_tarefa"
value="<?= $tarefa->id_tarefa ?>">

<button type="submit">Excluir</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php else: ?>

<div style="text-align:center; margin-top:40px;">
<p>Você ainda não cadastrou nenhuma tarefa.</p>
</div>

<?php endif; ?>

</main>

<a href="projeto/CadastrarProjeto.php" class="add-pedido-btn">
+ Adicionar Novo Projeto
</a>

<script>
setTimeout(() => {
    const msg = document.getElementById('mensagem');
    if (msg) {
        msg.classList.add('fade-out');
        setTimeout(() => msg.remove(), 1000);
    }
}, 5000);
</script>

</body>
</html>
