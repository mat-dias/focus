<?php
// Inicia ou retoma a sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*Função para validar login e nível de acesso */
function proteger($perfilNecessario = null)
{
    // 1. Verifica se o ID existe na sessão 
    if (!isset($_SESSION['id'])) {
        header("Location: ../login.html");
        exit;
    }

    // 2. Verifica o nível de acesso
    if ($perfilNecessario && $_SESSION['role'] !== $perfilNecessario) {

        header("Location: ../dashboard/dashboard.php");
        exit;
    }
}