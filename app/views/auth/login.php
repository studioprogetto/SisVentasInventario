<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Studio & Progetto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="imagenes/logosinfondo.png" type="image/x-icon">

    <style>
        :root {
            --primary: #1A2A44;
            --primary-dark: #0f1b2e;
            --secondary: #2C3E50;
            --accent: #3498DB;
            --light: #F8F9FA;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --success: #28a745;
            --border-radius: 16px;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f1b2e 0%, #1a2a44 50%, #2c3e50 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.22);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 40px 32px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,20 L100,100 L0,100 Z" fill="rgba(255,255,255,0.05)"/></svg>') no-repeat bottom;
            background-size: 100% 30px;
            pointer-events: none;
        }

        .logo-container {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .logo-container img {
            height: 90px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            transition: transform 0.3s ease;
        }

        .login-card:hover .logo-container img {
            transform: scale(1.05);
        }

        .login-header h1 {
            margin: 0 0 8px;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-header h4 {
            margin: 0;
            font-weight: 400;
            font-size: 0.95rem;
            opacity: 0.9;
            letter-spacing: 0.3px;
        }

        .login-body {
            padding: 36px 32px;
            background: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .input-group-text {
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-right: none;
            color: var(--gray-600);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
            padding: 0 14px;
        }

        .form-control {
            border: 1px solid var(--gray-200);
            border-left: none;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            padding: 14px 16px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.2);
            z-index: 10;
        }

        .form-control.with-icon {
            padding-left: 12px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: var(--border-radius);
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            color: white;
            width: 100%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-transform: none;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.6s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 42, 68, 0.3);
        }

        .btn-back {
            background: var(--gray-200);
            color: var(--gray-700);
            border: none;
            border-radius: var(--border-radius);
            padding: 12px;
            font-weight: 500;
            width: 100%;
            transition: var(--transition);
        }

        .btn-back:hover {
            background: var(--gray-300);
            color: var(--gray-800);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 14px 16px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 1.1rem;
        }

        .copyright {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .copyright a {
            color: var(--accent);
            text-decoration: none;
        }

        .copyright a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 15px;
            }
            .login-body {
                padding: 28px 24px;
            }
            .login-header {
                padding: 32px 24px;
            }
            .logo-container img {
                height: 70px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-container">
                    <img src="../img/logo.png" alt="Logo Studio & Progetto">
                </div>
                <h1>Studio & Progetto</h1>
                <h4>Sistema de Gestión de Ventas e Inventario</h4>
            </div>

            <!-- Body -->
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>auth/login" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i> Usuario
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" 
                                   class="form-control with-icon" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Nombre de usuario" 
                                   required 
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" 
                                   class="form-control with-icon" 
                                   id="password" 
                                   name="password" 
                                   placeholder="••••••••" 
                                   required 
                                   autocomplete="current-password">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Ingresar al Sistema
                        </button>
                    </div>

                    <div class="d-grid">
                        <a href="http://localhost:8080/" class="btn btn-back">
                            <i class="fas fa-arrow-left me-2"></i>
                            Regresar al Inicio
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="copyright">
            © 2025 <strong>Coconan Enterprise</strong> – Studio & Progetto<br>
            <small>Todos los derechos reservados.</small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Efecto de foco suave
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>