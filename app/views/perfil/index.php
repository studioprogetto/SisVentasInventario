<div class="card">
    <div class="card-header">
        <h4 class="text-primary"><i class="fas fa-user-edit"></i> Mi Perfil</h4>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>perfil/guardar" method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['username']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="moneda" class="form-label">Moneda Preferida</label>
                <select name="moneda" id="moneda" class="form-select">
                    <option value="$" <?php echo ($usuario['moneda'] == '$') ? 'selected' : ''; ?>>Dólar ($)</option>
                    <option value="€" <?php echo ($usuario['moneda'] == '€') ? 'selected' : ''; ?>>Euro (€)</option>
                    <option value="S/" <?php echo ($usuario['moneda'] == 'S/') ? 'selected' : ''; ?>>Soles (S/)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>

        <hr>

        <h4 class="mt-4">Cambiar Logo del Sistema</h4>
        <div class="mb-3">
            <label>Logo Actual:</label><br>
            <?php 
                $logoPath = __DIR__ . '/../../../public/img/logo.png';
                $logoUrl = '<?php echo BASE_URL; ?>img/logo.png?v=' . time(); 
                if (file_exists($logoPath)):
            ?>
            <img src="<?php echo $logoUrl; ?>" alt="Logo Actual" class="profile-logo">
            <?php else: ?>
            <p class="text-muted">No se ha subido un logo.</p>
            <?php endif; ?>
        </div>

        <form action="<?php echo BASE_URL; ?>perfil/subirLogo" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="logo" class="form-label">Subir Nuevo Logo (formato PNG, tamaño recomendado: 150x40px)</label>
                <input class="form-control" type="file" name="logo" id="logo" accept="image/png" required>
            </div>
            <button type="submit" class="btn btn-info text-white">Actualizar Logo</button>
        </form>
    </div>
</div>