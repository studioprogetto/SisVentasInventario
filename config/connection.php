<?php

// Compatibility boundary for the current MVC while Laravel is migrated.
// Infrastructure comes from environment or an ignored local file, not source.
$localFile = __DIR__.'/local.php';
$local = is_file($localFile) ? require $localFile : [];
$settings = [];
foreach (['HOST', 'USER', 'PASS', 'NAME'] as $key) {
    $value = getenv('STUDIO_DB_'.$key);
    $settings[$key] = $value !== false ? $value : ($local[$key] ?? '');
}

return $settings;
