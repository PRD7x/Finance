<?php

$dbHost = getenv('MYSQLHOST') ?: 'hayabusa.proxy.rlwy.net';
$dbUsername = getenv('MYSQLUSER') ?: 'root';
$dbPassword = getenv('MYSQLPASSWORD') ?: 'jxLaJlcQyyCTbZlXnivxvWAcUAeCAFOd';
$dbName = getenv('MYSQLDATABASE') ?: 'railway';
$dbPort = intval(getenv('MYSQLPORT') ?: 57565);

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort);

if ($conexao->connect_error) {
    die("Erro de Conexão: " . $conexao->connect_error);
}
?>