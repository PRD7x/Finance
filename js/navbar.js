// Lógica do menu dropdown do usuário
document.addEventListener('DOMContentLoaded', () => {
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (avatarBtn && dropdownMenu) {
        // Abre/fecha o menu dropdown ao clicar no botão do avatar
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Impede o clique de se propagar para o documento
            dropdownMenu.classList.toggle('show');
        });

        // Fecha o menu dropdown se o usuário clicar em qualquer outra parte da tela
        document.addEventListener('click', (e) => {
            if (!dropdownMenu.contains(e.target) && e.target !== avatarBtn) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
