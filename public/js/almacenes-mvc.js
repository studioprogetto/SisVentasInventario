/**
 * Funcionalidades JavaScript para la gestión de almacenes
 */

class AlmacenesManager {
    constructor() {
        this.initEventListeners();
    }
    
    initEventListeners() {
        // Validación del formulario antes de enviar
        const form = document.getElementById('almacenForm');
        if (form) {
            form.addEventListener('submit', (e) => this.validarFormulario(e));
        }
        
        // Limpiar formulario cuando se cierra el modal
        const modal = document.getElementById('almacenModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', () => this.limpiarFormulario());
        }
    }
    
    validarFormulario(event) {
        const nombre = document.getElementById('almacen_nombre').value.trim();
        
        if (!nombre) {
            event.preventDefault();
            this.mostrarAlerta('El nombre del almacén es obligatorio', 'error');
            return false;
        }
        
        if (nombre.length < 2) {
            event.preventDefault();
            this.mostrarAlerta('El nombre debe tener al menos 2 caracteres', 'error');
            return false;
        }
        
        return true;
    }
    
    limpiarFormulario() {
        document.getElementById('almacenForm').reset();
        document.getElementById('almacen_id').value = '';
        document.getElementById('almacenModalLabel').textContent = 'Agregar Almacén';
    }
    
    mostrarAlerta(mensaje, tipo) {
        // Remover alertas existentes
        const alertasExistentes = document.querySelector('.alert-dismissible');
        if (alertasExistentes) {
            alertasExistentes.remove();
        }
        
        const alertClass = tipo === 'error' ? 'alert-danger' : 'alert-success';
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insertar al inicio del card-body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertAdjacentHTML('afterbegin', alertHTML);
    }
    
    // Método para cargar datos en el modal de edición
    cargarDatosEdicion(id, nombre, ubicacion) {
        document.getElementById('almacen_id').value = id;
        document.getElementById('almacen_nombre').value = nombre;
        document.getElementById('almacen_ubicacion').value = ubicacion;
        document.getElementById('almacenModalLabel').textContent = 'Editar Almacén';
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    new AlmacenesManager();
    
    // Asignar eventos a los botones de edición
    document.querySelectorAll('.edit-almacen-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const ubicacion = this.getAttribute('data-ubicacion');
            
            // Usar el método del manager si existe, o la función global
            if (window.almacenesManager) {
                window.almacenesManager.cargarDatosEdicion(id, nombre, ubicacion);
            } else {
                // Fallback a la función global
                document.getElementById('almacen_id').value = id;
                document.getElementById('almacen_nombre').value = nombre;
                document.getElementById('almacen_ubicacion').value = ubicacion;
                document.getElementById('almacenModalLabel').textContent = 'Editar Almacén';
            }
        });
    });
});

// Función global para limpiar formulario (compatibilidad)
function limpiarFormulario() {
    document.getElementById('almacenForm').reset();
    document.getElementById('almacen_id').value = '';
    document.getElementById('almacenModalLabel').textContent = 'Agregar Almacén';
}