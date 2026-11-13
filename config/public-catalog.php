<?php

// Infrastructure only; published product/category content remains in the database.
$adminPath = getenv('STUDIO_ADMIN_BASEPATH') ?: '/ProyectoWeb/mi_sistema_mvc/laravel/public';

return ['admin_path' => $adminPath, 'path' => $adminPath.'/catalogo', 'image_path' => $adminPath.'/catalogo/imagenes/', 'site_url' => getenv('STUDIO_PUBLIC_SITE_URL') ?: 'http://localhost/ProyectoWeb'];
