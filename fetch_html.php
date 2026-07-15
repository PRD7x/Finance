<?php
require 'config.php';
$_SESSION['user_id'] = 2; // Mock session
ob_start();
include 'expenses.php';
$html = ob_get_clean();
// We only want the tables
$start = strpos($html, '<table class="tabela-dados">');
$end = strpos($html, '<div id="aba-variaveis"');
if ($start !== false && $end !== false) {
    echo substr($html, $start, $end - $start);
} else {
    echo "Could not find tables\n";
}
