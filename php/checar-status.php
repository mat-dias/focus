<?php
session_start();
header('Content-Type: application/json'); //corrigido

$logado = isset($_SESSION['user_id']);

echo json_encode([
    "logado" => $logado,
    "nome"   => $logado ? ($_SESSION['user_nome'] ?? 'Utilizador') : null,
    'role'   => $_SESSION['role'] ?? 'user'
]);
exit;
