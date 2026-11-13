from pathlib import Path
import sys
import json

root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(root/'tools/python'))
from playwright.sync_api import sync_playwright, expect
from openpyxl import load_workbook
from pypdf import PdfReader

output = root/'docs/validation'
settings = json.loads((output/'test-server.json').read_text())
results = []
errors = []
paths = ['home','venta','venta/historial','producto','servicio','categoria','almacen','transferencia','compra','proveedor','cliente','caja','reporte','usuario','tipocambio','tipomoneda']
with sync_playwright() as playwright:
    browser = playwright.chromium.launch(executable_path='C:/Program Files/Google/Chrome/Application/chrome.exe', headless=True)
    context = browser.new_context(viewport={'width':1280,'height':900})
    page = context.new_page()
    page.on('pageerror', lambda exception: errors.append(str(exception)))
    page.goto(settings['url']+'/auth/login')
    page.get_by_label('Usuario', exact=True).fill(settings['username'])
    page.get_by_label('Contraseña', exact=True).fill(settings['password'])
    page.get_by_role('button',name='Ingresar',exact=True).click()
    page.wait_for_url('**/home')
    for width in [320,375,640,768,1280]:
        page.set_viewport_size({'width':width,'height':900})
        for path in paths+['catalogo']:
            response=page.goto(settings['url']+'/'+path)
            page.wait_for_load_state('networkidle')
            overflow=page.evaluate('document.documentElement.scrollWidth > innerWidth + 1')
            result={'path':path,'width':width,'status':response.status,'horizontal_overflow':overflow}
            results.append(result)
            if response.status!=200 or overflow:
                errors.append(str(result))
            directory=output/'responsive'/str(width)
            directory.mkdir(parents=True,exist_ok=True)
            page.screenshot(path=str(directory/(path.replace('/','-')+'.png')),full_page=True)
        if width==320:
            page.goto(settings['url']+'/home')
            page.get_by_role('button',name='Menú',exact=True).click()
            if page.locator('#nav-toggle').get_attribute('aria-expanded')!='true': errors.append('Mobile menu did not open')
            page.keyboard.press('Escape')
            if page.locator('#nav-toggle').get_attribute('aria-expanded')!='false': errors.append('Escape did not close menu')
    page.set_viewport_size({'width':1280,'height':900})
    page.goto(settings['url']+'/venta')
    page.locator('[data-pos-search]').fill('Producto')
    page.get_by_role('button',name='Buscar productos',exact=True).click()
    page.locator('.pos-add').first.click()
    page.locator('input[name="carrito[0][cantidad]"]').fill('2')
    page.get_by_role('button',name='Actualizar total',exact=True).click()
    expect(page.locator('[data-confirm-sale]')).to_be_enabled(timeout=10000)
    page.get_by_role('button',name='Finalizar venta',exact=True).click()
    page.wait_for_load_state('networkidle')
    if not page.get_by_role('status').filter(has_text='Operación registrada').count(): errors.append('POS browser sale did not succeed')
    page.screenshot(path=str(output/'pos-success.png'),full_page=True)
    browser.close()

book=load_workbook(output/'reporte-prueba.xlsx',read_only=True,data_only=True)
rows=list(book.active.values)
if len(rows)!=2 or 'operacion' not in rows[0]: errors.append('Workbook rows/header mismatch')
if len(rows)==2 and rows[1][rows[0].index('importe')]!=50:errors.append('Workbook amount must be numeric and accurate')
pdf=PdfReader(output/'reporte-prueba.pdf')
if not pdf.pages or 'STUDIO & PROGETTO' not in pdf.pages[0].extract_text(): errors.append('PDF content mismatch')
try:
    import pymupdf
    document=pymupdf.open(output/'reporte-prueba.pdf')
    document[0].get_pixmap(matrix=pymupdf.Matrix(1.5,1.5)).save(output/'reporte-prueba.png')
except ImportError:
    errors.append('PDF renderer unavailable')
(output/'browser-results.json').write_text(json.dumps({'responsive':results,'errors':errors,'xlsx_rows':len(rows),'pdf_pages':len(pdf.pages)},indent=2),encoding='utf-8')
print(json.dumps({'responsive_checks':len(results),'errors':errors,'xlsx_rows':len(rows),'pdf_pages':len(pdf.pages)},ensure_ascii=False))
raise SystemExit(bool(errors))
