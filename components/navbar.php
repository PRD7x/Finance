<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_index = ($current_page === 'index.php');
?>
<header class="navbar">
    <div class="logo">
        <?php if ($is_index): ?>
            💰 Meu Patrimônio
        <?php else: ?>
            <a href="index.php" style="color:white; text-decoration:none; display: flex; align-items: center; gap: 8px;">
                <i data-feather="arrow-left"></i> Voltar
            </a>
        <?php endif; ?>
    </div>

    <div class="user-area">
        <div class="avatar-container">
            <div class="avatar" id="avatarBtn" style="cursor: pointer;"><?php echo isset($first_letter) ? $first_letter : ''; ?></div>
            
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-header">
                    <span class="user-email"><?php echo htmlspecialchars(isset($email) ? $email : ''); ?></span>
                </div>
                <hr class="dropdown-divider">
                <a href="logout.php" class="dropdown-item">
                    <i data-feather="log-out"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </div>
</header>
