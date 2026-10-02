<?php
/**
 * Layout HTML compartido por las vistas autenticadas.
 *
 * Construye la navegacion segun el rol, muestra mensajes flash y carga los
 * recursos comunes alrededor de la variable $content.
 *
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurante Escuela INTECAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .navbar-custom        { background: linear-gradient(135deg, #0a2647 0%, #123d6b 50%, #1e40af 100%) !important; padding: .55rem 1rem; }
        .nav-btn              {
            background-color: rgba(255,255,255,.14);
            color: #fff !important;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 999px;
            padding: .38rem .75rem;
            margin: .15rem .2rem;
            font-weight: 600;
            font-size: .86rem;
            white-space: nowrap;
            transition: all .2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            text-decoration: none;
            position: relative;
        }
        .nav-btn:hover        { background-color: #fff !important; color: #123d6b !important; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.2); }
        .btn-logout           {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: #fff !important;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 999px;
            padding: .38rem .9rem;
            font-weight: 700;
            font-size: .86rem;
            white-space: nowrap;
            transition: all .2s ease-in-out;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .btn-logout:hover     { background: linear-gradient(135deg, #b02a37, #842029); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(220,53,69,.4); }
        .user-badge           {
            background-color: rgba(0,0,0,.25);
            border-radius: 999px;
            padding: .38rem .85rem;
            border: 1px solid rgba(255,255,255,.25);
            font-size: .85rem;
            white-space: nowrap;
        }
        .brand-text           { font-size: clamp(0.95rem, 1.8vw, 1.2rem); letter-spacing: -0.2px; }
        @media (max-width: 1199.98px) {
            #navbarContent {
                background: rgba(10, 38, 71, 0.98);
                border-radius: 12px;
                padding: 1rem;
                margin-top: .75rem;
                border: 1px solid rgba(255,255,255,0.15);
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            }
            .nav-btn {
                width: 100%;
                justify-content: flex-start;
                padding: .6rem 1rem;
                margin: .2rem 0;
                font-size: .92rem;
            }
            .navbar-user-actions {
                width: 100%;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: .5rem !important;
                border-top: 1px solid rgba(255,255,255,0.15);
                padding-top: .75rem;
                margin-top: .75rem;
            }
            .navbar-user-actions .user-badge {
                justify-content: center;
                width: 100%;
                padding: .5rem;
            }
            .btn-logout {
                width: 100%;
                justify-content: center;
                padding: .55rem 1rem;
            }
        }
    </style>
    <!-- // Hoja de estilos del sistema con soporte responsivo y pestañas destacadas -->
    <link href="<?= BASE_URL ?>/css/site.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<header>
    <nav class="navbar navbar-expand-xl navbar-dark navbar-custom shadow">
        <div class="container-fluid px-2 px-sm-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center fw-bold me-2 me-md-3"
               href="<?= BASE_URL ?>/account/login">
                <span class="fs-4 me-2">🍳</span><span class="brand-text">Restaurante Intecap</span>
            </a>
            <button class="navbar-toggler border-0 p-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Alternar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-xl-0 align-items-xl-center">
                    <?php if (Auth::check()): ?>

                        <?php if (Auth::role() === 'Administrador'): ?>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/admin/index">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/admin/usuarios">
                                    <i class="bi bi-people-fill"></i> Usuarios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/cocina/index">
                                    <i class="bi bi-fire"></i> Área Cocina
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/index">
                                    <i class="bi bi-egg-fried"></i> Hacer Reserva
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/historial">
                                    <i class="bi bi-journal-text"></i> Mi Historial
                                </a>
                            </li>
                            <!-- Reemplazo de Solicitudes por La Carta (Panel Administrativo) -->
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/carta/admin">
                                    <i class="bi bi-book-half"></i> La Carta
                                </a>
                            </li>
                            <!-- Gestión de Anuncios Informativos del Login -->
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/admin/anuncios">
                                    <i class="bi bi-megaphone-fill"></i> Anuncios
                                </a>
                            </li>

                        <?php elseif (Auth::role() === 'Cocina'): ?>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/cocina/index">
                                    <i class="bi bi-fire"></i> Área Cocina
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/index">
                                    <i class="bi bi-egg-fried"></i> Hacer Reserva
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/historial">
                                    <i class="bi bi-journal-text"></i> Mis Reservas
                                </a>
                            </li>
                            <!-- Nueva pestaña La Carta para Cocina -->
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/carta/index">
                                    <i class="bi bi-book-half"></i> La Carta
                                </a>
                            </li>

                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/index">
                                    <i class="bi bi-egg-fried"></i> Menú del Día
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/empleado/historial">
                                    <i class="bi bi-journal-text"></i> Mi Historial
                                </a>
                            </li>
                            <!-- Nueva pestaña La Carta para Empleados/Usuarios -->
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/carta/index">
                                    <i class="bi bi-book-half"></i> La Carta
                                </a>
                            </li>

                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <?php if (Auth::check()): ?>
                    <div class="navbar-user-actions d-flex align-items-center text-white gap-2 flex-wrap mt-2 mt-xl-0">
                        <div class="user-badge d-flex align-items-center me-1 flex-wrap gap-1">
                            <i class="bi bi-person-circle fs-5 me-1 text-warning"></i>
                            <span class="fw-bold"><?= htmlspecialchars(Auth::user()['nombre']) ?></span>
                            <span class="badge bg-light text-dark small">
                                <?= htmlspecialchars(Auth::role() ?? '') ?>
                            </span>
                        </div>
                        <a href="<?= BASE_URL ?>/account/cambiar-password"
                           class="nav-btn d-flex align-items-center gap-1">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </a>
                        <a href="<?= BASE_URL ?>/account/logout"
                           class="btn-logout d-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<main role="main" class="flex-grow-1">
    <div class="container-fluid mt-3">
        <?php foreach (flash_get_and_clear() as $tipo => $mensaje): ?>
            <div class="alert alert-<?= $tipo === 'error' ? 'danger' : ($tipo === 'advertencia' ? 'warning' : 'success') ?> alert-dismissible fade show fw-bold"
                 role="alert">
                <?= $tipo === 'error' ? '⚠️' : ($tipo === 'advertencia' ? '⚠️' : '✅') ?>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <?= $content ?>
</main>

<footer class="footer mt-auto py-3 bg-dark text-white text-center">
    <div class="container">
        <small class="text-white" style="opacity: 0.9;">&copy; <?= date('Y') ?> &mdash; Restaurante Escuela INTECAP</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/site.js"></script>
</body>
</html>
