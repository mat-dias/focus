<?php
include __DIR__ . '/Config.php';

function conectar() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }

    return $conn;
}
