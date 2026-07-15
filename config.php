<?php

$dbHost = 'hayabusa.proxy.rlwy.net';
$dbUsername = 'root';
$dbPassword = 'jxLaJlcQyyCTbZlXnivxvWAcUAeCAFOd';
$dbName = 'railway';
$dbPort = 57565;

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort);

if ($conexao->connect_error) {
    die("Erro de Conexão: " . $conexao->connect_error);
}
?>