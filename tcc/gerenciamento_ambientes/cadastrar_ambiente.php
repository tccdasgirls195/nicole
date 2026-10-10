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

$cadastroSucesso = false;
$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $tipo = trim($_POST["tipo"] ?? "");

    if (empty($nome) || empty($tipo)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $sql = "INSERT INTO ambientes (nome, tipo, status) VALUES (?, ?, 'Ativo')";
        $stmt = mysqli_prepare($conexao, $sql);

        if ($stmt) {
            $stmt->bind_param("ss", $nome, $tipo);
            if ($stmt->execute()) {
                $cadastroSucesso = true;
            } else {
                $erro = "Erro ao cadastrar ambiente: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erro = "Erro ao preparar o cadastro: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Ambiente</title>
    <link rel="stylesheet" href="../css/cadastrar_usuario.css">
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

<?php if ($cadastroSucesso): ?>
    <main class="mensagem">
        <div class="caixa-sucesso">
            <div class="icone-sucesso">✓</div>
            <h1>Ambiente cadastrado com sucesso!</h1>
            <p>O novo ambiente foi adicionado ao sistema.</p>
            <a href="gerenciar_ambientes.php" class="voltar">Voltar para gerenciamento</a>
        </div>
    </main>
<?php else: ?>
    <section class="titulo">
        <h1>Cadastrar Ambiente</h1>
    </section>
    <br>
    <p class="subtitulo">Preencha os dados para cadastrar um novo ambiente.</p>
    
    <main class="container">
        <?php if (!empty($erro)): ?>
            <div class="erro"><?= htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario">
            <label for="nome">Nome do Ambiente</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Laboratório de Informática 1" required>

            <label for="tipo">Tipo do Ambiente</label>
            <select id="tipo" name="tipo" required>
                <option value="">Selecione o tipo</option>
                <option value="DS">DS (Desenvolvimento de Sistemas)</option>
                <option value="ADM">ADM (Administração)</option>
                <option value="AUT">AUT (Automação)</option>
                <option value="Auditório">Auditório</option>
            </select>

            <div class="botoes">
                <button type="button" class="cancelar" onclick="window.location.href='gerenciar_ambientes.php';">Cancelar</button>
                <button type="submit" class="cadastrar">Cadastrar ambiente</button>
            </div>
        </form>
    </main>
<?php endif; ?>

</body>
</html>
<?php mysqli_close($conexao); ?>