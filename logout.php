<?php
// Inicia ou retoma a sessão ativa do usuário
session_start();

// Destrói todas as variáveis e encerra a sessão ativa no servidor
session_destroy();

// Redireciona o usuário de volta para a tela de login
header("Location: login.php");
exit();
?>
