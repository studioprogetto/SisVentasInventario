(() => {
  'use strict';
  const form = document.querySelector('[data-pos]');
  if (!form) return;
  const $ = selector => form.querySelector(selector);
  const node = (tag, className, text) => {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
  };
  const button = (text, className, action) => {
    const element = node('button', className, text); element.type = 'button';
    element.addEventListener('click', action); return element;
  };
  let page = 1, lastPage = 1, warehouse = '', services = false;
  let revision = 0, quoteTimer, searchTimer, clientTimer;
  let searchController, clientController, quoteController, clientRevision = 0;
  let currency = 'S/', selectedClient = null, cart = [];
  let pendingClient = false;
  const toNumber = value => Number.parseFloat(String(value ?? 0).replace(/[^\d.-]/g, '')) || 0;
  const money = amount => `${currency || 'S/'} ${Number(amount).toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
  const key = item => `${item.tipo}:${item.id}:${item.id_almacen || 0}`;
  async function json(url, options = {}) {
    const response = await fetch(url, {credentials:'same-origin', ...options, headers:{Accept:'application/json', ...options.headers}});
    const data = await response.json();
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'No se pudo completar la consulta.');
    return data;
  }
  function syncPaymentState() {
    const method = (String($('[name=metodo_pago]')?.value || 'efectivo')).toLowerCase();
    const cashPanel = $('[data-pos-cash-fields]');
    const status = $('[data-pos-payment-status]');
    const receivedField = $('[name=monto_recibido]');
    if (!cashPanel || !status || !receivedField) return;
    const shouldShowCash = method === 'efectivo' || method === 'mixto';
    cashPanel.hidden = !shouldShowCash;
    if (shouldShowCash) {
      receivedField.required = true;
      receivedField.setAttribute('aria-required', 'true');
    } else {
      receivedField.required = false;
      receivedField.setAttribute('aria-required', 'false');
      receivedField.value = '0';
    }
    if (!cart.length || !$('[name=accepted_total]').value) {
      status.textContent = shouldShowCash ? `Vuelto: ${money(0)}` : 'Pago registrado.';
      return;
    }
    if (!shouldShowCash) {
      status.textContent = 'Pago registrado.';
      return;
    }
    const total = toNumber($('[name=accepted_total]').value);
    const received = toNumber(receivedField.value);
    const delta = received - total;
    status.textContent = delta >= 0 ? `Vuelto: ${money(delta)}` : `Falta: ${money(Math.abs(delta))}`;
  }
  function invalidate() {
    revision++;
    clearTimeout(quoteTimer); quoteController?.abort();
    $('[data-confirm-sale]').disabled = true;
    $('[name=accepted_total]').value = '';
    $('[data-pos-total]').textContent = '—';
    $('[data-pos-breakdown]').hidden = true;
    form.querySelectorAll('.pos-line-subtotal').forEach(cell => { cell.textContent = '—'; });
    syncPaymentState();
    $('[data-pos-quote-status]').textContent = cart.length ? 'Actualizando total…' : 'Agrega un producto para calcular el total.';
    if (cart.length && !pendingClient) quoteTimer = setTimeout(calculate, 350);
  }
  function renderCart() {
    const body = $('[data-pos-cart]'); body.replaceChildren();
    $('[data-pos-empty]').hidden = cart.length > 0;
    $('[data-pos-count]').textContent = `${cart.reduce((n, line) => n + line.cantidad, 0)} artículos`;
    cart.forEach((item, index) => {
      const row = node('tr');
      const name = node('td'); name.append(node('strong','',item.nombre), node('small','',item.almacen));
      for (const [field,value] of Object.entries({id:item.id,tipo:item.tipo,id_almacen:item.id_almacen})) {
        if (value === null) continue;
        const hidden = node('input'); hidden.type='hidden'; hidden.name=`carrito[${index}][${field}]`; hidden.value=String(value); name.append(hidden);
      }
      const quantityCell = node('td');
      const quantity = node('input','pos-quantity'); quantity.type='number'; quantity.min='1'; quantity.max=String(item.stock === null ? 100000 : item.stock);
      quantity.step='1'; quantity.value=String(item.cantidad); quantity.name=`carrito[${index}][cantidad]`; quantity.setAttribute('aria-label',`Cantidad de ${item.nombre} en ${item.almacen}`);
      quantity.addEventListener('input', () => {
        const value = Number(quantity.value);
        item.cantidad = Number.isInteger(value) && value > 0 ? value : 0;
        $('[data-pos-count]').textContent = `${cart.reduce((n,line)=>n+line.cantidad,0)} artículos`;
        invalidate();
      });
      quantityCell.append(quantity);
      const price = node('td','pos-line-price',money(item.precio_venta));
      const subtotal = node('td','pos-line-subtotal','—');
      const removeCell = node('td');
      const remove = button('×','pos-remove',()=>{cart.splice(index,1);renderCart();invalidate();});
      remove.setAttribute('aria-label',`Quitar ${item.nombre} de ${item.almacen}`); removeCell.append(remove);
      row.append(name,quantityCell,price,subtotal,removeCell); body.append(row);
    });
  }
  async function calculate() {
    clearTimeout(quoteTimer);
    if (!cart.length || pendingClient) return;
    if (!form.checkValidity()) {
      $('[data-pos-quote-status]').textContent = 'Revisa las cantidades y los datos de pago.'; return;
    }
    quoteController?.abort(); quoteController = new AbortController();
    const current = revision;
    $('[data-confirm-sale]').disabled = true;
    $('[data-pos-quote-status]').textContent = 'Validando precios y descuentos…';
    try {
      const data = await json(form.dataset.quoteUrl, {method:'POST', body:new FormData(form), signal:quoteController.signal});
      if (current !== revision) return;
      currency = data.moneda_codigo;
      $('[name=accepted_total]').value = data.total;
      $('[data-pos-total]').textContent = money(data.total);
      const breakdown = $('[data-pos-breakdown]'); breakdown.replaceChildren(); breakdown.hidden = false;
      for (const [label,value] of [['Subtotal',data.subtotal],['Descuento de fidelidad',data.descuento_sellos],['Descuento autorizado',data.descuento_manual],['Saldo aplicado',data.saldo_aplicable],['Por cobrar',data.por_cobrar]]) {
        const line = node('div'); line.append(node('span','',label),node('strong','',money(value))); breakdown.append(line);
      }
      [...$('[data-pos-cart]').children].forEach((row,index)=>{
        const line = data.lineas[index];
        row.querySelector('.pos-line-price').textContent = money(line.precio);
        row.querySelector('.pos-line-subtotal').textContent = money(line.subtotal);
      });
      syncPaymentState();
      $('[data-pos-quote-status]').textContent = 'Total actualizado. Revisa y finaliza la venta.';
      $('[data-confirm-sale]').disabled = false;
    } catch (error) {
      if (error.name !== 'AbortError' && current === revision) $('[data-pos-quote-status]').textContent = error.message;
    }
  }
  function add(item) {
    const existing = cart.find(line=>key(line)===key(item));
    const next = existing ? existing.cantidad + 1 : 1;
    if (item.stock !== null && next > item.stock) {
      $('[data-pos-quote-status]').textContent = 'No hay más unidades disponibles en este almacén.'; return;
    }
    if (existing) existing.cantidad = next;
    else if (cart.length < 200) cart.push({...item,cantidad:1});
    else { $('[data-pos-quote-status]').textContent = 'El carrito admite hasta 200 líneas.'; return; }
    renderCart(); invalidate();
  }
  async function loadItems() {
    clearTimeout(searchTimer); searchController?.abort(); searchController = new AbortController();
    const url = new URL(form.dataset.itemsUrl);
    url.searchParams.set('term',$('[data-pos-search]').value.trim()); url.searchParams.set('page',page);
    url.searchParams.set('per_page',$('[data-pos-page-size]').value); url.searchParams.set('id_moneda',$('[name=id_moneda]').value);
    if (warehouse) url.searchParams.set('almacen',warehouse);
    if (services) url.searchParams.set('tipo','servicio');
    $('[data-pos-status]').textContent = 'Buscando productos…'; $('[data-pos-products]').setAttribute('aria-busy','true');
    form.querySelectorAll('.pos-add').forEach(button => { button.disabled = true; });
    try {
      const data = await json(url,{signal:searchController.signal});
      currency = data.currency; lastPage = data.last_page;
      const grid = $('[data-pos-products]'); grid.replaceChildren();
      data.data.forEach(item=>{
        const card = node('article','pos-product');
        const visual = node('div','pos-product-image');
        if (item.imagen) {
          const image = node('img'); image.src = item.imagen; image.alt = ''; image.loading='lazy';
          image.addEventListener('error',()=>{visual.replaceChildren(node('span','pos-service-icon','▧'));},{once:true}); visual.append(image);
        } else visual.append(node('span','pos-service-icon','✦'));
        const name = node('h3','',item.nombre);
        const location = node('p','pos-product-location',item.almacen);
        const stock = node('small','',!item.stock_consistente ? 'Stock pendiente de conciliación' : (item.stock === null ? 'Servicio · sin stock físico' : `${item.stock} disponibles`));
        const footer = node('div','pos-product-footer');
        const addButton = button('+','pos-add',()=>add(item)); addButton.setAttribute('aria-label',`Agregar ${item.nombre} de ${item.almacen}`);
        addButton.disabled = !item.stock_consistente || (item.stock !== null && item.stock < 1);
        footer.append(node('strong','',money(item.precio_venta)),addButton);
        card.append(visual,name,location,stock,footer); grid.append(card);
      });
      if (!data.data.length) grid.append(node('p','empty-state','No hay resultados. Prueba otro nombre, código o almacén.'));
      $('[data-pos-status]').textContent = `${data.total} resultados · precios en ${currency}`;
      $('[data-pos-page]').textContent = `${data.current_page} / ${lastPage}`;
      $('[data-pos-prev]').disabled = page <= 1; $('[data-pos-next]').disabled = page >= lastPage;
    } catch (error) {
      if (error.name !== 'AbortError') { $('[data-pos-status]').textContent = error.message; $('[data-pos-products]').replaceChildren(); }
    } finally { $('[data-pos-products]').setAttribute('aria-busy','false'); }
  }
  function resetPage() { page=1;loadItems(); }
  $('[data-pos-search]').addEventListener('input',()=>{
    clearTimeout(searchTimer); searchController?.abort();
    $('[data-pos-status]').textContent = 'Esperando 300 ms para buscar…';
    searchTimer=setTimeout(resetPage,300);
  });
  $('[data-pos-search]').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();resetPage();}});
  $('[data-pos-search-now]').addEventListener('click',resetPage);
  $('[name=metodo_pago]')?.addEventListener('change', syncPaymentState);
  $('[name=monto_recibido]')?.addEventListener('input', syncPaymentState);
  form.querySelectorAll('[data-pos-warehouse],[data-pos-services]').forEach(tab=>tab.addEventListener('click',()=>{
    warehouse=tab.dataset.posWarehouse || ''; services=tab.hasAttribute('data-pos-services');
    form.querySelectorAll('.warehouse-tab').forEach(button=>{const active=button===tab;button.classList.toggle('is-active',active);button.setAttribute('aria-pressed',String(active));}); resetPage();
  }));
  $('[data-pos-prev]').addEventListener('click',()=>{if(page>1){page--;loadItems();}});
  $('[data-pos-next]').addEventListener('click',()=>{if(page<lastPage){page++;loadItems();}});
  $('[data-pos-page-size]').addEventListener('change',resetPage);
  function clearClient() {
    clientRevision++; selectedClient=null;pendingClient=false;clientController?.abort();clearTimeout(clientTimer);
    $('[name=id_cliente]').value='';$('[data-pos-client-card]').hidden=true;
    $('[data-pos-client-results]').replaceChildren();$('[data-pos-client-search]').value='';
    $('[data-pos-client-search]').setAttribute('aria-expanded','false');
    $('[data-pos-client-status]').textContent='Venta sin cliente. Selecciona uno para consultar fidelidad y saldo.'; invalidate();
  }
  async function selectClient(id) {
    const current=++clientRevision;pendingClient=true;invalidate();
    $('[data-pos-client-results]').replaceChildren();$('[data-pos-client-search]').setAttribute('aria-expanded','false');
    $('[data-pos-client-card]').hidden=true;$('[data-pos-client-status]').textContent='Consultando compras, saldo y fidelidad…';
    try {
      const url=new URL(form.dataset.clientDetailUrl+'/'+id);url.searchParams.set('id_moneda',$('[name=id_moneda]').value);
      const data=await json(url);if(current!==clientRevision)return;
      selectedClient=id;$('[name=id_cliente]').value=String(id);pendingClient=false;
      $('[data-pos-client-search]').value=data.nombre_cliente;$('[data-pos-client-status]').textContent='Cliente seleccionado.';
      $('[data-pos-client-card]').hidden=false;$('[data-client-name]').textContent=data.nombre_cliente;
      $('[data-client-document]').textContent=data.documento_identidad ? `DNI / documento: ${data.documento_identidad}` : 'Sin documento registrado';
      $('[data-client-sales]').textContent=data.ventas_completadas;
      $('[data-client-credit]').textContent=`${data.moneda_codigo} ${data.saldo_disponible}`;
      const stamps=Math.max(0,Math.min(11,Number(data.sellos)));
      $('[data-client-stamps]').textContent=`${stamps} / 12 sellos`;
      const stars=$('[data-loyalty-stars]');stars.replaceChildren();stars.setAttribute('aria-label',`${stamps} de 12 sellos acumulados`);
      for(let index=0;index<12;index++) {const star=node('span',index<stamps?'is-earned':(index===stamps?'is-next':''),'★');star.setAttribute('aria-hidden','true');stars.append(star);}
      $('[data-loyalty-message]').textContent=data.descuento_proxima_compra ? `Esta compra con productos alcanza el ${data.descuento_proxima_compra}% de descuento.` : '6.ª compra: 5% · 12.ª compra: 10%. Sólo compras con productos suman sellos.';
      invalidate();
    } catch(error) {
      if(current!==clientRevision)return;pendingClient=false;selectedClient=null;$('[name=id_cliente]').value='';
      $('[data-pos-client-status]').textContent=error.message+' Selecciona nuevamente el cliente.';invalidate();
    }
  }
  async function searchClients() {
    clearTimeout(clientTimer);clientController?.abort();const term=$('[data-pos-client-search]').value.trim();
    const results=$('[data-pos-client-results]');results.replaceChildren();
    if(term.length<2){$('[data-pos-client-status]').textContent='Escribe al menos dos caracteres del nombre o DNI.';return;}
    clientController=new AbortController();$('[data-pos-client-status]').textContent='Buscando clientes…';
    try {
      const url=new URL(form.dataset.clientUrl);url.searchParams.set('term',term);
      const rows=await json(url,{signal:clientController.signal});if($('[data-pos-client-search]').value.trim()!==term)return;
      rows.forEach(row=>{
        const result=button('','pos-client-option',()=>selectClient(row.id_cliente));
        result.append(node('strong','',row.nombre_cliente),node('small','',row.documento_identidad || 'Sin documento'));results.append(result);
      });
      $('[data-pos-client-search]').setAttribute('aria-expanded',String(rows.length>0));
      $('[data-pos-client-status]').textContent=rows.length ? 'Selecciona el cliente de los resultados.' : 'No se encontraron clientes activos.';
    }catch(error){if(error.name!=='AbortError')$('[data-pos-client-status]').textContent=error.message;}
  }
  $('[data-pos-client-search]').addEventListener('input',()=>{
    if(selectedClient || pendingClient){clientRevision++;pendingClient=false;selectedClient=null;$('[name=id_cliente]').value='';$('[data-pos-client-card]').hidden=true;invalidate();}
    clearTimeout(clientTimer);clientController?.abort();$('[data-pos-client-results]').replaceChildren();
    clientTimer=setTimeout(searchClients,300);
  });
  $('[data-pos-client-search]').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();searchClients();}if(event.key==='Escape'){$('[data-pos-client-results]').replaceChildren();$('[data-pos-client-search]').setAttribute('aria-expanded','false');}});
  $('[data-pos-client-now]').addEventListener('click',searchClients);
  $('[data-pos-clear-client]').addEventListener('click',clearClient);
  for(const name of ['id_moneda','metodo_pago','descuento_manual','usar_saldo','observacion']) {
    const field=$(`[name=${name}]`);if(!field)continue;
    field.addEventListener(name==='descuento_manual'||name==='observacion'?'input':'change',()=>{
      invalidate();
      if(name==='id_moneda'){resetPage();if(selectedClient)selectClient(selectedClient);}
    });
  }
  $('[data-pos-quote]').addEventListener('click',calculate);
  form.addEventListener('submit',event=>{
    const method = $('[name=metodo_pago]')?.value || 'efectivo';
    const amount = toNumber($('[name=monto_recibido]')?.value || 0);
    const total = toNumber($('[name=accepted_total]').value || 0);
    if(!cart.length || pendingClient || !total){event.preventDefault();$('[data-pos-quote-status]').textContent='Espera el cálculo del total antes de finalizar.';return;}
    if((method === 'efectivo' || method === 'mixto') && amount < total){
      event.preventDefault();
      $('[data-pos-quote-status]').textContent = 'Ingresa el monto recibido para poder finalizar la venta en efectivo.';
      syncPaymentState();
    }
  });
  syncPaymentState();
  loadItems();
})();
