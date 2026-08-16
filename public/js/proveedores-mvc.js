document.addEventListener('DOMContentLoaded', function() {
    
    const proveedorModal = document.getElementById('proveedorModal');
    
    if (proveedorModal) {
        proveedorModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = proveedorModal.querySelector('.modal-title');
            const form = proveedorModal.querySelector('form');
            
            form.reset();
            document.getElementById('id_proveedor').value = '';
            modalTitle.textContent = 'Agregar Nuevo Proveedor';

            if (button.classList.contains('edit-proveedor-btn')) {
                modalTitle.textContent = 'Editar Proveedor';
                
                // Llenar el formulario con los datos del botón
                document.getElementById('id_proveedor').value = button.dataset.id;
                document.getElementById('nombre_proveedor').value = button.dataset.nombre;
                document.getElementById('ruc').value = button.dataset.ruc;
                document.getElementById('telefono').value = button.dataset.telefono;
                document.getElementById('email').value = button.dataset.email;
                document.getElementById('direccion').value = button.dataset.direccion;
            }
        });
    }

});