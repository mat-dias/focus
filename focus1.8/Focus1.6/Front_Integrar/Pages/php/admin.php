<?php
// Inicia ou retoma a sessão do usuário
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Bloqueia acesso se usuário não for administrador
if ($_SESSION['perfil_usuario'] !== 'admin') {// Alterar nome de perfil_usuario e acrescentar no banco um campo ENUM com Adm e User
    header("Location: dashboard.php");
    exit;
}
