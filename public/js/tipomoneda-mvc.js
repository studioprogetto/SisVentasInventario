document.addEventListener('DOMContentLoaded', function() {
    const monedaModal = document.getElementById('monedaModal');
    if (monedaModal) {
        monedaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = monedaModal.querySelector('.modal-title');
            const form = monedaModal.querySelector('form');
            form.reset();
            
            document.getElementById('moneda_id').value = '';
            modalTitle.textContent = 'Agregar Nueva Moneda';
            
            if (button.classList.contains('edit-moneda-btn')) {
                modalTitle.textContent = 'Editar Moneda';
                document.getElementById('moneda_id').value = button.dataset.id;
                document.getElementById('moneda_descripcion').value = button.dataset.descripcion;
                document.getElementById('moneda_simbolo').value = button.dataset.simbolo;
            }
        });
    }
});