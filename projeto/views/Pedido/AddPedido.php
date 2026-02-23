<?php

session_start();
include __DIR__ . '/../../app/classes/Sql.php';
include __DIR__ . '/../../app/classes/StrBuilder.php';

// Todos objetos do formulário devem estar configurados.   
$nome = $_SESSION['nome'];
$cpf = $_SESSION['cpf'];
$banco = $_SESSION['banco'];
$conta = $_SESSION['conta'];
$taxa = doubleval($_SESSION['taxa']);
$meses = intval($_SESSION['tempo']);
$capital = number_format(doubleval($_SESSION['capital']), 2, '.', '');
$rendimento = number_format(doubleval($_SESSION['rendimento']), 2, '.', '');
$total = number_format(doubleval($_SESSION['total']), 2, '.', '');
$id = $_SESSION['id'];
$cadastro = new CadastroPedido($nome, $cpf, $banco, $conta, $taxa, $meses, $capital, $rendimento, $total, $id);

// Instancia o Objeto, já com valores adquiridos pelo formulário

class CadastroPedido
{
    public $nome, $cpf, $banco, $conta, $taxa, $meses, $capital, $rendimento, $total, $id;

    public function __construct($nome, $cpf, $banco, $conta, $taxa, $meses, $capital, $rendimento, $total, $id)
    {
        $this->nome = $nome;
        $this->cpf = $cpf;
        $this->banco = $banco;
        $this->conta = $conta;
        $this->taxa = $taxa;
        $this->meses = $meses;
        $this->capital = $capital;
        $this->rendimento = $rendimento;
        $this->total = $total;
        $this->id = $id;
    }

    public function incluirPedido()
    {
        try {
            $sql = new StrBuilder();
            $sql->appendLine("insert into tb_pedido");
            $sql->appendLine("(nome_pedido, cpf_pedido, banco_pedido,");
            $sql->appendLine("conta_pedido, taxa_pedido, meses_pedido,");
            $sql->appendLine("capital_pedido, rendimento_pedido, total_pedido, id_user)");
            $sql->appendLine("values (?,?,?,?,?,?,?,?,?, ?)");
            $comando = new Sql();

            $row = $comando->query($sql->getStr(), array(
                $this->nome, $this->cpf, $this->banco,
                $this->conta, $this->taxa, $this->meses,
                $this->capital, $this->rendimento, $this->total,
                $this->id));

            if ($row > 0) {
                echo "<script> 
                        alert('Pedido registrado com sucesso!')
                        window.location = '../Gerenciar.php'; 
                      </script>";
            } else {
                echo "Erro ao cadastrar o pedido!";
            }
        } catch (PDOException $erro) {
            echo "Erro" . $erro->getMessage();
        }
    }
}

$cadastro->incluirPedido();

?>
