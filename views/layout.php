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
    <link href="<?= BASE_URL ?>/css/site.css" rel="stylesheet">
    <style>
        .navbar-custom        { background-color: #215ca8 !important; padding: .75rem 1rem; }
        .nav-btn              {
            background-color: rgba(255,255,255,.15);
            color: #fff !important;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 20px;
            padding: .45rem 1rem;
            margin: .2rem .3rem;
            font-weight: 600;
            font-size: .9rem;
            transition: all .25s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            position: relative;
        }
        .nav-btn:hover        { background-color: #fff !important; color: #215ca8 !important; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,.2); }
        .btn-logout           { background-color: #dc3545; color: #fff !important; border: none; border-radius: 20px; padding: .45rem 1.1rem; font-weight: 700; transition: all .25s ease-in-out; text-decoration: none; }
        .btn-logout:hover     { background-color: #b02a37; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220,53,69,.4); }
        .user-badge           { background-color: rgba(0,0,0,.2); border-radius: 20px; padding: .4rem .9rem; border: 1px solid rgba(255,255,255,.2); font-size: .9rem; }
        .navbar-badge         {
            position: absolute;
            top: -6px; right: -6px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            font-size: .65rem;
            font-weight: 700;
            width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
            line-height: 1;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<?php
// El badge solo consulta solicitudes cuando el usuario es administrador.
$_navSolicitudesPendientes = 0;
if (Auth::check() && Auth::role() === 'Administrador') {
    try {
        $db = Database::getConnection();
        $row = $db->query("SELECT COUNT(*) AS total FROM solicitudes_restablecimiento_password WHERE estado = 'Pendiente'")->fetch();
        $_navSolicitudesPendientes = (int)($row['total'] ?? 0);
    } catch (Throwable $e) {
        $_navSolicitudesPendientes = 0;
    }
}
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center fw-bold fs-5 me-3"
               href="<?= BASE_URL ?>/account/login">
                <span class="fs-4 me-2">🍳</span><span>Restaurante Intecap</span>
            </a>
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
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
                            <li class="nav-item">
                                <a class="nav-btn" href="<?= BASE_URL ?>/admin/solicitudes-restablecimiento">
                                    <i class="bi bi-shield-lock-fill"></i> Solicitudes
                                    <?php if ($_navSolicitudesPendientes > 0): ?>
                                        <span class="navbar-badge">
                                            <?= min($_navSolicitudesPendientes, 99) ?>
                                        </span>
                                    <?php endif; ?>
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

                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <?php if (Auth::check()): ?>
                    <div class="d-flex align-items-center text-white gap-2 flex-wrap mt-2 mt-lg-0">
                        <div class="user-badge d-flex align-items-center me-1">
                            <i class="bi bi-person-circle fs-5 me-2 text-warning"></i>
                            <span class="fw-bold"><?= htmlspecialchars(Auth::user()['nombre']) ?></span>
                            <span class="ms-2 badge bg-light text-dark small">
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

<footer class="footer mt-auto py-3 bg-dark text-white-50 text-center">
    <div class="container">
        <small>&copy; <?= date('Y') ?> &mdash; Restaurante Escuela INTECAP</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/site.js"></script>
</body>
</html>
