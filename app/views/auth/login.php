<?php
/**
 * ============================================================
 * STUDIO & PROGETTO
 * Sistema de Gestión de Ventas e Inventario
 * Pantalla de inicio de sesión
 * ============================================================
 *
 * Se mantiene la integración con:
 * - BASE_URL
 * - $error
 * - POST auth/login
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Sistema de Gestión de Ventas e Inventario - Studio & Progetto"
    >

    <meta
        name="theme-color"
        content="#1d4ed8"
    >

    <title>Iniciar sesión | Studio & Progetto</title>

    <!-- Inicializar preferencia de tema antes de cargar estilos para evitar parpadeo -->
    <script>
        (function(){
            try {
                var t = localStorage.getItem('theme');
                if(t){ document.documentElement.setAttribute('data-theme', t); }
                else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches){ document.documentElement.setAttribute('data-theme','dark'); }
            } catch(e) {}
        })();
    </script>

    <!-- Cargar sistema de diseño global -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css?v=<?php echo time(); ?>">

    <link
        rel="icon"
        href="../img/logo.png"
        type="image/png"
    >

    <!-- ======================================================
         TIPOGRAFÍA
    ======================================================= -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        /* ======================================================
           VARIABLES CORPORATIVAS
        ======================================================= */

        :root {
            /* Mapear variables locales del login a tokens globales (fallbacks conservadores) */
            --primary: var(--color-primary, #1d4ed8);
            --primary-hover: var(--color-primary-600, #1e40af);
            --primary-soft: color-mix(in srgb, var(--color-primary) 8%, white);

            --navy: var(--color-text, #0f1724);
            --text: var(--color-text, #172033);
            --text-secondary: var(--color-muted, #64748b);
            --text-muted: var(--color-muted, #94a3b8);

            --background: var(--color-bg, #f8fafc);
            --white: var(--color-surface, #ffffff);

            --border: var(--color-border, #e2e8f0);
            --border-focus: var(--color-primary-600, #93c5fd);

            --danger: var(--color-danger, #dc2626);
            --danger-bg: color-mix(in srgb, var(--color-danger) 6%, white);
            --danger-border: color-mix(in srgb, var(--color-danger) 12%, white);

            --success: var(--color-success, #16a34a);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;

            --shadow-card: 0 20px 50px rgba(15, 23, 42, 0.06);
            --shadow-button: 0 8px 20px rgba(13, 110, 253, 0.14);

            --transition: 180ms ease;

            /* Imagen del panel derecho (fallback local) */
            --hero-image: url('../img/login-bg.jpg');
        }


        /* ======================================================
           RESET
        ======================================================= */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        html {
            min-height: 100%;
            font-size: 16px;
        }


        body {
            min-height: 100vh;

            font-family:
                'Inter',
                -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                Roboto,
                Arial,
                sans-serif;

            background: var(--background);
            color: var(--text);

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }


        button,
        input {
            font: inherit;
        }


        button {
            border: 0;
        }


        a {
            color: inherit;
        }


        /* ======================================================
           CONTENEDOR PRINCIPAL
        ======================================================= */

        .login-page {

            min-height: 100vh;

            display: grid;

            grid-template-columns:
                minmax(460px, 48%)
                minmax(420px, 52%);

            background: var(--white);
        }


        /* ======================================================
           PANEL IZQUIERDO
        ======================================================= */

        .login-panel {

            min-height: 100vh;

            display: flex;
            flex-direction: column;

            justify-content: center;

            padding:
                50px
                clamp(50px, 7vw, 110px);

            background: var(--white);

            position: relative;

            z-index: 2;
        }


        .login-content {

            width: 100%;
            max-width: 450px;

            margin: 0 auto;
        }


        /* ======================================================
           MARCA
        ======================================================= */

        .brand {

            display: flex;
            align-items: center;

            gap: 14px;

            margin-bottom: 42px;
        }


        .brand-logo {

            width: 48px;
            height: 48px;

            object-fit: contain;

            border-radius: 10px;

            display: block;
        }


        .brand-fallback {

            width: 48px;
            height: 48px;

            display: none;

            align-items: center;
            justify-content: center;

            background: var(--primary);

            color: white;

            border-radius: 10px;

            font-size: 18px;
            font-weight: 700;
        }


        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }


        .brand-name {

            font-size: 15px;

            font-weight: 700;

            color: var(--navy);

            letter-spacing: -0.2px;
        }


        .brand-system {

            font-size: 12px;

            color: var(--text-muted);

            font-weight: 500;
        }


        /* ======================================================
           TITULO
        ======================================================= */

        .welcome {

            margin-bottom: 34px;
        }


        .welcome-label {

            display: inline-flex;
            align-items: center;

            gap: 7px;

            margin-bottom: 14px;

            font-size: 12px;

            font-weight: 700;

            letter-spacing: 0.08em;

            text-transform: uppercase;

            color: var(--primary);
        }


        .welcome-label::before {

            content: '';

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--primary);
        }


        .welcome h1 {

            font-size:
                clamp(28px, 3vw, 36px);

            line-height: 1.15;

            letter-spacing: -1.1px;

            font-weight: 700;

            color: var(--navy);

            margin-bottom: 13px;
        }


        .welcome p {

            max-width: 410px;

            font-size: 14px;

            line-height: 1.7;

            color: var(--text-secondary);
        }


        /* ======================================================
           FORMULARIO
        ======================================================= */

        .login-form {

            display: flex;
            flex-direction: column;

            gap: 22px;
        }


        .form-group {

            display: flex;
            flex-direction: column;

            gap: 8px;
        }


        .form-label {

            display: flex;
            align-items: center;

            justify-content: space-between;

            font-size: 13px;

            font-weight: 600;

            color: var(--text);
        }


        .input-wrapper {

            position: relative;

            width: 100%;
        }


        .input-icon {

            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            width: 18px;
            height: 18px;

            color: var(--text-muted);

            pointer-events: none;

            transition:
                color var(--transition);
        }


        .form-input {

            width: 100%;

            min-height: 50px;

            padding:
                0 16px 0 46px;

            border:
                1px solid var(--border);

            border-radius:
                var(--radius-md);

            outline: none;

            background: var(--white);

            color: var(--text);

            font-size: 14px;

            transition:
                border-color var(--transition),
                box-shadow var(--transition),
                background var(--transition);
        }


        .form-input::placeholder {

            color: var(--text-muted);
        }


        .form-input:hover {

            border-color: var(--color-border);
        }


        .form-input:focus {

            border-color:
                var(--border-focus);

            background:
                var(--white);

            box-shadow:
                0 0 0 4px
                color-mix(in srgb, var(--color-primary) 8%, transparent);
        }


        .form-input:focus + .input-icon {

            color: var(--primary);
        }


        /* ======================================================
           PASSWORD
        ======================================================= */

        .password-input {

            padding-right: 50px;
        }


        .toggle-password {

            position: absolute;

            right: 7px;
            top: 50%;

            transform:
                translateY(-50%);

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: transparent;

            color: var(--text-muted);

            cursor: pointer;

            border-radius: 8px;

            transition:
                color var(--transition),
                background var(--transition);
        }


        .toggle-password:hover {

            color: var(--primary);

            background:
                var(--primary-soft);
        }


        .toggle-password svg {

            width: 18px;
            height: 18px;
        }


        /* ======================================================
           OPCIONES
        ======================================================= */

        .form-options {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-top: -4px;
        }


        .remember {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            font-size: 12px;

            color: var(--text-secondary);

            cursor: pointer;
        }


        .remember input {

            width: 15px;
            height: 15px;

            accent-color: var(--primary);

            cursor: pointer;
        }


        .forgot-password {

            font-size: 12px;

            font-weight: 600;

            color: var(--primary);

            text-decoration: none;

            transition:
                color var(--transition);
        }


        .forgot-password:hover {

            color: var(--primary-hover);

            text-decoration: underline;
        }


        /* ======================================================
           BOTON PRINCIPAL
        ======================================================= */

        .btn-login {

            width: 100%;

            min-height: 51px;

            display: flex;

            align-items: center;
            justify-content: center;

            gap: 9px;

            padding: 0 20px;

            border-radius:
                var(--radius-md);

            background:
                var(--primary);

            color: white;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                var(--shadow-button);

            transition:
                background var(--transition),
                transform var(--transition),
                box-shadow var(--transition);
        }


        .btn-login:hover:not(:disabled) {

            background:
                var(--primary-hover);

            transform:
                translateY(-1px);

            box-shadow:
                0 10px 24px
                rgba(29, 78, 216, 0.25);
        }


        .btn-login:active:not(:disabled) {

            transform:
                translateY(0);
        }


        .btn-login:disabled {

            opacity: 0.75;

            cursor:
                not-allowed;

            box-shadow: none;
        }


        .btn-login svg {

            width: 17px;
            height: 17px;
        }


        /* ======================================================
           ERROR
        ======================================================= */

        .error-message {

            display: flex;

            align-items: flex-start;

            gap: 11px;

            padding: 13px 14px;

            margin-bottom: 22px;

            border:
                1px solid var(--danger-border);

            border-radius:
                var(--radius-md);

            background:
                var(--danger-bg);

            color:
                var(--danger);

            font-size: 13px;

            line-height: 1.5;

            animation:
                errorAppear 220ms ease-out;
        }


        .error-message svg {

            flex: 0 0 auto;

            width: 17px;
            height: 17px;

            margin-top: 1px;
        }


        @keyframes errorAppear {

            from {

                opacity: 0;

                transform:
                    translateY(-5px);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0);
            }
        }


        /* ======================================================
           SEGURIDAD
        ======================================================= */

        .security-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 24px;

            color: var(--text-muted);

            font-size: 11px;

            text-align: center;
        }


        .security-note svg {

            width: 14px;
            height: 14px;

            color: var(--success);
        }


        /* ======================================================
           FOOTER
        ======================================================= */

        .login-footer {

            margin-top: 42px;

            padding-top: 22px;

            border-top:
                1px solid var(--color-border);

            text-align: center;

            color: var(--text-muted);

            font-size: 11px;

            line-height: 1.6;
        }


        .login-footer strong {

            color: var(--text-secondary);

            font-weight: 600;
        }


        /* ======================================================
           PANEL DERECHO / IMAGEN
        ======================================================= */

        .hero-panel {

            min-height: 100vh;

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    color-mix(in srgb, var(--color-primary) 12%, var(--color-surface-2) 88%),
                    color-mix(in srgb, var(--color-primary) 6%, var(--color-surface) 94%)
                );
        }


        .hero-image {

            position: absolute;

            inset: 0;

            width: 100%;
            height: 100%;

            object-fit: cover;

            object-position: center;

            background-image:
                var(--hero-image);

            background-size: cover;

            background-position: center;
        }


        /*
         * Capa elegante para mejorar contraste
         * sin oscurecer demasiado la fotografía.
         */

        .hero-overlay {

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    180deg,
                    color-mix(in srgb, var(--color-surface) 4%, transparent) 0%,
                    color-mix(in srgb, var(--color-surface) 12%, transparent) 100%
                );

            pointer-events: none;
        }


        /* ======================================================
           INFORMACION SOBRE LA IMAGEN
        ======================================================= */

        .hero-content {

            position: absolute;

            left: 48px;
            right: 48px;
            bottom: 44px;

            color: var(--color-surface);

            z-index: 2;
        }


        .hero-line {

            width: 42px;
            height: 3px;

            margin-bottom: 17px;

            border-radius: 10px;

            background:
                rgba(255, 255, 255, 0.9);
        }


        .hero-content h2 {

            max-width: 570px;

            margin-bottom: 9px;

            font-size:
                clamp(22px, 2.4vw, 32px);

            line-height: 1.2;

            letter-spacing: -0.6px;

            font-weight: 600;

            text-shadow:
                0 2px 15px
                rgba(0, 0, 0, 0.15);
        }


        .hero-content p {

            max-width: 510px;

            font-size: 13px;

            line-height: 1.6;

            color:
                rgba(255, 255, 255, 0.88);

            text-shadow:
                0 1px 8px
                rgba(0, 0, 0, 0.15);
        }


        /* ======================================================
           INDICADOR VISUAL
        ======================================================= */

        .hero-badge {

            position: absolute;

            top: 38px;
            right: 38px;

            z-index: 3;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 9px 13px;

            border:
                1px solid
                rgba(255, 255, 255, 0.35);

            border-radius: 999px;

            background:
                rgba(255, 255, 255, 0.14);

            backdrop-filter:
                blur(10px);

            -webkit-backdrop-filter:
                blur(10px);

            color: white;

            font-size: 11px;

            font-weight: 600;

            letter-spacing: 0.02em;
        }


        .hero-badge-dot {

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #4ade80;

            box-shadow:
                0 0 0 4px
                rgba(74, 222, 128, 0.15);
        }


        /* ======================================================
           SPINNER
        ======================================================= */

        .spinner {

            width: 16px;
            height: 16px;

            border:
                2px solid
                rgba(255, 255, 255, 0.35);

            border-top-color:
                white;

            border-radius: 50%;

            animation:
                spin 650ms linear infinite;
        }


        @keyframes spin {

            to {
                transform:
                    rotate(360deg);
            }
        }


        /* ======================================================
           RESPONSIVE - TABLET
        ======================================================= */

        @media (max-width: 1050px) {

            .login-page {

                grid-template-columns:
                    minmax(430px, 52%)
                    minmax(360px, 48%);
            }


            .login-panel {

                padding:
                    45px 55px;
            }


            .hero-content {

                left: 32px;
                right: 32px;
                bottom: 35px;
            }


            .hero-badge {

                top: 25px;
                right: 25px;
            }
        }


        /* ======================================================
           RESPONSIVE - MOBILE
        ======================================================= */

        @media (max-width: 780px) {

            .login-page {

                display: block;

                min-height: 100vh;
            }


            .hero-panel {

                display: none;
            }


            .login-panel {

                min-height: 100vh;

                padding:
                    35px 24px;
            }


            .login-content {

                max-width: 470px;
            }


            .brand {

                margin-bottom: 48px;
            }


            .welcome h1 {

                font-size: 30px;
            }
        }


        /* ======================================================
           RESPONSIVE - TELEFONOS PEQUEÑOS
        ======================================================= */

        @media (max-width: 420px) {

            .login-panel {

                padding:
                    28px 20px;
            }


            .brand {

                margin-bottom: 38px;
            }


            .brand-logo,
            .brand-fallback {

                width: 43px;
                height: 43px;
            }


            .welcome {

                margin-bottom: 28px;
            }


            .welcome h1 {

                font-size: 27px;

                letter-spacing:
                    -0.8px;
            }


            .welcome p {

                font-size: 13px;
            }


            .login-form {

                gap: 19px;
            }


            .form-options {

                align-items: flex-start;

                flex-direction: column;

                gap: 12px;
            }


            .login-footer {

                margin-top: 35px;
            }
        }


        /* ======================================================
           REDUCIR ANIMACIONES
        ======================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {

                animation-duration:
                    0.01ms !important;

                animation-iteration-count:
                    1 !important;

                transition-duration:
                    0.01ms !important;
            }
        }

    </style>
</head>


<body>

    <main class="login-page">

        <!-- ==================================================
             PANEL IZQUIERDO
        =================================================== -->

        <section
            class="login-panel"
            aria-label="Inicio de sesión"
        >

            <div class="login-content">


                <!-- ==========================================
                     MARCA
                =========================================== -->

                <div class="brand">

                    <img
                        src="../img/logo.png"
                        alt="Studio & Progetto"
                        class="brand-logo"
                        onerror="
                            this.style.display='none';
                            this.nextElementSibling.style.display='flex';
                        "
                    >

                    <div class="brand-fallback">
                        SP
                    </div>


                    <div class="brand-text">

                        <span class="brand-name">
                            Studio &amp; Progetto
                        </span>

                        <span class="brand-system">
                            Gestión empresarial
                        </span>

                    </div>

                </div>


                <!-- ==========================================
                     BIENVENIDA
                =========================================== -->

                <header class="welcome">

                    <div class="welcome-label">
                        Acceso seguro
                    </div>

                    <h1>
                        Bienvenido al sistema
                    </h1>

                    <p>
                        Ingresa tus credenciales para acceder
                        al Sistema de Gestión de Ventas e
                        Inventario de Studio &amp; Progetto.
                    </p>

                </header>


                <!-- ==========================================
                     MENSAJE DE ERROR PHP
                =========================================== -->

                <?php if (!empty($error)): ?>

                    <div
                        class="error-message"
                        role="alert"
                        aria-live="polite"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >

                            <path
                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                            />

                            <line
                                x1="12"
                                y1="9"
                                x2="12"
                                y2="13"
                            />

                            <line
                                x1="12"
                                y1="17"
                                x2="12.01"
                                y2="17"
                            />

                        </svg>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $error,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- ==========================================
                     FORMULARIO
                =========================================== -->

                <form
                    action="<?php echo htmlspecialchars(
                        BASE_URL,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>auth/login"
                    method="POST"
                    class="login-form"
                    id="loginForm"
                >


                    <!-- ======================================
                         USUARIO
                    ======================================= -->

                    <div class="form-group">

                        <label
                            for="username"
                            class="form-label"
                        >
                            Usuario
                        </label>


                        <div class="input-wrapper">

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                placeholder="Ingresa tu usuario"
                                required
                                autocomplete="username"
                                autofocus
                            >


                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >

                                <path
                                    d="M20 21a8 8 0 0 0-16 0"
                                />

                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                />

                            </svg>

                        </div>

                    </div>


                    <!-- ======================================
                         CONTRASEÑA
                    ======================================= -->

                    <div class="form-group">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Contraseña
                        </label>


                        <div class="input-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input password-input"
                                placeholder="Ingresa tu contraseña"
                                required
                                autocomplete="current-password"
                            >


                            <svg
                                class="input-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >

                                <rect
                                    x="3"
                                    y="11"
                                    width="18"
                                    height="10"
                                    rx="2"
                                />

                                <path
                                    d="M7 11V7a5 5 0 0 1 10 0v4"
                                />

                            </svg>


                            <button
                                type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false"
                            >

                                <svg
                                    id="eyeIcon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >

                                    <path
                                        d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />

                                </svg>

                            </button>

                        </div>

                    </div>


                    <!-- ======================================
                         OPCIONES
                    ======================================= -->

                    <div class="form-options">

                        <label class="remember">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >

                            <span>
                                Mantener sesión iniciada
                            </span>

                        </label>


                        <a
                            href="#"
                            class="forgot-password"
                        >
                            ¿Olvidaste tu contraseña?
                        </a>

                    </div>


                    <!-- ======================================
                         BOTÓN INGRESAR
                    ======================================= -->

                    <button
                        type="submit"
                        class="btn-login"
                        id="loginButton"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >

                            <path
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                            />

                            <polyline
                                points="10 17 15 12 10 7"
                            />

                            <line
                                x1="15"
                                y1="12"
                                x2="3"
                                y2="12"
                            />

                        </svg>

                        <span id="loginButtonText">
                            Ingresar al sistema
                        </span>

                    </button>


                </form>


                <!-- ==========================================
                     SEGURIDAD
                =========================================== -->

                <div class="security-note">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >

                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="10"
                            rx="2"
                        />

                        <path
                            d="M7 11V7a5 5 0 0 1 10 0v4"
                        />

                    </svg>

                    Conexión protegida y acceso restringido

                </div>


                <!-- ==========================================
                     FOOTER
                =========================================== -->

                <footer class="login-footer">

                    © 2026
                    <strong>
                        Coconan Enterprise
                    </strong>
                    · Studio &amp; Progetto

                    <br>

                    Todos los derechos reservados.

                </footer>


            </div>

        </section>


        <!-- ==================================================
             PANEL DERECHO
        =================================================== -->

        <aside
            class="hero-panel"
            aria-label="Información corporativa"
        >

            <!-- Imagen -->
            <div
                class="hero-image"
                role="img"
                aria-label="Imagen corporativa"
            ></div>


            <!-- Overlay -->
            <div
                class="hero-overlay"
                aria-hidden="true"
            ></div>


            <!-- Indicador -->
            <div class="hero-badge">

                <span class="hero-badge-dot"></span>

                Plataforma empresarial

            </div>


            <!-- Texto sobre imagen -->
            <div class="hero-content">

                <div class="hero-line"></div>

                <h2>
                    Gestiona tu negocio
                    con mayor precisión.
                </h2>

                <p>
                    Centraliza tus ventas, inventario y
                    operaciones desde una plataforma
                    diseñada para facilitar la gestión
                    empresarial.
                </p>

            </div>

        </aside>

    </main>


    <!-- ======================================================
         JAVASCRIPT
    ======================================================= -->

        <script src="<?php echo BASE_URL; ?>js/theme.js"></script>

        <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const passwordInput =
                    document.getElementById('password');

                const togglePassword =
                    document.getElementById('togglePassword');

                const eyeIcon =
                    document.getElementById('eyeIcon');

                const loginForm =
                    document.getElementById('loginForm');

                const loginButton =
                    document.getElementById('loginButton');

                const loginButtonText =
                    document.getElementById('loginButtonText');


                /* ==================================================
                   MOSTRAR / OCULTAR CONTRASEÑA
                =================================================== */

                togglePassword.addEventListener(
                    'click',
                    function () {

                        const isPassword =
                            passwordInput.type === 'password';


                        passwordInput.type =
                            isPassword
                                ? 'text'
                                : 'password';


                        togglePassword.setAttribute(
                            'aria-pressed',
                            String(isPassword)
                        );


                        togglePassword.setAttribute(
                            'aria-label',
                            isPassword
                                ? 'Ocultar contraseña'
                                : 'Mostrar contraseña'
                        );


                        if (isPassword) {

                            eyeIcon.innerHTML = `
                                <path
                                    d="M3 3l18 18"
                                />

                                <path
                                    d="M10.6 10.6a2 2 0 0 0 2.8 2.8"
                                />

                                <path
                                    d="M9.9 4.2A10.7 10.7 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-3.2 4.7"
                                />

                                <path
                                    d="M6.6 6.6C3.8 8.4 2 12 2 12s3.5 8 10 8a10.7 10.7 0 0 0 3.1-.5"
                                />
                            `;

                        } else {

                            eyeIcon.innerHTML = `
                                <path
                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />
                            `;
                        }

                    }
                );


                /* ==================================================
                   ESTADO DE CARGA DEL LOGIN
                =================================================== */

                loginForm.addEventListener(
                    'submit',
                    function (event) {

                        /*
                         * Permite que PHP procese normalmente
                         * el formulario.
                         *
                         * Solamente cambia visualmente el botón.
                         */

                        if (
                            !loginForm.checkValidity()
                        ) {

                            return;
                        }


                        loginButton.disabled = true;


                        loginButtonText.textContent =
                            'Verificando acceso...';


                        loginButton.querySelector('svg')
                            .outerHTML = `
                                <span
                                    class="spinner"
                                    aria-hidden="true"
                                ></span>
                            `;
                    }
                );


                /* ==================================================
                   EFECTO SUAVE EN INPUTS
                =================================================== */

                const inputs =
                    document.querySelectorAll(
                        '.form-input'
                    );


                inputs.forEach(
                    function (input) {

                        input.addEventListener(
                            'focus',
                            function () {

                                const wrapper =
                                    input.closest(
                                        '.input-wrapper'
                                    );

                                if (!wrapper) {
                                    return;
                                }

                                const icon =
                                    wrapper.querySelector(
                                        '.input-icon'
                                    );

                                if (icon) {

                                    icon.style.color =
                                        'var(--primary)';
                                }
                            }
                        );


                        input.addEventListener(
                            'blur',
                            function () {

                                const wrapper =
                                    input.closest(
                                        '.input-wrapper'
                                    );

                                if (!wrapper) {
                                    return;
                                }

                                const icon =
                                    wrapper.querySelector(
                                        '.input-icon'
                                    );

                                if (
                                    icon &&
                                    document.activeElement !== input
                                ) {

                                    icon.style.color =
                                        'var(--text-muted)';
                                }
                            }
                        );

                    }
                );

            }
        );

    </script>

</body>

</html>