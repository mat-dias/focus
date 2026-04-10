<?php
//Conexao MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db = "focus";

function getConexao()
{
    global $host, $user, $pass, $db;

    try {
        $conn = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user,
            $pass
        );

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $conn;

    } catch (PDOException $erro) {
        die("Erro de conexão: " . $erro->getMessage());
    }
}