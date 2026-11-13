from pathlib import Path
import json
import sys
import time

root=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(root/'tools/python'))
from playwright.sync_api import sync_playwright, expect

settings=json.loads((root/'docs/validation/test-server.json').read_text())
output=root/'docs/validation/pos'
output.mkdir(parents=True,exist_ok=True)
errors=[]
requests=[]
checks=[]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='C:/Program Files/Google/Chrome/Application/chrome.exe',headless=True)
    page=browser.new_page(viewport={'width':1800,'height':1100},color_scheme='light')
    page.on('pageerror',lambda e:errors.append(str(e)))
    page.on('request',lambda r: requests.append((time.monotonic(),r.url)) if '/pos/items?' in r.url else None)
    page.goto(settings['url']+'/auth/login')
    page.get_by_label('Usuario',exact=True).fill(settings['username'])
    page.get_by_label('Contraseña',exact=True).fill(settings['password'])
    page.get_by_role('button',name='Ingresar',exact=True).click()
    page.wait_for_url('**/home')
    page.goto(settings['url']+'/venta')
    expect(page.locator('.pos-add').first).to_be_visible()
    page.screenshot(path=str(output/'desktop-empty-light.png'),full_page=True)
    # Debounce: no request before 300 ms; one request after the last typed term.
    count=len(requests)
    started=time.monotonic()
    page.locator('[data-pos-search]').fill('Producto')
    page.wait_for_timeout(100)
    assert len(requests)==count,'Search sent before 300 ms'
    page.locator('[data-pos-search]').fill('Producto de prueba')
    typed=time.monotonic()
    with page.expect_response(lambda r:'/pos/items?' in r.url):
        page.wait_for_timeout(500)
    search_requests=requests[count:]
    assert len(search_requests)==1,'Debounced search must issue only one request'
    assert search_requests[0][0]-typed>=0.28,'Debounce delay too short'
    checks.append({'debounce_ms':round((search_requests[0][0]-typed)*1000),'requests':len(search_requests)})
    expect(page.locator('.pos-product h3').first).to_have_text('Producto de prueba')
    assert page.locator('.pos-product img').first.evaluate('(img)=>img.complete && img.naturalWidth>0')
    page.get_by_role('button',name='Destino de prueba',exact=True).click()
    expect(page.locator('[data-pos-products]')).to_contain_text('No hay resultados')
    page.get_by_role('button',name='Todos',exact=True).click()
    expect(page.locator('.pos-add').first).to_be_visible()
    page.locator('.pos-add').first.click()
    page.locator('input[name="carrito[0][cantidad]"]').fill('2')
    expect(page.locator('[data-confirm-sale]')).to_be_enabled(timeout=10000)
    expect(page.locator('[data-pos-total]')).to_have_text('PEN 50.00')
    page.locator('[data-pos-client-search]').fill('72709806')
    page.get_by_role('button',name='Buscar clientes',exact=True).click()
    page.locator('.pos-client-option').first.click()
    expect(page.locator('[data-pos-client-card]')).to_be_visible()
    expect(page.locator('[data-loyalty-stars] span')).to_have_count(12)
    expect(page.locator('[data-client-name]')).to_have_text('Cliente de prueba')
    expect(page.locator('[data-loyalty-stars] .is-earned')).to_have_count(5)
    expect(page.locator('[data-pos-total]')).to_have_text('PEN 47.50',timeout=10000)
    expect(page.locator('[data-confirm-sale]')).to_be_enabled(timeout=10000)
    for theme in ['light','dark']:
        if page.locator('html').get_attribute('data-theme')!=theme:page.locator('[data-theme-toggle]').click()
        for width in [320,375,768,1280,1800]:
            page.set_viewport_size({'width':width,'height':1000})
            assert not page.evaluate('document.documentElement.scrollWidth>innerWidth+1'),f'Overflow {theme} {width}'
            page.screenshot(path=str(output/(theme+'-'+str(width)+'.png')),full_page=True)
            checks.append({'theme':theme,'width':width,'overflow':False})
    page.get_by_role('button',name='Quitar cliente',exact=True).click()
    expect(page.locator('[data-pos-client-card]')).to_be_hidden()
    expect(page.locator('[name=id_cliente]')).to_have_value('')
    expect(page.locator('[data-confirm-sale]')).to_be_enabled(timeout=10000)
    page.get_by_role('button',name='Finalizar venta',exact=True).click()
    expect(page.get_by_role('status').filter(has_text='Operación registrada')).to_be_visible()
    assert not errors,errors
    browser.close()
(output/'results.json').write_text(json.dumps({'checks':checks,'errors':errors,'sale':True,'client_card':True,'warehouse_filter':True},indent=2))
print(json.dumps({'checks':len(checks),'errors':errors,'sale':True,'client_card':True,'warehouse_filter':True}))
