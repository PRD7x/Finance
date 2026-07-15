<?php

$dbHost = '127.0.0.1';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'dados';
$dbPort = 3306;

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort);

if ($conexao->connect_error) {
    die("Erro de Conexão: " . $conexao->connect_error);
}
?>