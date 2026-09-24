```php
<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");

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
        die(
            "Erro ao preparar a ação: " .
            $conn->error
        );
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die(
            "Erro ao bloquear o representante: " .
            $stmt->error
        );
    }

    $stmt->close();

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
        die(
            "Erro ao preparar a ação: " .
            $conn->error
        );
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        die(
            "Erro ao ativar o representante: " .
            $stmt->error
        );
    }

    $stmt->close();

}

// =====================================================
// AÇÃO INVÁLIDA
// =====================================================

else {

    die("Ação inválida.");

}

// =====================================================
// VOLTAR PARA GERENCIAR REPRESENTANTES
// =====================================================

header("Location: gerenciar_representantes.php");
exit;

?>
```
