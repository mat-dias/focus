<?php
require_once "MySQLClass.php";
try {
    $db = getConexao();
    echo "Conectado com sucesso ao servidor DBaaS!";
} catch (Exception $e) {
    echo "Erro de conexão: " . $e->getMessage();
}