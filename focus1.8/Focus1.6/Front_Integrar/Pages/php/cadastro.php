<?php
header('Content-Type: application/json; charset=utf-8');

// caminho da classe MySQL
require_once __DIR__ . "/MySQLClass.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["sucesso" => false, "mensagem" => "Método não permitido."]);
    exit();
}

/* DADOS */
$nome = trim($_POST["nome"] ?? "");
$sobrenome = trim($_POST["sobrenome"] ?? "");
$dataNasc = trim($_POST["data_nascimento"] ?? "");
$genero = trim($_POST["genero"] ?? "");
$telefone = preg_replace('/\D/', '', $_POST["telefone"] ?? "");
$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

/* VALIDAÇÕES BÁSICAS */
$erros = [];
if ($nome === "" || $sobrenome === "")
    $erros[] = "Nome e sobrenome são obrigatórios.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $erros[] = "E-mail inválido.";
if (strlen($senha) < 6)
    $erros[] = "A senha deve ter ao menos 6 caracteres.";
if ($senha !== $confirmar)
    $erros[] = "As senhas não coincidem.";

if (!empty($erros)) {
    http_response_code(422);
    echo json_encode(["sucesso" => false, "mensagem" => implode(" ", $erros)]);
    exit();
}

/* LÓGICA DE UPLOAD (Avatar) */
$avatar = null;
if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
    $pasta = __DIR__ . "/uploads/fotos/";
    if (!is_dir($pasta))
        mkdir($pasta, 0755, true);

    $avatar = "user_" . uniqid() . "." . $ext;
    move_uploaded_file($_FILES["foto"]["tmp_name"], $pasta . $avatar);
}

try {
    $db = new MySQLClass();

    $sql = "INSERT INTO users 
            (name, email, password, role, phone, gender, birth_date, avatar, created_at, updated_at)
            VALUES 
            (:name, :email, :password, :role, :phone, :gender, :birth_date, :avatar, NOW(), NOW())";

    $db->exec($sql, [
        ":name" => $nome . " " . $sobrenome,
        ":email" => $email,
        ":password" => password_hash($senha, PASSWORD_DEFAULT),
        ":role" => 'user', // Valor para a coluna 'role'
        ":phone" => $telefone,
        ":gender" => $genero,
        ":birth_date" => $dataNasc,
        ":avatar" => $avatar
    ]);

    echo json_encode(["sucesso" => true, "mensagem" => "Cadastro realizado com sucesso!"]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(["sucesso" => false, "mensagem" => "Este e-mail já está cadastrado."]);
    } else {
        error_log($e->getMessage());
        echo json_encode(["sucesso" => false, "mensagem" => "Erro ao salvar no banco de dados."]);
    }
}