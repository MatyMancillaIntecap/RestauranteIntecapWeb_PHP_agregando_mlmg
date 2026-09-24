<?php
/**
 * Dashboard administrativo con KPIs, filtros por fecha y exportacion global.
 *
 * @var int $dieta_solicitados
 * @var int $dieta_iniciales
 * @var int $normales_solicitados
 * @var int $normales_iniciales
 * @var float $ventas_hoy
 * @var int $reservas_hoy
 * @var int $usuarios_con_reserva
 * @var int $total_usuarios
 * @var int $solicitudes_pendientes
 * @var string $fecha_inicio
 * @var string $fecha_fin
 */
?>
<div class="container-fluid mt-3 mb-5">

    <!-- ENCABEZADO -->
    <div class="row align-items-center mb-4">
        <div class="col-12 text-center">
            <h3 class="text-primary fw-bold">👨‍💼 Panel de Administración</h3>
            <p class="text-muted mb-0">Métricas en tiempo real, rendimiento de ventas y control del sistema.</p>
        </div>
    </div>

    <!-- 4 TARJETAS DE MÉTRICAS -->
    <div class="row g-4 mb-4">

        <!-- Platillos de Dieta -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background-color:#ADD8E6;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-dark small fw-bold text-uppercase">🥗 Platillos de Dieta</span>
                        <h4 class="fw-bold text-dark mt-2 mb-0">
                            <?= (int)$dieta_solicitados ?> solicitados / <?= (int)$dieta_iniciales ?> publicados
                        </h4>
                    </div>
                    <div class="fs-1 text-primary">🌿</div>
                </div>
            </div>
        </div>

        <!-- Platillos Normales -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background-color:#9ACD32;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-dark small fw-bold text-uppercase">🍽️ Platillos Normales</span>
                        <h4 class="fw-bold text-dark mt-2 mb-0">
                            <?= (int)$normales_solicitados ?> solicitados / <?= (int)$normales_iniciales ?> publicados
                        </h4>
                    </div>
                    <div class="fs-1 text-success">🍛</div>
                </div>
            </div>
        </div>

        <!-- Ventas de Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background-color:#FF7F50;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white small fw-bold text-uppercase">💰 Ventas de Hoy</span>
                        <h4 class="fw-bold text-white mt-2 mb-0">
                            Q <?= number_format((float)$ventas_hoy, 2) ?>
                        </h4>
                    </div>
                    <div class="fs-1 text-warning">💵</div>
                </div>
            </div>
        </div>

        <!-- Reservas de Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100" style="background-color:#FFF0F5;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-dark small fw-bold text-uppercase">🍽️ Reservas de Hoy</span>
                        <h4 class="fw-bold text-dark mt-2 mb-0">
                            <?= (int)$reservas_hoy ?> solicitudes
                        </h4>
                    </div>
                    <div class="fs-1 text-danger">📋</div>
                </div>
            </div>
        </div>

    </div>

    <!-- TARJETAS USUARIOS + SOLICITUDES -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-dark text-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-light small fw-bold text-uppercase">👥 Usuarios del Sistema</span>
                        <h5 class="fw-bold text-info mt-1 mb-0">
                            <?= (int)$usuarios_con_reserva ?> con reservas hoy / <?= (int)$total_usuarios ?> registrados
                        </h5>
                    </div>
                    <div class="fs-2">👤</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <a href="<?= BASE_URL ?>/admin/solicitudes-restablecimiento"
               class="card border-0 shadow-sm rounded-3 p-3 bg-warning text-dark text-decoration-none h-100 d-block">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small fw-bold text-uppercase">🔐 Solicitudes de contraseña</span>
                        <h5 class="fw-bold mt-1 mb-0"><?= (int)$solicitudes_pendientes ?> pendientes</h5>
                    </div>
                    <div class="fs-2">📨</div>
                </div>
            </a>
        </div>
    </div>

    <!-- RESUMEN ESTADÍSTICO DETALLADO CON FILTRO POR FECHA -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold">📊 Resumen Estadístico Detallado por Fecha</h5>
        </div>
        <div class="card-body">

            <!-- Formulario de filtro de KPIs -->
            <form method="get" action="<?= BASE_URL ?>/admin/index" class="row g-3 align-items-end mb-4">
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold">Fecha inicio:</label>
                    <input type="date" name="fechaInicio" class="form-control"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold">Fecha fin:</label>
                    <input type="date" name="fechaFin" class="form-control"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-2 col-12">
                    <button type="submit" class="btn btn-dark w-100 fw-bold">🔍 Filtrar</button>
                </div>
            </form>

            <hr>

            <!-- Tarjetas de resumen detallado -->
            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small d-block">Total de Ventas en la Fecha</span>
                        <h4 class="fw-bold text-success">Q <?= number_format((float)$ventas_hoy, 2) ?></h4>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small d-block">Total de Reservas Activas</span>
                        <h4 class="fw-bold text-primary"><?= (int)$reservas_hoy ?></h4>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small d-block">Usuarios que Reservaron</span>
                        <h4 class="fw-bold text-dark">
                            <?= (int)$usuarios_con_reserva ?> / <?= (int)$total_usuarios ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
