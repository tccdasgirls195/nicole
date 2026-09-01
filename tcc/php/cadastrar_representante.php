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
// VARIÁVEIS INICIAIS
// =====================================================

$cadastroSucesso = false;
$erro = "";


// =====================================================
// CADASTRAR REPRESENTANTE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senhaUsuario = trim($_POST["senha"]);
    $idTurma = intval($_POST["id_turma"]);


    // =================================================
    // VALIDAÇÃO
    // =================================================

    if (
        empty($nome) ||
        empty($email) ||
        empty($senhaUsuario) ||
        $idTurma <= 0
    ) {

        $erro = "Preencha todos os campos.";

    } else {


        // =================================================
        // CADASTRO DO REPRESENTANTE
        // =================================================

        $sql = "INSERT INTO representante
                (nome, email, senha, id_turma, status)
                VALUES (?, ?, ?, ?, 'Ativo')";

        $stmt = $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param(
                "sssi",
                $nome,
                $email,
                $senhaUsuario,
                $idTurma
            );


            if ($stmt->execute()) {

                $cadastroSucesso = true;

            } else {

                $erro = "Erro ao cadastrar representante: "
                      . $stmt->error;

            }

            $stmt->close();

        } else {

            $erro = "Erro ao preparar o cadastro: "
                  . $conn->error;

        }

    }

}


// =====================================================
// BUSCAR TURMAS
// =====================================================

$turmas = $conn->query(
    "SELECT id_turma, serie, curso
     FROM turma
     ORDER BY serie, curso"
);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cadastrar Representante</title>

    <link
        rel="stylesheet"
        href="../css/cadastrar_usuario.css"
    >

</head>


<body>


<header class="menu">

    <div class="logo">

        <img src="../logo.png">

    </div>


    <nav>

        <a href="">
            Home
        </a>

        <a href="#" class="has-submenu">
            Cursos
        </a>

        <a href="#" class="has-submenu">
            A Etec
        </a>

        <a href="#" class="has-submenu">
            Equipe Etec
        </a>

        <li>

            <a
                href="../selecionar_lab.html"
                class="has-submenu"
            >
                Agendamento
            </a>

            <ul class="submenu">

                <li>

                    <a href="meus-agendamentos.php">
                        Meus agendamentos
                    </a>

                </li>

            </ul>

        </li>

        <a href="#" class="has-submenu">
            Notícias
        </a>

        <a href="">
            Empregos & Estágios
        </a>

        <a href="">
            Parceiros
        </a>

        <a href="">
            TCC
        </a>

    </nav>

</header>



<?php if ($cadastroSucesso): ?>


    <!-- =================================================
         MENSAGEM DE SUCESSO
    ================================================== -->

    <main class="mensagem">

        <div class="caixa-sucesso">

            <div class="icone-sucesso">
                ✓
            </div>


            <h1>
                Representante cadastrado com sucesso!!!
            </h1>


            <p>
                O novo representante foi adicionado ao sistema.
            </p>


            <a
                href="gerenciar_representantes.php"
                class="voltar"
            >

                Voltar para gerenciamento

            </a>

        </div>

    </main>


<?php else: ?>


    <!-- =================================================
         FORMULÁRIO
    ================================================== -->

    <main class="container">


        <h1>
            Cadastrar Representante
        </h1>


        <p class="subtitulo">

            Preencha os dados para cadastrar um novo representante.

        </p>



        <?php if (!empty($erro)): ?>

            <div class="erro">

                <?php echo htmlspecialchars($erro); ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            class="formulario"
        >


            <!-- =================================================
                 NOME
            ================================================== -->

            <label for="nome">
                Nome
            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                required
            >



            <!-- =================================================
                 E-MAIL
            ================================================== -->

            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >



            <!-- =================================================
                 SENHA
            ================================================== -->

            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                required
            >



            <!-- =================================================
                 TURMA DO REPRESENTANTE
            ================================================== -->

            <label for="id_turma">
                Turma
            </label>

            <select
                name="id_turma"
                id="id_turma"
                required
            >

                <option value="">
                    Selecione a turma
                </option>


                <?php if ($turmas && $turmas->num_rows > 0): ?>

                    <?php while ($turma = $turmas->fetch_assoc()): ?>

                        <option
                            value="<?php echo $turma["id_turma"]; ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $turma["serie"]
                            );

                            echo " - ";

                            echo htmlspecialchars(
                                $turma["curso"]
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>


            </select>



            <!-- =================================================
                 BOTÕES
            ================================================== -->

            <div class="botoes">


                <button
                    type="button"
                    class="cancelar"
                    onclick="window.location.href='gerenciar_representantes.php';"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="cadastrar"
                >

                    Cadastrar representante

                </button>


            </div>


        </form>


    </main>


<?php endif; ?>


</body>

</html>


<?php

$conn->close();

?>