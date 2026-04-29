<?php 
session_start();//corrigido
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logado' => true,
        'nome'   => $_SESSION['user_nome'] ?? 'Utilizador',
        'foto'   => $_SESSION['user_foto'] ?? null,
        'profile_id' => $_SESSION['profile_id'] ?? null,
        'streak' => (int)($perfil[0]['streak'] ?? 0),
        'xp' => (int)($perfil[0]['xp'] ?? 0)
    ]);
} else {
    echo json_encode(['logado' => false]);
}
exit;