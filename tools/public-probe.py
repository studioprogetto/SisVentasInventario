from pathlib import Path
import sys,json
root=Path(__file__).resolve().parents[1];sys.path.insert(0,str(root/'tools/python'))
from playwright.sync_api import sync_playwright
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='C:/Program Files/Google/Chrome/Application/chrome.exe',headless=True);page=b.new_page(viewport={'width':320,'height':900});page.goto('http://localhost/ProyectoWeb/index.php',wait_until='domcontentloaded');page.wait_for_timeout(1000)
 print(json.dumps(page.evaluate('''() => ({width:innerWidth,scroll:document.documentElement.scrollWidth,items:[...document.querySelectorAll('body *')].map(e=>({e,r:e.getBoundingClientRect()})).filter(({e,r})=>r.width>0 && r.right>innerWidth+1 && !e.closest('.cards,.slide-track,.slider-track')).slice(0,20).map(({e,r})=>({tag:e.tagName,id:e.id,cls:String(e.className),left:r.left,right:r.right,width:r.width}))})''')))
 b.close()
