document.addEventListener('DOMContentLoaded', function() {
    // ... (toda la lógica del carrito de compras) ...
    
    // --- BÚSQUEDA DE PRODUCTOS (apuntando al controlador) ---
    buscarInput.addEventListener('keyup', function() {
        const term = this.value;
        // MODIFICADO: la ruta ahora es /compra/buscarProductos
        fetch(`/mi_sistema_mvc/public/compra/buscarProductos?term=${term}`)
            .then(response => response.json())
            .then(data => {
                // ... (la lógica para mostrar resultados no cambia) ...
            });
    });
});