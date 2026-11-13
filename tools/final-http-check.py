from urllib.request import urlopen
from urllib.error import HTTPError
from pathlib import Path
import json

base = 'http://localhost/ProyectoWeb/'
checks = {
    '': 200,
    'mi_sistema_mvc/laravel/public/auth/login': 200,
    'mi_sistema_mvc/laravel/public/catalogo': 200,
    'mi_sistema_mvc/laravel/public/api/catalogo/categorias': 200,
    'mi_sistema_mvc/laravel/public/sitemap.xml': 200,
    'robots.txt': 200,
    'mi_sistema_mvc/config/database.php': 403,
    'mi_sistema_mvc/laravel/.env': 403,
    'mi_sistema_mvc/docs/INFORME_FINAL.md': 403,
    'mi_sistema_mvc/backups/': 403,
    'mi_sistema_mvc/.git/config': [403, 404],
}
results = []
for path, expected in checks.items():
    try:
        with urlopen(base+path, timeout=20) as response:
            status = response.status
    except HTTPError as error:
        status = error.code
    results.append({'path': path, 'status': status, 'expected': expected})
def matches(item):
    expected = item['expected']
    return item['status'] in expected if isinstance(expected, list) else item['status'] == expected

passed = all(matches(item) for item in results)
(Path(__file__).resolve().parents[1]/'docs/validation/http.json').write_text(json.dumps({'passed': passed, 'checks': results}, indent=2), encoding='utf-8')
print(json.dumps({'passed': passed, 'checks': len(results), 'failures': [item for item in results if not matches(item)]}))
raise SystemExit(0 if passed else 1)
