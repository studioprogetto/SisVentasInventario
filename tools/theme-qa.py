from pathlib import Path
import sys
import json

root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(root/'tools/python'))
from playwright.sync_api import sync_playwright, expect

settings = json.loads((root/'docs/validation/test-server.json').read_text())
output = root/'docs/validation/redesign'
output.mkdir(parents=True, exist_ok=True)
errors = []
checks = []
paths = ['home','venta','venta/historial','producto','servicio','categoria','almacen','transferencia','compra','proveedor','cliente','caja','reporte','usuario','tipocambio','tipomoneda','catalogo']
with sync_playwright() as p:
    browser = p.chromium.launch(executable_path='C:/Program Files/Google/Chrome/Application/chrome.exe', headless=True)
    context = browser.new_context(viewport={'width':1440,'height':1000}, color_scheme='light')
    page = context.new_page()
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(settings['url']+'/auth/login')
    expect(page.locator('html')).to_have_attribute('data-theme','light')
    page.get_by_role('button',name='Activar modo oscuro').click()
    page.reload()
    expect(page.locator('html')).to_have_attribute('data-theme','dark')
    page.screenshot(path=str(output/'login-dark.png'))
    page.get_by_label('Usuario',exact=True).fill(settings['username'])
    page.get_by_label('Contraseña',exact=True).fill(settings['password'])
    page.get_by_role('button',name='Ingresar',exact=True).click()
    page.wait_for_url('**/home')
    for theme in ['light','dark']:
        current = page.locator('html').get_attribute('data-theme')
        if current != theme:
            page.locator('[data-theme-toggle]').click()
        for width in [320,375,640,768,1440]:
            page.set_viewport_size({'width':width,'height':1000})
            for path in paths:
                response = page.goto(settings['url']+'/'+path)
                overflow = page.evaluate('document.documentElement.scrollWidth > innerWidth + 1')
                actual = page.locator('html').get_attribute('data-theme')
                item = {'theme':theme,'width':width,'path':path,'status':response.status,'overflow':overflow}
                checks.append(item)
                if response.status != 200 or overflow or actual != theme:
                    errors.append(item)
                if path in ['home','producto','venta'] and width in [375,1440]:
                    page.screenshot(path=str(output/(path+'-'+theme+'-'+str(width)+'.png')),full_page=True)
            if width == 320:
                page.goto(settings['url']+'/home')
                page.get_by_role('button',name='Menú',exact=True).click()
                expect(page.locator('#main-nav')).to_be_in_viewport()
                page.keyboard.press('Escape')
                expect(page.locator('#nav-toggle')).to_have_attribute('aria-expanded','false')
                expect(page.locator('#nav-toggle')).to_be_focused()
    browser.close()
(output/'results.json').write_text(json.dumps({'checks':checks,'errors':errors},indent=2),encoding='utf-8')
print(json.dumps({'checks':len(checks),'errors':errors}))
raise SystemExit(bool(errors))
