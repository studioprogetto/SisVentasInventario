from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
site=root.parent
p=site/'index.php'
source=p.read_text(encoding='utf-8')
start=source.rindex('<script>')
end=source.index('</script>',start)+len('</script>')
code=source[start+8:end-len('</script>')]
class_start=code.index('class InfiniteSlider')
initialization=code.index('// INICIALIZACIÓN MEJORADA')
slider=code[class_start:initialization]
behavior=code[initialization:]
preloader=behavior.index('// PRELOADER')
behavior=behavior[:preloader]+'''// Release the preloader even if an external resource fails.
const releasePreloader=()=>['preloader','overlayer'].forEach(id=>{const element=document.getElementById(id);if(element)element.style.display='none';});
window.addEventListener('load',releasePreloader,{once:true});
setTimeout(releasePreloader,3000);
'''
behavior=behavior.replace('productos.php?search=','productos.php?q=')
behavior=behavior.replace("alert('No se pueden realizar transacciones. Puedes seguir navegando como visitante.');","alert('Puedes seguir consultando el catálogo como visitante.');")
(site/'js/infinite-slider.js').write_text(slider,encoding='utf-8')
(site/'js/home-behavior.js').write_text(behavior,encoding='utf-8')
source=source[:start]+'<script src="js/infinite-slider.js"></script>\n<script src="js/home-behavior.js"></script>'+source[end:]
source=source.replace('<script src="js/main.js"></script>','<script src="js/home-ui.js"></script>')
source=source.replace('<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>','')
source=source.replace('<link rel="stylesheet" href="css/main.css">','<link rel="stylesheet" href="css/main.css">\n<link rel="stylesheet" href="css/home-fixes.css">')
p.write_text(source,encoding='utf-8')
print('Homepage scripts separated; no global catalog handlers, jQuery dependency or indefinite preloader.')
