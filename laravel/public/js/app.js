(() => {
  'use strict';

  const toggle = document.getElementById('nav-toggle');
  const sidebar = document.getElementById('main-nav');
  const backdrop = document.querySelector('[data-close-nav]');
  const mobile = window.matchMedia('(max-width: 1023px)');
  const desktop = window.matchMedia('(min-width: 1024px)');
  const collapsedStorageKey = 'studio-sidebar-collapsed';

  const closeMobileNav = () => {
    sidebar?.classList.remove('is-open');
    document.body.classList.remove('nav-open');
    toggle?.setAttribute('aria-expanded', 'false');
    backdrop?.setAttribute('aria-hidden', 'true');
    if (sidebar) sidebar.inert = true;
  };

  const openMobileNav = () => {
    sidebar?.classList.add('is-open');
    document.body.classList.add('nav-open');
    toggle?.setAttribute('aria-expanded', 'true');
    backdrop?.setAttribute('aria-hidden', 'false');
    if (sidebar) sidebar.inert = false;
  };

  const syncNavigation = () => {
    if (!sidebar) return;
    const isMobileMenuOpen = mobile.matches && toggle?.getAttribute('aria-expanded') === 'true';
    sidebar.inert = mobile.matches && !isMobileMenuOpen;
  };

  const setDesktopCollapsed = collapsed => {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    toggle?.setAttribute('aria-expanded', String(!collapsed));
    toggle?.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
    try { localStorage.setItem(collapsedStorageKey, String(collapsed)); } catch (_) {}
  };

  const syncResponsiveLayout = () => {
    if (mobile.matches) {
      document.body.classList.remove('sidebar-collapsed');
      if (toggle) toggle.setAttribute('aria-label', 'Abrir menú');
      closeMobileNav();
      return;
    }

    const savedState = localStorage.getItem(collapsedStorageKey) === 'true';
    setDesktopCollapsed(savedState);
    if (backdrop) backdrop.setAttribute('aria-hidden', 'true');
  };

  mobile.addEventListener('change', syncResponsiveLayout);
  desktop.addEventListener('change', syncResponsiveLayout);

  toggle?.addEventListener('click', () => {
    if (mobile.matches) {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      if (expanded) closeMobileNav(); else openMobileNav();
      syncNavigation();
      return;
    }

    const collapsed = document.body.classList.contains('sidebar-collapsed');
    setDesktopCollapsed(!collapsed);
  });

  backdrop?.addEventListener('click', closeMobileNav);

  sidebar?.querySelectorAll('a[href]').forEach(link => {
    link.addEventListener('click', () => {
      if (mobile.matches) closeMobileNav();
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Tab' && mobile.matches && toggle?.getAttribute('aria-expanded') === 'true' && sidebar) {
      const links = [...sidebar.querySelectorAll('a[href]')];
      const first = links[0];
      const last = links[links.length - 1];
      if (!sidebar.contains(document.activeElement) || (!event.shiftKey && document.activeElement === last)) {
        event.preventDefault(); first?.focus();
      } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last?.focus();
      }
    }

    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
      closeMobileNav();
      toggle.focus();
    }
  });
  const saleToast = document.querySelector('[data-sale-toast]');
  if (saleToast) {
    const hideToast = () => saleToast.classList.remove('is-visible');
    const openTicket = () => {
      const url = saleToast.dataset.ticketUrl;
      if (url) window.location.href = url;
    };
    setTimeout(() => saleToast.classList.add('is-visible'), 80);
    setTimeout(hideToast, 3000);
    saleToast.querySelector('[data-open-ticket]')?.addEventListener('click', openTicket);
    saleToast.querySelector('[data-close-sale-toast]')?.addEventListener('click', hideToast);
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') hideToast();
    });
  }
  document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  const timers = new WeakMap();
  const invalidate=form=>{if(form?.matches('[data-pos]'))return;const button=form?.querySelector('[data-confirm-sale]');if(button){button.disabled=true;form.dataset.revision=String(Number(form.dataset.revision||0)+1);form.querySelector('[name=accepted_total]').value='';}};
  document.addEventListener('input',event=>invalidate(event.target.form));
  document.addEventListener('change',event=>invalidate(event.target.form));
  document.querySelectorAll('[data-quote]').forEach(button=>button.addEventListener('click',async()=>{
    const form=button.form;const output=form.querySelector('[data-total]');const revision=form.dataset.revision||'0';
    button.disabled=true;
    try{
      const response=await fetch(form.dataset.quoteUrl,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'},credentials:'same-origin'});
      const data=await response.json();
      if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||'No se pudo calcular el total.');
      if((form.dataset.revision||'0')!==revision)throw new Error('Los datos cambiaron. Calcula nuevamente el total.');
      output.textContent=`${data.moneda_codigo}: subtotal ${data.subtotal} · descuento por sellos ${data.descuento_sellos} · descuento manual ${data.descuento_manual} · total ${data.total} · saldo aplicado ${data.saldo_aplicable} · por cobrar ${data.por_cobrar}`;
      form.querySelector('[data-confirm-sale]').disabled=false;
      form.querySelector('[name=accepted_total]').value=data.total;
    }catch(error){output.textContent=error.message;}finally{button.disabled=false;}
  }));
  document.addEventListener('input', event => {
    const input = event.target.closest('[data-item-search]');
    if (!input) return;
    clearTimeout(timers.get(input));
    const line = input.closest('[data-line]');
    const select = line.querySelector('[data-item-result]');
    line.querySelector('[data-item-id]').value = '';
    const term = input.value.trim();
    select.replaceChildren(new Option('Selecciona un resultado', ''));
    if (term.length < 2) return;
    timers.set(input, setTimeout(async () => {
      const url = new URL(input.form.dataset.itemUrl);
      url.searchParams.set('term', term);
      const category=input.form.querySelector('[data-category-filter]')?.value;if(category)url.searchParams.set('categoria',category);
      try {
        const response = await fetch(url, {headers:{Accept:'application/json'}, credentials:'same-origin'});
        if (!response.ok) throw new Error('No se pudo consultar el catálogo.');
        const items = await response.json();
        if (input.value.trim() !== term) return;
        select.replaceChildren(new Option('Selecciona un resultado', ''));
        for (const item of items) {
          if (input.form.hasAttribute('data-products-only') && item.tipo !== 'producto') continue;
          const option = new Option(`${item.nombre} · ${item.tipo}${item.stock !== undefined ? ' · disponible: '+item.stock : ''}`, `${item.tipo}:${item.id}`);
          option.item = item;
          select.add(option);
        }
        line.querySelector('[data-price]').textContent = select.options.length > 1 ? 'Selecciona el producto encontrado.' : 'No hay coincidencias.';
      } catch (error) { line.querySelector('[data-price]').textContent = error.message; }
    }, 250));
  });
  document.querySelectorAll('[data-client-search]').forEach(input=>input.addEventListener('input',()=>{
    clearTimeout(timers.get(input));const term=input.value.trim();if(term.length<2)return;
    timers.set(input,setTimeout(async()=>{
      const url=new URL(input.form.dataset.clientUrl);url.searchParams.set('term',term);
      try{const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok)return;const rows=await response.json();if(input.value.trim()!==term)return;const select=input.form.querySelector('[name=id_cliente]');select.replaceChildren(new Option('Venta sin cliente',''));rows.forEach(row=>select.add(new Option(row.nombre_cliente,row.id_cliente)));}
      catch{input.setCustomValidity('No se pudo buscar el cliente. Intenta nuevamente.');return;}
      input.setCustomValidity('');
    },250));
  }));
  document.addEventListener('change', event => {
    const select = event.target.closest('[data-item-result]');
    if (!select) return;
    const line = select.closest('[data-line]');
    const item = select.selectedOptions[0]?.item;
    line.querySelector('[data-item-id]').value = item?.id || '';
    if (line.querySelector('[data-item-type]')) line.querySelector('[data-item-type]').value = item?.tipo || 'producto';
    if (item?.id_almacen && line.querySelector('[data-warehouse]')) line.querySelector('[data-warehouse]').value = item.id_almacen;
    line.querySelector('[data-price]').textContent = item ? `Precio de catálogo en moneda principal: ${Number(item.precio_venta).toFixed(2)}` : '';
  });
  document.addEventListener('click', event => {
    const add = event.target.closest('[data-add-line]');
    const remove = event.target.closest('[data-remove-line]');
    if (!add && !remove) return;
    const form = (add || remove).closest('form');
    const container = form.querySelector('[data-lines]');
    invalidate(form);
    if (add) {
      const clone = container.querySelector('[data-line]').cloneNode(true);
      clone.querySelectorAll('input').forEach(input => { input.value = input.type === 'number' ? '1' : (input.hasAttribute('data-item-type') ? 'producto' : ''); });
      clone.querySelector('[data-item-result]').replaceChildren(new Option('Selecciona un resultado', ''));
      clone.querySelector('[data-price]').textContent = '';
      container.append(clone);
      clone.querySelector('input').focus();
    } else if (container.children.length > 1) remove.closest('[data-line]').remove();
    for (const [index,line] of [...container.children].entries()) {
      line.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/\[\d+\]/,`[${index}]`); });
    }
  });
  document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
    if (form.checkValidity()) form.querySelectorAll('button[type=submit],button:not([type])').forEach(button => { button.disabled = true; });
  }));
})();
