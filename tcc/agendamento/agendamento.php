<?php
// ==========================================================
// 1. INICIA A SESSÃO
// ==========================================================
session_start();

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
$tiposPermitidos = [
    'professor',
    'administrador',
    'coordenador',
    'gestao'
];

if (!in_array($_SESSION['usuario_tipo'], $tiposPermitidos)) {
    header("Location: login.php");
    exit();
}

// ==========================================================
// 4. LOGOUT
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../login/login.php");
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
require_once dirname(__DIR__) . "/conexao.php";

$mensagem = "";
if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1') {
    $mensagem = "Agendamento enviado com sucesso!";
}

$erro = "";
$ocupados = [];
$ambientesBloqueados = [];

$usuario_id = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];

// ==========================================================
// 8. BUSCA O NOME DO USUÁRIO LOGADO
// ==========================================================
$nome_usuario = "";

$tabelasUsuarios = [
    'professor' => ['tabela' => 'professor', 'id' => 'id_professor'],
    'coordenador' => ['tabela' => 'coordenador', 'id' => 'id_coordenador'],
    'administrador' => ['tabela' => 'administrador', 'id' => 'id_administrador'],
    'gestao' => ['tabela' => 'gestao', 'id' => 'id_gestao']
];

if (isset($tabelasUsuarios[$usuario_tipo])) {
    $tabela = $tabelasUsuarios[$usuario_tipo]['tabela'];
    $colunaId = $tabelasUsuarios[$usuario_tipo]['id'];

    $sqlUsuario = "SELECT nome FROM $tabela WHERE $colunaId = ?";
    $stmtUsuario = mysqli_prepare($conexao, $sqlUsuario);

    if ($stmtUsuario) {
        mysqli_stmt_bind_param($stmtUsuario, "i", $usuario_id);
        mysqli_stmt_execute($stmtUsuario);
        $resultadoUsuario = mysqli_stmt_get_result($stmtUsuario);

        if ($linhaUsuario = mysqli_fetch_assoc($resultadoUsuario)) {
            $nome_usuario = $linhaUsuario['nome'];
        }
        mysqli_stmt_close($stmtUsuario);
    }
}

// ==========================================================
// 9. BUSCA O ID DA GESTÃO
// ==========================================================
$id_gestao = null;
$sqlGestao = "SELECT id_gestao FROM gestao WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1";
$emailGestao = "gestao@email.com";
$stmtGestao = mysqli_prepare($conexao, $sqlGestao);

if ($stmtGestao) {
    mysqli_stmt_bind_param($stmtGestao, "s", $emailGestao);
    mysqli_stmt_execute($stmtGestao);
    $resultadoGestao = mysqli_stmt_get_result($stmtGestao);

    if ($linhaGestao = mysqli_fetch_assoc($resultadoGestao)) {
        $id_gestao = $linhaGestao['id_gestao'];
    }
    mysqli_stmt_close($stmtGestao);
}

// ==========================================================
// 10. BUSCA AMBIENTES BLOQUEADOS NO BANCO
// ==========================================================
$resBloq = mysqli_query($conexao, "SELECT id_ambientes FROM ambientes WHERE status = 'Bloqueado'");
if ($resBloq) {
    while ($b = mysqli_fetch_assoc($resBloq)) {
        $ambientesBloqueados[] = (int)$b['id_ambientes'];
    }
}

// ==========================================================
// 11. PROCESSA O FORMULÁRIO DE AGENDAMENTO
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_agendamento'])) {

    $id_ambientes = isset($_POST['id_ambientes']) ? intval($_POST['id_ambientes']) : 0;
    $data_agendamento = isset($_POST['data_agendamento']) ? trim($_POST['data_agendamento']) : "";
    $horario = isset($_POST['horario']) ? trim($_POST['horario']) : "";
    $descr = isset($_POST['descr']) ? trim($_POST['descr']) : "";

    if ($id_ambientes <= 0) {
        $erro = "Selecione um laboratório.";
    } elseif (in_array($id_ambientes, $ambientesBloqueados)) {
        $erro = "Este laboratório está bloqueado e não pode ser agendado.";
    } elseif (empty($data_agendamento)) {
        $erro = "Selecione uma data.";
    } elseif (empty($horario)) {
        $erro = "Selecione um horário.";
    } elseif (empty($descr)) {
        $erro = "Digite uma descrição para o agendamento.";
    } elseif ($id_gestao === null) {
        $erro = "Não foi possível localizar a Gestão no sistema.";
    } elseif (empty($nome_usuario)) {
        $erro = "Não foi possível identificar o usuário logado.";
    } else {

        $sqlVerifica = "SELECT id_agendamentos FROM agendamentos WHERE id_ambientes = ? AND data_agendamento = ? AND horario = ? LIMIT 1";
        $stmtVerifica = mysqli_prepare($conexao, $sqlVerifica);

        if ($stmtVerifica) {
            mysqli_stmt_bind_param($stmtVerifica, "iss", $id_ambientes, $data_agendamento, $horario);
            mysqli_stmt_execute($stmtVerifica);
            $resultadoVerifica = mysqli_stmt_get_result($stmtVerifica);

            if (mysqli_num_rows($resultadoVerifica) > 0) {
                $erro = "Este laboratório já está ocupado nessa data e horário.";
            }
            mysqli_stmt_close($stmtVerifica);
        } else {
            $erro = "Erro ao verificar disponibilidade.";
        }

        if (empty($erro)) {
            $id_professor = ($usuario_tipo === 'professor') ? $usuario_id : null;

            $sqlInsert = "INSERT INTO agendamentos (nome_prof, descr, data_agendamento, id_gestao, id_professor, id_ambientes, horario, solicitante_id, solicitante_tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = mysqli_prepare($conexao, $sqlInsert);

            if ($stmtInsert) {
                mysqli_stmt_bind_param($stmtInsert, "sssiiisis", $nome_usuario, $descr, $data_agendamento, $id_gestao, $id_professor, $id_ambientes, $horario, $usuario_id, $usuario_tipo);

                if (mysqli_stmt_execute($stmtInsert)) {
                    header("Location: agendamento.php?sucesso=1");
                    exit();
                } else {
                    $erro = "Erro ao salvar o agendamento: " . mysqli_stmt_error($stmtInsert);
                }
                mysqli_stmt_close($stmtInsert);
            } else {
                $erro = "Erro ao preparar o agendamento: " . mysqli_error($conexao);
            }
        }
    }
}

// ==========================================================
// 12. BUSCA LABORATÓRIOS OCUPADOS POR DATA/HORÁRIO
// ==========================================================
if (isset($_GET["data"]) && isset($_GET["horario"]) && !empty($_GET["data"]) && !empty($_GET["horario"])) {
    $data = $_GET["data"];
    $horarioSelecionado = $_GET["horario"];

    $sqlOcupados = "SELECT id_ambientes FROM agendamentos WHERE data_agendamento = ? AND horario = ?";
    $stmtOcupados = mysqli_prepare($conexao, $sqlOcupados);

    if ($stmtOcupados) {
        mysqli_stmt_bind_param($stmtOcupados, "ss", $data, $horarioSelecionado);
        mysqli_stmt_execute($stmtOcupados);
        $resultadoOcupados = mysqli_stmt_get_result($stmtOcupados);
        while ($linha = mysqli_fetch_assoc($resultadoOcupados)) {
            $ocupados[] = (int)$linha["id_ambientes"];
        }
        mysqli_stmt_close($stmtOcupados);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agendamento</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/agendamento.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<header>
    <div class="logo">
        <img src="../logo.png" alt="Logo">
    </div>

    <nav>
        <a href="">Home</a>
        <a href="#" class="has-submenu">Cursos</a>
        <li>
            <a href="#" class="has-submenu">A Etec</a>
             <ul class="submenu">
                <li><a href="eventos_login.php">Eventos</a></li>
            </ul>
        </li>
        <a href="#" class="has-submenu">Equipe Etec</a>
        
        <li>
            <a href="agendamento.php" class="has-submenu">Agendamento</a>
             <ul class="submenu">
                <li><a href="meus-agendamentos.php">Meus agendamentos</a></li>
            </ul>
        </li>

        <a href="#" class="has-submenu">Notícias</a>
        <a href="">Empregos & Estágios</a>
        <a href="">Parceiros</a>
        <a href="">TCC</a>
    </nav>
</header>

<section class="titulo">
    <h1>Agendamento</h1>
</section>

<a href="../login/login.php" style="color:#ff4d4d; text-decoration:none; font:inherit; display:inline-block;">
    <i class="fa-solid fa-right-from-bracket"></i> Sair
</a>

<div class="container">

    <div class="filtros">
        <div class="campo">
            <label for="data">Data:</label>
            <input type="date" id="data">
        </div>

        <div class="campo">
            <label for="horario">Horário: </label>
            <div class="campo-horario">
                <select id="horario" name="horario">
                    <option value="" selected disabled></option>
                    <option>7h30 - 8h20</option>
                    <option>8h20 - 9h10</option>
                    <option>9h10 - 10h</option>
                    <option>10h20 - 11h10</option>
                    <option>11h10 - 12h</option>
                    <option>13h - 13h50</option>
                    <option>13h50 - 14h40</option>
                    <option>14h40 - 15h30</option>
                    <option>15h30 - 16h20</option>
                    <option>16h20 - 17h10</option>
                    <option>18h - 18h50</option>
                    <option>18h50 - 19h40</option>
                    <option>19h40 - 20h</option>
                    <option>20h - 20h50</option>
                    <option>20h50 - 21h40</option>
                    <option>21h40 - 22h30</option>
                </select>
                <i class="fa-regular fa-clock"></i>
            </div>
        </div>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div style="background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin:15px 0;">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin:15px 0;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="conteudo">

        <!-- =================================================
             MAPA DE LABORATÓRIOS (EXIBINDO LAB 1, LAB 2...)
        ================================================== -->
        <div class="area-laboratorios">

            <!-- 1. LABORATÓRIOS DE DS -->
            <div class="titulo-laboratorios">
                <h2>Laboratórios de DS</h2>
            </div>
            <div class="mapa">
                <?php
                $sqlDS = "SELECT id_ambientes, nome, status FROM ambientes WHERE tipo = 'DS' ORDER BY id_ambientes";
                $resDS = mysqli_query($conexao, $sqlDS);
                $contador = 1;
                while ($amb = mysqli_fetch_assoc($resDS)):
                    $idAmb = (int)$amb['id_ambientes'];
                    $bloqueado = in_array($idAmb, $ambientesBloqueados);
                    $ocup = in_array($idAmb, $ocupados);
                    $classe = ($bloqueado || $ocup) ? 'ocupado' : '';
                    $onclick = $bloqueado ? "mostrarAlertaBloqueio()" : ($ocup ? "" : "selecionarLab($idAmb, 'LAB $contador')");
                ?>
                    <div class="lab <?= $classe ?>" onclick="<?= $onclick ?>">
                        LAB <?= $contador ?>
                    </div>
                <?php 
                    $contador++;
                endwhile; 
                ?>
            </div>

            <br><br>

            <!-- 2. LABORATÓRIOS DE ADM / RH -->
            <div class="titulo-laboratorios">
                <h2>Laboratórios de ADM / RH</h2>
            </div>
            <div class="mapa">
                <?php
                $sqlAdmRh = "SELECT id_ambientes, nome, status FROM ambientes WHERE tipo IN ('ADM', 'RH') ORDER BY id_ambientes";
                $resAdmRh = mysqli_query($conexao, $sqlAdmRh);
                $contador = 1;
                while ($amb = mysqli_fetch_assoc($resAdmRh)):
                    $idAmb = (int)$amb['id_ambientes'];
                    $bloqueado = in_array($idAmb, $ambientesBloqueados);
                    $ocup = in_array($idAmb, $ocupados);
                    $classe = ($bloqueado || $ocup) ? 'ocupado' : '';
                    $onclick = $bloqueado ? "mostrarAlertaBloqueio()" : ($ocup ? "" : "selecionarLab($idAmb, 'LAB $contador')");
                ?>
                    <div class="lab <?= $classe ?>" onclick="<?= $onclick ?>">
                        LAB <?= $contador ?>
                    </div>
                <?php 
                    $contador++;
                endwhile; 
                ?>
            </div>

            <br><br>

            <!-- 3. LABORATÓRIOS DE AUTOMAÇÃO -->
            <div class="titulo-laboratorios">
                <h2>Laboratórios de Automação</h2>
            </div>
            <div class="mapa">
                <?php
                $sqlAut = "SELECT id_ambientes, nome, status FROM ambientes WHERE tipo = 'AUT' ORDER BY id_ambientes";
                $resAut = mysqli_query($conexao, $sqlAut);
                $contador = 1;
                while ($amb = mysqli_fetch_assoc($resAut)):
                    $idAmb = (int)$amb['id_ambientes'];
                    $bloqueado = in_array($idAmb, $ambientesBloqueados);
                    $ocup = in_array($idAmb, $ocupados);
                    $classe = ($bloqueado || $ocup) ? 'ocupado' : '';
                    $onclick = $bloqueado ? "mostrarAlertaBloqueio()" : ($ocup ? "" : "selecionarLab($idAmb, 'LAB $contador')");
                ?>
                    <div class="lab <?= $classe ?>" onclick="<?= $onclick ?>">
                        LAB <?= $contador ?>
                    </div>
                <?php 
                    $contador++;
                endwhile; 
                ?>
            </div>

        </div>

        <!-- =================================================
             FORMULÁRIO DE RESERVA
        ================================================== -->
        <div id="overlayReserva" class="overlay-reserva">
            <div id="formReserva" class="form-reserva">
                <button type="button" class="fechar-formulario" onclick="fecharFormulario()">&times;</button>
                <h2>Solicitar reserva</h2>

                <form action="agendamento.php" method="POST">
                    <p id="labEscolhido"></p>
                    <p id="dataEscolhida"></p>
                    <p id="horarioEscolhido"></p>

                    <input type="hidden" name="id_ambientes" id="id_ambientes">
                    <input type="hidden" name="data_agendamento" id="data_agendamento">
                    <input type="hidden" name="horario" id="horario_form">

                    <label>Nome do solicitante:</label>
                    <input type="text" value="<?= htmlspecialchars($nome_usuario) ?>" readonly>

                    <label>Descrição:</label>
                    <textarea name="descr" maxlength="120" required placeholder="Descrição..."></textarea>

                    <button type="submit" name="enviar_agendamento">Enviar solicitação</button>
                </form>
            </div>
        </div>

        <div class="legenda">
            <h2> Legenda:</h2>
            <div class="item">
                <span class="ocupado"></span>
                Indisponível / Bloqueado
            </div>
            <div class="item">
                <span class="livre"></span>
                Disponível
            </div>
        </div>
    </div>
</div>

<div id="alertaDataHorario" class="overlay-alerta">
    <div class="caixa-alerta">
        <button type="button" class="fechar-alerta" onclick="fecharAlertaDataHorario()">&times;</button>
        <div class="icone-alerta"><i class="fa-solid fa-calendar-xmark"></i></div>
        <h2>Selecione uma data e um horário!</h2>
        <p>Para prosseguir o agendamento, selecione uma data e um horário</p>
    </div>
</div>

<div id="alertaBloqueio" class="overlay-alerta">
    <div class="caixa-alerta">
        <button type="button" class="fechar-alerta" onclick="fecharAlertaBloqueio()">&times;</button>
        <div class="icone-alerta"><i class="fa-solid fa-ban" style="color: red;"></i></div>
        <h2>Ambiente Bloqueado</h2>
        <p>Este ambiente foi bloqueado pela administração e não está disponível para agendamento.</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const data = document.getElementById("data");
    const horario = document.getElementById("horario");

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("sucesso") === "1") {
        sessionStorage.removeItem("data");
        sessionStorage.removeItem("horario");
    }

    function atualizarPagina() {
        if (data.value !== "" && horario.value !== "") {
            sessionStorage.setItem("data", data.value);
            sessionStorage.setItem("horario", horario.value);
            window.location.href = "agendamento.php?data=" + encodeURIComponent(data.value) + "&horario=" + encodeURIComponent(horario.value);
        }
    }

    data.addEventListener("change", atualizarPagina);
    horario.addEventListener("change", atualizarPagina);

    if (sessionStorage.getItem("data")) {
        data.value = sessionStorage.getItem("data");
    }

    if (sessionStorage.getItem("horario")) {
        horario.value = sessionStorage.getItem("horario");
    }
});

function selecionarLab(id, nome) {
    const data = document.getElementById("data").value;
    const horario = document.getElementById("horario").value;

    if (data === "" || horario === "") {
        document.getElementById("alertaDataHorario").style.display = "flex";
        return;
    }

    document.getElementById("overlayReserva").classList.add("ativo");
    document.getElementById("labEscolhido").innerHTML = "<strong>Laboratório:</strong> " + nome;
    document.getElementById("dataEscolhida").innerHTML = "<strong>Data:</strong> " + data;
    document.getElementById("horarioEscolhido").innerHTML = "<strong>Horário:</strong> " + horario;

    document.getElementById("id_ambientes").value = id;
    document.getElementById("data_agendamento").value = data;
    document.getElementById("horario_form").value = horario;
}

function mostrarAlertaBloqueio() {
    document.getElementById("alertaBloqueio").style.display = "flex";
}

function fecharAlertaDataHorario() {
    document.getElementById("alertaDataHorario").style.display = "none";
}

function fecharAlertaBloqueio() {
    document.getElementById("alertaBloqueio").style.display = "none";
}

function fecharFormulario() {
    document.getElementById("overlayReserva").classList.remove("ativo");
}

window.onpageshow = function(event) {
    if (event.persisted || (performance && performance.navigation.type === 2)) {
        document.body.innerHTML = ''; 
        window.location.replace("login.php");
    }
};
</script>

<section>
    <div class="container">
        <div class="row">
            <div class="col-sm-2 offset-sm-5 text-center">
                <button class="btn btn-warning text-white btn-block" onclick="voltar()">Voltar</button>
                <script>
                  function voltar(){
                    window.location.href = '../opcoes.html';
                  }
                </script>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
