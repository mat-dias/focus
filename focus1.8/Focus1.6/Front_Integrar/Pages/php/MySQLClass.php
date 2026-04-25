<?php
require_once __DIR__ . '/conexao.php';
class MySQLClass
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = getConexao();
            if ($this->db->connect_error) {
                throw new Exception($this->db->connect_error);
            }
            $this->db->query("SET SESSION wait_timeout=60");
        } catch (Exception $e) {
            throw new Exception("Erro de rede com o banco: " . $e->getMessage());
        }
    }
    public function getConnection()
    {
        return $this->db;
    }
    public function search($sql)
    {
        $result = $this->db->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }
    public function exec($sql)
    {
        return $this->db->query($sql);
    }

    public function searchSafe($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function execSafe($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
}
