from pathlib import Path
import sys,json
root=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(root/'tools/python'))
from playwright.sync_api import sync_playwright
results=[]
errors=[]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='C:/Program Files/Google/Chrome/Application/chrome.exe',headless=True)
    page=browser.new_page()
    page.on('pageerror',lambda error:errors.append(str(error)))
    for width in [320,375,640,768,1280]:
        page.set_viewport_size({'width':width,'height':900})
        response=page.goto('http://localhost/ProyectoWeb/index.php',wait_until='domcontentloaded')
        page.wait_for_timeout(1500)
        overflow=page.evaluate('document.documentElement.scrollWidth > innerWidth + 1')
        results.append({'width':width,'status':response.status,'overflow':overflow})
        if overflow or response.status!=200:errors.append(str(results[-1]))
        page.screenshot(path=str(root/f'docs/validation/public-home-{width}.png'),full_page=True)
    browser.close()
(root/'docs/validation/public-home.json').write_text(json.dumps({'results':results,'errors':errors},indent=2),encoding='utf-8')
print(json.dumps({'checks':len(results),'errors':errors}))
raise SystemExit(bool(errors))
