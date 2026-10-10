<?php
// ==========================================================
// INICIA A SESSÃO E VERIFICA PERMISSÃO DE ADMINISTRADOR
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../login/login.php");
    exit();
}

require_once dirname(__DIR__) . "/conexao.php";

if (!isset($_GET["id"])) {
    header("Location: gerenciar_ambientes.php");
    exit();
}

$id = intval($_GET["id"]);
$erro = "";
$sucesso = false;

// Salvar alterações
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $tipo = trim($_POST["tipo"] ?? "");

    if (empty($nome) || empty($tipo)) {
        $erro = "Nome e tipo são obrigatórios.";
    } else {
        $sql = "UPDATE ambientes SET nome = ?, tipo = ? WHERE id_ambientes = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $nome, $tipo, $id);
            if ($stmt->execute()) {
                $sucesso = true;
            } else {
                $erro = "Erro ao atualizar o ambiente: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erro = "Erro ao preparar a atualização: " . $conn->error;
        }
    }
}

// Buscar dados atuais do ambiente
$sql = "SELECT * FROM ambientes WHERE id_ambientes = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$ambiente = $resultado->fetch_assoc();
$stmt->close();

if (!$ambiente) {
    die("Ambiente não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ambiente</title>
    <link rel="stylesheet" href="../css/editar_usuario.css">
</head>
<body>

<header class="menu">
    <div class="logo">
        <img src="../logo.png" alt="Logo">
    </div>
    <nav>
        <a href="">Home</a>
        <a href="gerenciar_ambientes.php">Gerenciar Ambientes</a>
    </nav>
</header>

<?php if ($sucesso): ?>
    <main class="mensagem">
        <div class="caixa-sucesso">
            <div class="icone-sucesso">✓</div>
            <h1>Dados alterados com sucesso!</h1>
            <p>As informações do ambiente foram atualizadas corretamente.</p>
            <a href="gerenciar_ambientes.php" class="voltar">Voltar para gerenciamento</a>
        </div>
    </main>
<?php else: ?>
    <section class="titulo">
        <h1>Editar Ambiente</h1>
    </section>
    <br>
    <p class="subtitulo">Altere os dados do ambiente.</p>

    <main class="container">
        <?php if (!empty($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario">
            <label for="nome">Nome do Ambiente</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($ambiente["nome"]); ?>" required>

            <label for="tipo">Tipo do Ambiente</label>
            <select name="tipo" id="tipo" required>
                <option value="DS" <?= ($ambiente["tipo"] == "DS") ? "selected" : ""; ?>>DS (Desenvolvimento de Sistemas)</option>
                <option value="ADM" <?= ($ambiente["tipo"] == "ADM") ? "selected" : ""; ?>>ADM (Administração)</option>
                <option value="AUT" <?= ($ambiente["tipo"] == "AUT") ? "selected" : ""; ?>>AUT (Automação)</option>
                <option value="Auditório" <?= ($ambiente["tipo"] == "Auditório") ? "selected" : ""; ?>>Auditório</option>
            </select>

            <div class="botoes">
                <button type="button" class="cancelar" onclick="window.location.href='gerenciar_ambientes.php';">Cancelar</button>
                <button type="submit" class="salvar">Salvar alterações</button>
            </div>
        </form>
    </main>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>