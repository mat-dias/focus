<?php
require_once __DIR__ . "/MySQLClass.php"; //apagar depois

$db = new MySQLClass();
$novaSenha = "123456"; 
$emailUsuario = "deoliveiramoreiramatheus67@gmail.com";

// O segredo está aqui: esta função gera o hash que o PHP entende
$hashProtegido = password_hash($novaSenha, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = :hash WHERE email = :email";
$resultado = $db->exec($sql, [
    ":hash"  => $hashProtegido,
    ":email" => $emailUsuario
]);

if ($resultado > 0) {
    echo "Sucesso! O hash no banco foi atualizado.";
} else {
    echo "Erro: Usuário não encontrado ou a senha já era essa.";
}