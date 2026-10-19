<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . "/conexao.php";

if (!isset($_GET["acao"]) || !isset($_GET["id"])) {
    header("Location: gerenciar_ambientes.php");
    exit();
}

$acao = $_GET["acao"];
$id = intval($_GET["id"]);

if ($acao == "bloquear") {
    $sql = "UPDATE ambientes SET status = 'Bloqueado' WHERE id_ambientes = ?";
} elseif ($acao == "ativar") {
    $sql = "UPDATE ambientes SET status = 'Ativo' WHERE id_ambientes = ?";
} else {
    die("Ação inválida.");
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: gerenciar_ambientes.php?mensagem=" . ($acao == "bloquear" ? "bloqueado" : "ativado"));
exit();
?>