<?php
// scripts/replace_colors.php
// Uso: php scripts/replace_colors.php
// Reemplaza colores hex en bloques <style> y atributos style dentro de app/views
// Hace backup de cada archivo modificado con extensión .bak

$root = __DIR__ . '/../app/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$map = [
    // whites
    '/#ffffff\b/i' => 'var(--color-surface)',
    '/#fff\b/i' => 'var(--color-surface)',
    // blacks / text
    '/#000000\b/i' => 'var(--color-text)',
    '/#000\b/i' => 'var(--color-text)',
    // primary
    '/#1d4ed8\b/i' => 'var(--color-primary)',
    '/#0d6efd\b/i' => 'var(--color-primary)',
    '/#007bff\b/i' => 'var(--color-primary)',
    '/#0b5ed7\b/i' => 'var(--color-primary-600)',
    '/#3085d6\b/i' => 'var(--color-primary-600)',
    // muted / neutrals
    '/#6c757d\b/i' => 'var(--color-muted)',
    '/#495057\b/i' => 'var(--color-muted)',
    '/#343a40\b/i' => 'var(--color-muted)',
    '/#7f8c8d\b/i' => 'var(--color-muted)',
    '/#adb5bd\b/i' => 'var(--color-muted)',
    '/#94a3b8\b/i' => 'var(--color-muted)',
    // borders
    '/#dee2e6\b/i' => 'var(--color-border)',
    '/#e2e8f0\b/i' => 'var(--color-border)',
    '/#ddd\b/i' => 'var(--color-border)',
    '/#ccc\b/i' => 'var(--color-border)',
    '/#999\b/i' => 'var(--color-border)',
    // success / green
    '/#28a745\b/i' => 'var(--color-success)',
    '/#198754\b/i' => 'var(--color-success)',
    '/#4ade80\b/i' => 'var(--color-success)',
    // warning
    '/#ffc107\b/i' => 'var(--color-warning)',
    '/#ffb020\b/i' => 'var(--color-warning)',
    // danger
    '/#dc3545\b/i' => 'var(--color-danger)',
    '/#d33\b/i' => 'var(--color-danger)',
    // other accents
    '/#4e73df\b/i' => 'var(--color-primary)',
    '/#224abe\b/i' => 'var(--color-primary-600)',
    '/#6f42c1\b/i' => 'var(--color-yape)',
    '/#6610f2\b/i' => 'var(--color-primary-600)',
    // surfaces / light
    '/#f8fafc\b/i' => 'var(--color-bg)',
    '/#f8f9fa\b/i' => 'var(--color-surface-2)',
    '/#fafafa\b/i' => 'var(--color-surface-2)',
    '/#f8f9ff\b/i' => 'var(--color-surface-2)',
    '/#f1f3f7\b/i' => 'var(--color-surface-2)',
    '/#f5f5f5\b/i' => 'var(--color-surface-2)',
    '/#e9ecef\b/i' => 'var(--color-surface-2)',
    '/#f0f0f0\b/i' => 'var(--color-border)',
];

$report = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (stripos($path, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR) === false) continue;
    if (!preg_match('/\\.php$/i', $path)) continue;

    $content = file_get_contents($path);
    $original = $content;
    $changed = false;
    $fileReport = [];

    // 1) Replace inside <style>...</style> blocks
    $content = preg_replace_callback('/<style[^>]*>(.*?)<\\/style>/is', function($m) use ($map, &$fileReport, &$changed) {
        $block = $m[1];
        $before = $block;
        foreach ($map as $pat => $rep) {
            $count = 0;
            $block = preg_replace($pat, $rep, $block, -1, $count);
            if ($count) {
                $fileReport[] = "style-block: $count replacements for $pat -> $rep";
                $changed = true;
            }
        }
        return str_replace($m[1], $block, $m[0]);
    }, $content);

    // 2) Replace inside style="..." attributes
    $content = preg_replace_callback('/(style=)([\"\'])(.*?)(\2)/is', function($m) use ($map, &$fileReport, &$changed) {
        $val = $m[3];
        foreach ($map as $pat => $rep) {
            $count = 0;
            $val = preg_replace($pat, $rep, $val, -1, $count);
            if ($count) {
                $fileReport[] = "style-attr: $count replacements for $pat -> $rep";
                $changed = true;
            }
        }
        return $m[1] . $m[2] . $val . $m[2];
    }, $content);

    // 3) Replace occurrences within style-like blocks (e.g., <div style... already covered), and generic replacements outside but avoid touching JS strings by skipping inside <script>...</script>
    // We'll perform replacements on content excluding <script>...</script>
    $parts = preg_split('/(<script[^>]*>.*?<\\/script>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    for ($i=0;$i<count($parts);$i++) {
        if (preg_match('/^<script/i', $parts[$i])) continue; // skip script blocks
        foreach ($map as $pat => $rep) {
            $count = 0;
            $parts[$i] = preg_replace($pat, $rep, $parts[$i], -1, $count);
            if ($count) {
                $fileReport[] = "global: $count replacements for $pat -> $rep";
                $changed = true;
            }
        }
    }
    $content = implode('', $parts);

    // 4) Replace svg fill/stroke hex values with currentColor
    $beforeSvg = $content;
    $content = preg_replace('/(fill|stroke)\s*=\s*(["\'])#([0-9a-fA-F]{3,6})\2/i', '$1=$2currentColor$2', $content, -1, $svgCount);
    if ($svgCount) {
        $fileReport[] = "svg: $svgCount fill/stroke -> currentColor";
        $changed = true;
    }

    if ($changed && $content !== $original) {
        // backup
        copy($path, $path . '.bak');
        file_put_contents($path, $content);
        $report[$path] = $fileReport;
    }
}

// Write report
$reportPath = __DIR__ . '/../color-replace-report.txt';
$lines = [];
foreach ($report as $file => $items) {
    $lines[] = "File: $file";
    foreach ($items as $it) $lines[] = "  - $it";
    $lines[] = "";
}
if (empty($lines)) $lines[] = "No replacements made.";
file_put_contents($reportPath, implode("\n", $lines));

echo "Done. Report: $reportPath\n";
