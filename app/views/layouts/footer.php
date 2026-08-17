    </main>

    <!-- Footer dentro de .main-content -->
    <footer class="bg-studio text-white text-center p-2 mt-3">
        <p>&copy; <?php echo date('Y'); ?> - Sistema de Gestión de Inventario y de Ventas - Studio & Progetto</p>
    </footer>
</div> <!-- cierre de .main-content -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="<?php echo BASE_URL; ?>js/theme.js"></script>
<script src="<?php echo BASE_URL; ?>js/scripts.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const TARGET_HOUR = 20; // 8 PM
    const TARGET_MIN = 30;

    function pad(n){ return String(n).padStart(2,'0'); }
    function hoyYYYYMMDD(date) {
        return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate());
    }

    const horaElemento = document.getElementById('hora-local');
    const avisoElemento = document.getElementById('avisohora');

    function actualizarHora() {
        const ahora = new Date();

        if (!horaElemento || !avisoElemento) return;

        // Mostrar hora en HH:MM:SS
        const opciones = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        horaElemento.textContent = ahora.toLocaleTimeString('es-PE', opciones);

        // Color por defecto
        horaElemento.style.color = 'black';
        avisoElemento.textContent = '';

        const key = 'modalMostrado_' + hoyYYYYMMDD(ahora) + '_' + pad(TARGET_HOUR) + pad(TARGET_MIN);

        if (ahora.getHours() > TARGET_HOUR || (ahora.getHours() === TARGET_HOUR && ahora.getMinutes() >= TARGET_MIN)) {
            horaElemento.style.color = 'red';
            avisoElemento.textContent = '🕒 Hora de irse';
            avisoElemento.style.color = 'blue';

            if (!localStorage.getItem(key)) {
                localStorage.setItem(key, '1');
                Swal.fire({
                    title: 'Terminó el día laboral',
                    text: 'Hora de irse 😎',
                    icon: 'info',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    }

    actualizarHora();
    setInterval(actualizarHora, 1000);
});

</script>

<script>
// Aplica `width` a progress bars que usan `data-percentage`.
function applyProgressWidths(root=document){
    root.querySelectorAll('.progress-bar[data-percentage]').forEach(function(pb){
        var val = pb.getAttribute('data-percentage');
        if(val !== null){
            var num = parseFloat(val) || 0;
            pb.style.width = Math.min(Math.max(num, 0), 100) + '%';
        }
    });
}

document.addEventListener('DOMContentLoaded', function(){ applyProgressWidths(document); });

// Expose for dynamic updates
window.applyProgressWidths = applyProgressWidths;
</script>

</body>
</html>