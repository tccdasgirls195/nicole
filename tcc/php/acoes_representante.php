<?php
// ==========================================================
// 1. INICIA A SESSÃO
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// 2. VERIFICA SE O USUÁRIO ESTÁ LOGADO
// ==========================================================
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// ==========================================================
// 3. VERIFICA O TIPO DE USUÁRIO
// ==========================================================
$tipoPermitido = 'coordenador';

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== $tipoPermitido) {
    header("Location: login.php");
    exit();
}

// ==========================================================
// 4. LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// ==========================================================
// 5. CABEÇALHOS ANTI-CACHE
// ==========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================================
// 6. CONEXÃO COM O BANCO
// ==========================================================
require_once __DIR__ . "/conexao.php";

// =====================================================
// VERIFICAR DADOS
// =====================================================

if (
    !isset($_GET["acao"]) ||
    !isset($_GET["id"])
) {

    header("Location: gerenciar_representantes.php");
    exit;

}


$acao = $_GET["acao"];
$id = intval($_GET["id"]);


// =====================================================
// BLOQUEAR REPRESENTANTE
// =====================================================

if ($acao == "bloquear") {

    $sql = "
        UPDATE representante
        SET status = 'Bloqueado'
        WHERE id_representante = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro ao preparar a ação: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Erro ao bloquear o representante: " . $stmt->error);
    }

    $stmt->close();

    header("Location: gerenciar_representantes.php?mensagem=bloqueado");
    exit;
}


// =====================================================
// ATIVAR REPRESENTANTE
// =====================================================

elseif ($acao == "ativar") {

    $sql = "
        UPDATE representante
        SET status = 'Ativo'
        WHERE id_representante = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro ao preparar a ação: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die("Erro ao ativar o representante: " . $stmt->error);
    }

    $stmt->close();

    header("Location: gerenciar_representantes.php?mensagem=ativado");
    exit;
}


// =====================================================
// AÇÃO INVÁLIDA
// =====================================================

else {
    die("Ação inválida.");
}

?>
