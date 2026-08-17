document.addEventListener('DOMContentLoaded', function() {
    // --- VARIABLES PRINCIPALES ---
    let carrito = [];
    let totalOriginal = 0;
    let descuentoSellos = 0;
    let descuentoManual = 0;

    const MONEDA = '<?php echo getMoneda(); ?>';
    const buscarInput = document.getElementById('buscar_producto');
    const resultadosBusqueda = document.getElementById('resultados_busqueda');
    const carritoTbody = document.getElementById('carrito_tbody');
    const totalVentaSpan = document.getElementById('total_venta');
    const btnFinalizarVenta = document.getElementById('btn_finalizar_venta');
    const modalTotalPagar = document.getElementById('modal_total_pagar');
    const montoRecibidoInput = document.getElementById('monto_recibido');
    const cambioClienteSpan = document.getElementById('cambio_cliente');
    const btnConfirmarVenta = document.getElementById('btn_confirmar_venta');
    const metodoPagoSelect = document.getElementById('metodo_pago');
    const pagoEfectivoDiv = document.getElementById('pago_efectivo_div');
    const idClienteSelect = document.getElementById('id_cliente');
    const nombreClienteSpan = document.getElementById('nombreCliente');
    const dniClienteSpan = document.getElementById('dniCliente');
    const sellosClienteSpan = document.getElementById('sellosCliente');
    const infoClienteDiv = document.getElementById('infoCliente');
    const tarjetaSellosDiv = document.getElementById('tarjeta-sellos');
    const cardTarjetaSellos = document.getElementById('card_tarjeta_sellos');

    const redondearBajoBtn = document.getElementById('redondear_bajo');
    const redondearNormalBtn = document.getElementById('redondear_normal');
    const descuentoInput = document.getElementById('descuento_manual');
    const aplicarDescuentoBtn = document.getElementById('aplicar_descuento');
    const descuentoSellosSpan = document.getElementById('descuento_sellos_span');
    const descuentoManualSpan = document.getElementById('descuento_manual_span');

    // ------------------------------
    // --- BÚSQUEDA DE PRODUCTOS ---
    // ------------------------------
    buscarInput.addEventListener('keyup', function() {
        const term = this.value.trim();
        if(term.length < 2){ resultadosBusqueda.innerHTML=''; return; }

        fetch(`/SisVentasInventario/public/venta/buscar?term=${encodeURIComponent(term)}`)
        .then(r=>r.json())
        .then(data=>{
            resultadosBusqueda.innerHTML='';
            if(data.length){
                data.forEach(p=>{
                    const item=document.createElement('a');
                    item.href='#';
                    item.className='list-group-item list-group-item-action';
                    item.textContent=`${p.nombre} - Stock: ${p.stock} - ${MONEDA}${parseFloat(p.precio_venta).toFixed(2)}`;
                    item.addEventListener('click', e=>{
                        e.preventDefault();
                        agregarAlCarrito(p);
                        buscarInput.value='';
                        resultadosBusqueda.innerHTML='';
                    });
                    resultadosBusqueda.appendChild(item);
                });
            }else{
                resultadosBusqueda.innerHTML='<span class="list-group-item">No se encontraron productos</span>';
            }
        }).catch(err=>console.error(err));
    });

    function agregarAlCarrito(item){
        const existente = carrito.find(p=>p.id===item.id && p.tipo===item.tipo);
        if(existente) existente.cantidad++;
        else carrito.push({id:item.id,nombre:item.nombre,precio:parseFloat(item.precio_venta),cantidad:1,tipo:item.tipo});
        renderizarCarrito();
    }

    function renderizarCarrito(){
        carritoTbody.innerHTML='';
        carrito.forEach((item,idx)=>{
            const subtotal = item.precio * item.cantidad;
            const tr = document.createElement('tr');
            tr.innerHTML=`
                <td>${item.nombre} <small class="text-muted">(${item.tipo})</small></td>
                <td>${MONEDA}${item.precio.toFixed(2)}</td>
                <td><input type="number" min="1" class="form-control form-control-sm cantidad-input" data-index="${idx}" value="${item.cantidad}"></td>
                <td>${MONEDA}${subtotal.toFixed(2)}</td>
                <td><button class="btn btn-danger btn-sm eliminar-btn" data-index="${idx}"><i class="fas fa-trash"></i></button></td>
            `;
            carritoTbody.appendChild(tr);
        });
        calcularTotales();
        btnFinalizarVenta.disabled = carrito.length === 0;
    }

    carritoTbody.addEventListener('change', e=>{
        if(e.target.classList.contains('cantidad-input')){
            const idx = e.target.dataset.index;
            const qty = parseInt(e.target.value);
            if(qty>0){ carrito[idx].cantidad=qty; renderizarCarrito(); }
        }
    });

    carritoTbody.addEventListener('click', e=>{
        const btn = e.target.closest('.eliminar-btn');
        if(btn){ carrito.splice(btn.dataset.index,1); renderizarCarrito(); }
    });

    // ------------------------------
    // --- CALCULO DE TOTALES ---
    // ------------------------------
    function calcularTotales(){
        totalOriginal = carrito.reduce((s,i)=>s+i.precio*i.cantidad,0);

        let sellosCliente = parseInt(sellosClienteSpan.textContent)||0;
        descuentoSellos = 0;

        if(!idClienteSelect.value){
            cardTarjetaSellos.style.display='none';
            sellosCliente = 0;
        }else{
            cardTarjetaSellos.style.display='block';
            if(sellosCliente>=12) descuentoSellos=0.10;
            else if(sellosCliente>=6) descuentoSellos=0.05;
        }

        let totalConSellos = totalOriginal - (totalOriginal*descuentoSellos);
        let totalFinal = Math.max(0,totalConSellos - descuentoManual);

        descuentoSellosSpan.textContent = descuentoSellos>0 ? `Descuento por sellos: ${descuentoSellos*100}%` : '';
        descuentoManualSpan.textContent = descuentoManual>0 ? `Descuento aplicado: ${MONEDA}${descuentoManual.toFixed(2)}` : '';
        totalVentaSpan.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;
        modalTotalPagar.textContent = `${MONEDA}${totalFinal.toFixed(2)}`;

        const sellosAGuardar = idClienteSelect.value ? carrito.filter(i=>i.tipo==='producto').reduce((s,i)=>s+i.cantidad,0) : 0;
        renderTarjetaSellos(sellosCliente, sellosAGuardar);
    }

    redondearBajoBtn.addEventListener('click', ()=>{
        descuentoManual=Math.max(0,totalOriginal*(1-descuentoSellos)-Math.floor(totalOriginal*(1-descuentoSellos)));
        calcularTotales();
    });

    redondearNormalBtn.addEventListener('click', ()=>{
        descuentoManual=Math.max(0,totalOriginal*(1-descuentoSellos)-Math.round(totalOriginal*(1-descuentoSellos)));
        calcularTotales();
    });

    aplicarDescuentoBtn.addEventListener('click', ()=>{
        descuentoManual=parseFloat(descuentoInput.value)||0;
        calcularTotales();
    });

    // ------------------------------
    // --- MODAL DE PAGO ---
    // ------------------------------
    btnFinalizarVenta.addEventListener('click', ()=>{
        calcularTotales();
        montoRecibidoInput.value='';
        cambioClienteSpan.textContent=`${MONEDA}0.00`;
    });

    metodoPagoSelect.addEventListener('change', ()=>{
        pagoEfectivoDiv.style.display = metodoPagoSelect.value==='efectivo'?'block':'none';
    });

    montoRecibidoInput.addEventListener('keyup', ()=>{
        const total=parseFloat(modalTotalPagar.textContent.replace(MONEDA,''))||0;
        const recibido=parseFloat(montoRecibidoInput.value)||0;
        cambioClienteSpan.textContent=`${MONEDA}${(recibido-total).toFixed(2)}`;
    });

    // ------------------------------
    // --- CONFIRMAR VENTA ---
    // ------------------------------
    btnConfirmarVenta.addEventListener('click', function(){
    if(!carrito.length){ alert("El carrito está vacío."); return; }
    this.disabled=true;

    const totalFinal = parseFloat(modalTotalPagar.textContent.replace(MONEDA,''))||0;
    const observacion = document.getElementById('observacion_venta').value.trim() || '';
    
    const ventaData = {
        id_cliente: idClienteSelect.value || null,
        metodo_pago: metodoPagoSelect.value,
        descuento_manual: descuentoManual,
        observacion: observacion, 
        carrito: carrito.map(i => ({
            id: i.id,
            tipo: i.tipo,
            cantidad: i.cantidad,
            precio: i.precio
        }))
    };

    console.log("Enviando datos:", ventaData); // Para debug

    fetch('/SisVentasInventario/public/venta/guardar',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(ventaData)
    }).then(r => {
        if (!r.ok) {
            throw new Error('Error HTTP: ' + r.status);
        }
        return r.json();
    }).then(data => {
        if(data.success){
            window.open(`/SisVentasInventario/public/venta/ticket/${data.id_venta}`,'_blank');
            // Limpiar carrito y resetear
            carrito = [];
            descuentoManual = 0;
            idClienteSelect.value = '';
            infoClienteDiv.style.display = 'none';
            renderizarCarrito();
            calcularTotales();
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('pagoModal'));
            modal.hide();
            
            alert('Venta registrada exitosamente!');
        } else {
            alert('Error: ' + (data.error || 'Ocurrió un error'));
            this.disabled = false;
        }
    }).catch(err => {
        console.error('Error completo:', err);
        alert('Error de conexión: ' + err.message);
        this.disabled = false;
    });
});

    // ------------------------------
    // --- TARJETA DE SELLOS ---
    // ------------------------------
    function renderTarjetaSellos(sellosActuales, sellosAAgregar=0){
        tarjetaSellosDiv.innerHTML='';
        if(!idClienteSelect.value) return; // nada para genérico

        let totalSellos = sellosActuales + sellosAAgregar;
        if(totalSellos>12) totalSellos=12;
        for(let i=1;i<=12;i++){
            const icon=document.createElement('i');
            icon.classList.add('fas','fa-star','fa-2x','me-1');
            if(i<=sellosActuales) icon.classList.add('text-warning');
            else if(i<=totalSellos) icon.classList.add('text-success');
            else icon.classList.add('text-secondary');
            tarjetaSellosDiv.appendChild(icon);
        }
    }

    // ------------------------------
    // --- AUTOCOMPLETE CLIENTES ---
    // ------------------------------
    $("#buscarCliente").autocomplete({
        source:function(request,response){
            $.ajax({
                url:"/SisVentasInventario/public/cliente/buscar",
                dataType:"json",
                data:{term:request.term},
                success:function(data){
                    const opciones=[{label:"Venta genérica",value:"Venta genérica",id:null,dni:"-",sellos:0}]
                        .concat(data.map(item=>({label:item.nombre_cliente+" ("+item.documento_identidad+")",value:item.nombre_cliente,id:item.id_cliente,dni:item.documento_identidad,sellos:item.sellos})));
                    response(opciones);
                }
            });
        },
        minLength:0,
        select:function(event,ui){
            idClienteSelect.value=ui.item.id;
            nombreClienteSpan.textContent=ui.item.value;
            dniClienteSpan.textContent=ui.item.dni;
            sellosClienteSpan.textContent=ui.item.sellos;
            infoClienteDiv.style.display='block';
            calcularTotales();
        }
    }).focus(function(){ $(this).autocomplete("search",""); });
});
