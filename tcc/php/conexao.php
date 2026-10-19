<?php
// ==========================================================
// CONEXÃO ÚNICA COM O BANCO DE DADOS
// ==========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "MODELO_TCC";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Compatibilidade com arquivos antigos que usam $conexao.
$conexao = $conn;
?>
