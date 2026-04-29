<?php
session_start();//corrigido

if (!isset($_SESSION['user_id'])) {

    header('Location: ../../index.html'); 
    exit();
}

// O ID da conta para questões de segurança
$idUsuario = $_SESSION['user_id']; 

// O ID do perfil para registrar XP, Streaks e Chamados
$idPerfil = $_SESSION['profile_id'] ?? null; 
?>