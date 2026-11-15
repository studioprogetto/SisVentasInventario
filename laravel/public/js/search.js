(() => {
    const fold = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es');
    window.SearchUI = {
        matches(text, query) {
            const source = fold(text);
            return fold(query).trim().split(/\s+/).every(word => source.includes(word));
        }
    };

    // A dictated scale often arrives as “uno a cincuenta” or “1 dos puntos 50”.
    const numbers = {uno:1, un:1, diez:10, veinte:20, veinticinco:25, treinta:30,
        cuarenta:40, cincuenta:50, setenta:70, 'setenta y cinco':75, cien:100, doscientos:200};
    function interpret(text) {
        return text.trim().replace(/[.,;!?]+$/u,'').replace(/\b(uno|un|1)\s*(?:dos puntos|a|:)\s*(setenta y cinco|veinticinco|doscientos|cincuenta|cuarenta|treinta|setenta|veinte|diez|cien|\d+)\b/gi,
            (_,one,denominator) => '1:' + (numbers[denominator.toLowerCase()] ?? denominator));
    }
    const selector = 'input[type=search],input[name=q],#cat-search,.filters input[name=cliente],.filters input[name=usuario]';
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let session = null;
    const status = document.createElement('div');
    status.className = 'voice-status';
    status.setAttribute('role','status');
    document.body.append(status);
    let statusTimer;
    function announce(message) {
        status.textContent = message;
        clearTimeout(statusTimer);
        statusTimer = setTimeout(() => status.textContent = '',5500);
    }
    function decorate(root) {
        const inputs = root.matches?.(selector) ? [root] : root.querySelectorAll(selector);
        for (const input of inputs) {
            if (input.parentElement.classList.contains('voice-entry')) continue;
            const wrap = document.createElement('span');
            wrap.className = 'voice-entry';
            input.before(wrap);
            wrap.append(input);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'voice-trigger';
            button.setAttribute('aria-label','Dictar búsqueda');
            button.title = 'Dictar búsqueda';
            button.innerHTML = '<svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="2" width="6" height="13" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v4m-4 0h8"/></svg>';
            wrap.append(button);
        }
    }
    decorate(document.body);
    new MutationObserver(changes => {
        for (const change of changes) for (const node of change.addedNodes)
            if (node.nodeType === 1) decorate(node);
    }).observe(document.body,{childList:true,subtree:true});

    document.addEventListener('click',event => {
        const button = event.target.closest('.voice-trigger');
        if (!button) return;
        const input = button.previousElementSibling;
        input.focus();
        if (!Recognition || !window.isSecureContext) {
            announce('Usa el micrófono del teclado para dictar en este campo, o escribe tu búsqueda.');
            return;
        }
        if (session) { session.abort(); return; }
        const mic = new Recognition();
        mic.lang = 'es-PE';
        mic.interimResults = false;
        mic.onresult = event => {
            input.value = interpret(event.results[0][0].transcript);
            input.dispatchEvent(new Event('input',{bubbles:true}));
            announce('Buscando: ' + input.value);
        };
        mic.onerror = event => announce(event.error === 'not-allowed' ? 'Permite el micrófono en el navegador o usa el dictado del teclado.' : 'No se pudo reconocer la voz. Puedes volver a intentar o escribir.');
        mic.onend = () => { session = null; button.setAttribute('aria-pressed','false'); };
        try { mic.start(); session = mic; button.setAttribute('aria-pressed','true'); announce('Escuchando…'); }
        catch { announce('El micrófono no está disponible.'); }
    });

    const region = document.querySelector('[data-search-results]');
    if (!region) return;
    let timer;
    let request;
    document.addEventListener('input',event => {
        const input = event.target;
        if (!input.matches(selector) || !input.form || input.form.method.toLowerCase() !== 'get') return;
        clearTimeout(timer);
        request?.abort();
        const form = input.form;
        timer = setTimeout(async () => {
            const url = new URL(form.action || location.href);
            url.search = new URLSearchParams(new FormData(form));
            const controller = new AbortController();
            request = controller;
            region.setAttribute('aria-busy','true');
            try {
                const response = await fetch(url,{signal:controller.signal});
                if (!response.ok) throw new Error();
                const html = new DOMParser().parseFromString(await response.text(),'text/html');
                const next = html.querySelector('[data-search-results]');
                if (!next) throw new Error();
                if (controller.signal.aborted) return;
                region.replaceChildren(...next.childNodes);
                history.replaceState(null,'',url);
                document.dispatchEvent(new Event('search:updated'));
            } catch {
                if (!controller.signal.aborted) announce('No se pudo actualizar. Pulsa Enter para volver a buscar.');
            } finally {
                if (request === controller) region.removeAttribute('aria-busy');
            }
        },300);
    });
})();
