document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA PARA EL MENÚ LATERAL RESPONSIVO ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});