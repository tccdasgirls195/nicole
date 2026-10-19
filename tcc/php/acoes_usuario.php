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
$tipoPermitido = 'administrador';

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
    !isset($_GET["tipo"]) ||
    !isset($_GET["id"])
) {

    header("Location: gerenciar_usuarios.php");

    exit;

}


$acao = $_GET["acao"];

$tipo = $_GET["tipo"];

$id = intval($_GET["id"]);


// =====================================================
// TABELAS
// =====================================================

$tabelas = [

    "administrador" => [
        "tabela" => "administrador",
        "id" => "id_administrador"
    ],

    "coordenador" => [
        "tabela" => "coordenador",
        "id" => "id_coordenador"
    ],

    "professor" => [
        "tabela" => "professor",
        "id" => "id_professor"
    ],

    "representante" => [
        "tabela" => "representante",
        "id" => "id_representante"
    ],

    "gestao" => [
        "tabela" => "gestao",
        "id" => "id_gestao"
    ]

];


// =====================================================
// VERIFICAR TIPO
// =====================================================

if (!isset($tabelas[$tipo])) {

    die("Tipo de usuário inválido.");

}


$tabela = $tabelas[$tipo]["tabela"];

$campoId = $tabelas[$tipo]["id"];


// =====================================================
// BLOQUEAR
// =====================================================

if ($acao == "bloquear") {


    $sql = "UPDATE $tabela

            SET status = 'Bloqueado'

            WHERE $campoId = ?";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Erro ao preparar a ação: "
            . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $id
    );


    if (!$stmt->execute()) {

        die(
            "Erro ao bloquear o usuário: "
            . $stmt->error
        );

    }

}


// =====================================================
// ATIVAR
// =====================================================

elseif ($acao == "ativar") {


    $sql = "UPDATE $tabela

            SET status = 'Ativo'

            WHERE $campoId = ?";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Erro ao preparar a ação: "
            . $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $id
    );


    if (!$stmt->execute()) {

        die(
            "Erro ao ativar o usuário: "
            . $stmt->error
        );

    }

}


// =====================================================
// AÇÃO INVÁLIDA
// =====================================================

else {

    die("Ação inválida.");

}


// =====================================================
// VOLTAR PARA GERENCIAMENTO COM MENSAGEM DE SUCESSO
// =====================================================

if ($acao == "bloquear") {
    header("Location: gerenciar_usuarios.php?mensagem=bloqueado");
} else {
    header("Location: gerenciar_usuarios.php?mensagem=ativado");
}

exit;

?>