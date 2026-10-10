<?php
// ==========================================================
// 1. INICIA A SESSÃO E VERIFICA PERMISSÃO DE ADMINISTRADOR
// ==========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

// Cabeçalhos anti-cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/conexao.php";

// =====================================================
// PESQUISA
// =====================================================
$pesquisa = "";
if (isset($_GET["pesquisa"])) {
    $pesquisa = trim($_GET["pesquisa"]);
}
$busca = "%" . $pesquisa . "%";

// =====================================================
// BUSCAR AMBIENTES
// =====================================================
$sql = "SELECT id_ambientes, nome, tipo, status 
        FROM ambientes 
        WHERE nome LIKE ? OR tipo LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $busca, $busca);
$stmt->execute();
$resultAmbientes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Ambientes</title>
    <link rel="stylesheet" href="../css/gerenciar_usuarios.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
    /* Estilos copiados do gerenciamento de usuários para manter o padrão visual */
    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45);
        z-index: 9999; align-items: center; justify-content: center;
    }
    .modal-overlay.ativo { display: flex; }
    .modal-caixa {
        width: 420px; max-width: calc(100% - 40px); background: #fff;
        border-radius: 14px; padding: 28px; text-align: center;
        box-shadow: 0 8px 30px rgba(0,0,0,.25);
    }
    .modal-icone {
        width: 58px; height: 58px; margin: 0 auto 15px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #fff3cd; color: #856404; font-size: 28px; font-weight: bold;
    }
    .popup-sucesso {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        z-index: 10000; min-width: 320px; padding: 22px 28px; background: #d4edda;
        color: #155724; border: 1px solid #c3e6cb; border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,.2); text-align: center; font-size: 17px; font-weight: bold;
    }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../logo.png" alt="Logo">
    </div>
    <nav>
        <a href="">Home</a>
        <a href="agendamento.php">Agendamento</a>
    </nav>
</header>

<section class="titulo">
    <h1>Gerenciamento de Ambientes</h1>
</section>
<br>
<p class="subtitulo" align="center">Gerencie os ambientes e laboratórios cadastrados no sistema.</p>

<div class="container" style="width:calc(100% - 106px); max-width:1500px; margin:20px auto;">
    
    <div class="novo-usuario">
        <a href="cadastrar_ambiente.php">+ Cadastrar ambiente</a>
    </div>

    <form method="GET" class="pesquisa">
        <input type="text" name="pesquisa" placeholder="Pesquisar por nome ou tipo..." value="<?= htmlspecialchars($pesquisa); ?>">
        <button type="submit">Pesquisar</button>
    </form>

    <div class="tabela-container">
        <table>
            <thead>
                <tr>
                    <th>Nome do Ambiente</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ambiente = $resultAmbientes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($ambiente["nome"]); ?></td>
                        <td><?= htmlspecialchars($ambiente["tipo"]); ?></td>
                        <td>
                            <?php if ($ambiente["status"] == "Ativo" || empty($ambiente["status"])): ?>
                                <span class="status ativo">Ativo</span>
                            <?php else: ?>
                                <span class="status bloqueado">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                        <td class="acoes">
                            <a href="editar_ambiente.php?id=<?= $ambiente["id_ambientes"]; ?>" class="editar">Editar</a>
                            
                            <?php if ($ambiente["status"] == "Bloqueado"): ?>
                                <a href="acoes_ambiente.php?acao=ativar&id=<?= $ambiente["id_ambientes"]; ?>" class="ativar acao-confirmar">Ativar</a>
                            <?php else: ?>
                                <a href="acoes_ambiente.php?acao=bloquear&id=<?= $ambiente["id_ambientes"]; ?>" class="bloquear acao-confirmar">Bloquear</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="modalConfirmacao" class="modal-overlay">
    <div class="modal-caixa">
        <div class="modal-icone">!</div>
        <h2>Confirmar ação</h2>
        <p id="textoConfirmacao"></p>
        <div class="modal-botoes" style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" id="btnCancelar" class="btn-cancelar" style="padding:10px 20px; border-radius:20px; border:none; background:#e9e9e9; cursor:pointer;">Cancelar</button>
            <button type="button" id="btnConfirmar" class="btn-confirmar" style="padding:10px 20px; border-radius:20px; border:none; background:#e52b2b; color:#fff; cursor:pointer;">Confirmar</button>
        </div>
    </div>
</div>

<?php if (isset($_GET["mensagem"])): ?>
    <div id="popupSucesso" class="popup-sucesso">
        <span class="check" style="font-size:30px; display:block;">✓</span>
        <?= $_GET["mensagem"] === "bloqueado" ? "Ambiente bloqueado com sucesso!" : ($_GET["mensagem"] === "ativado" ? "Ambiente ativado com sucesso!" : "Operação realizada com sucesso!"); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalConfirmacao");
    const texto = document.getElementById("textoConfirmacao");
    const btnConfirmar = document.getElementById("btnConfirmar");
    const btnCancelar = document.getElementById("btnCancelar");
    let linkConfirmado = null;

    document.querySelectorAll(".acao-confirmar").forEach(function (link) {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            linkConfirmado = link;
            const ativar = link.classList.contains("ativar");
            texto.textContent = ativar ? "Tem certeza que deseja ativar este ambiente?" : "Tem certeza que deseja bloquear este ambiente?";
            btnConfirmar.textContent = ativar ? "Ativar" : "Bloquear";
            modal.classList.add("ativo");
        });
    });

    btnCancelar.addEventListener("click", function () { modal.classList.remove("ativo"); });
    btnConfirmar.addEventListener("click", function () { if (linkConfirmado) window.location.href = linkConfirmado.href; });
});
</script>
</body>
</html>
<?php $conn->close(); ?>