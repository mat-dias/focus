<?php
include __DIR__ . '/../../database/Conexao.php';

class Sql {

    private $conn;

    public function __construct() {
        $this->conn = conectar(); // usa a função conectar() do Conexao.php
    }

    // Para INSERT, UPDATE, DELETE
    public function query($sql, $params = array()) {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            die("Erro na query: " . $this->conn->error);
        }

        if ($params) {
            // Define todos os parâmetros como string
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Para SELECT
    public function select($sql, $params = array(), $one = false) {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            die("Erro na query: " . $this->conn->error);
        }

        if ($params) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($one) {
            $data = $result->fetch_object();
        } else {
            $data = [];
            while ($row = $result->fetch_object()) {
                $data[] = $row;
            }
        }

        $stmt->close();
        return $data;
    }
}
