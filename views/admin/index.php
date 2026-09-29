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

    <!-- // TARJETAS DE MÉTRICAS CON COLORES LLAMATIVOS, FUERTES Y LÍMITES CLAROS -->
    <div class="row g-4 mb-4">

        <!-- Platillos de Dieta (Azul Cyan Eléctrico) -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%); border: 2.5px solid #0369a1 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-white text-info mb-2 fw-bold text-uppercase shadow-sm">🥗 Platillos Dieta</span>
                        <h3 class="fw-bold text-white mb-0">
                            <?= (int)$dieta_solicitados ?> <small class="text-white-50 fs-6">/ <?= (int)$dieta_iniciales ?> publ.</small>
                        </h3>
                        <small class="text-white-50 d-block mt-1 fw-semibold">Platillos solicitados hoy</small>
                    </div>
                    <div class="fs-1 p-2 rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 58px; height: 58px; background: rgba(255, 255, 255, 0.25);">
                        🌿
                    </div>
                </div>
            </div>
        </div>

        <!-- Platillos Normales (Púrpura / Violeta Intenso) -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); border: 2.5px solid #6d28d9 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-white mb-2 fw-bold text-uppercase shadow-sm" style="color: #7c3aed;">🍽️ Platillos Normales</span>
                        <h3 class="fw-bold text-white mb-0">
                            <?= (int)$normales_solicitados ?> <small class="text-white-50 fs-6">/ <?= (int)$normales_iniciales ?> publ.</small>
                        </h3>
                        <small class="text-white-50 d-block mt-1 fw-semibold">Platillos solicitados hoy</small>
                    </div>
                    <div class="fs-1 p-2 rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 58px; height: 58px; background: rgba(255, 255, 255, 0.25);">
                        🍛
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas de Hoy (Verde Esmeralda Brillante) -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: 2.5px solid #047857 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-white text-success mb-2 fw-bold text-uppercase shadow-sm">💰 Ventas del Día</span>
                        <h3 class="fw-bold text-white mb-0">
                            Q <?= number_format((float)$ventas_hoy, 2) ?>
                        </h3>
                        <small class="text-white-50 d-block mt-1 fw-semibold">Ingresos recaudados hoy</small>
                    </div>
                    <div class="fs-1 p-2 rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 58px; height: 58px; background: rgba(255, 255, 255, 0.25);">
                        💵
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservas de Hoy (Rojo Rubí / Coral Intenso) -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%); border: 2.5px solid #be123c !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-white text-danger mb-2 fw-bold text-uppercase shadow-sm">📋 Reservas del Día</span>
                        <h3 class="fw-bold text-white mb-0">
                            <?= (int)$reservas_hoy ?> <small class="text-white-50 fs-6">solicitudes</small>
                        </h3>
                        <small class="text-white-50 d-block mt-1 fw-semibold">Total pedidos registrados</small>
                    </div>
                    <div class="fs-1 p-2 rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 58px; height: 58px; background: rgba(255, 255, 255, 0.25);">
                        👥
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TARJETAS USUARIOS + LA CARTA CON COLORES LLAMATIVOS -->
    <div class="row g-4 mb-4">
        <!-- Usuarios del Sistema (Azul Marino Oscuro Pizarra) -->
        <div class="col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 2.5px solid #334155 !important;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-warning text-dark mb-2 fw-bold text-uppercase shadow-sm">👥 Usuarios del Sistema</span>
                        <h5 class="fw-bold text-white mt-1 mb-0">
                            <?= (int)$usuarios_con_reserva ?> con reserva hoy <span class="text-white-50 fs-6">/ <?= (int)$total_usuarios ?> registrados</span>
                        </h5>
                        <small class="text-white-50">Gestión de roles y accesos al restaurante</small>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/usuarios" class="btn btn-warning text-dark fw-bold btn-sm shadow">
                        Ver Usuarios →
                    </a>
                </div>
            </div>
        </div>
        <!-- Acceso directo a La Carta (Azul Zafiro Brillante) -->
        <div class="col-md-6">
            <div class="card shadow rounded-3 p-3 h-100 text-white"
                 style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); border: 2.5px solid #1e40af !important;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-white text-primary mb-2 fw-bold text-uppercase shadow-sm">📖 Módulo La Carta</span>
                        <h5 class="fw-bold text-white mt-1 mb-0">Catálogo General y Recuento Consolidado</h5>
                        <small class="text-white-50">Entradas, platos fuertes, bebidas y postres</small>
                    </div>
                    <a href="<?= BASE_URL ?>/carta/admin" class="btn btn-light text-primary fw-bold btn-sm shadow">
                        Administrar →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- RESUMEN ESTADÍSTICO DETALLADO CON FILTRO POR FECHA -->
    <div class="card shadow rounded-3 border-2">
        <div class="card-header bg-primary text-white py-3" style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%) !important;">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <span>📊</span> Resumen Estadístico Detallado por Fecha
            </h5>
        </div>
        <div class="card-body p-4">

            <!-- Formulario de filtro de KPIs con límites y colores -->
            <div class="p-3 rounded-3 mb-4 shadow-sm" style="background: #f8fafc; border: 2px solid #cbd5e1 !important;">
                <form method="get" action="<?= BASE_URL ?>/admin/index" class="row g-3 align-items-end">
                    <div class="col-md-5 col-12">
                        <label class="form-label small fw-bold text-dark">Fecha inicio:</label>
                        <input type="date" name="fechaInicio" class="form-control"
                               value="<?= htmlspecialchars($fecha_inicio) ?>">
                    </div>
                    <div class="col-md-5 col-12">
                        <label class="form-label small fw-bold text-dark">Fecha fin:</label>
                        <input type="date" name="fechaFin" class="form-control"
                               value="<?= htmlspecialchars($fecha_fin) ?>">
                    </div>
                    <div class="col-md-2 col-12">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">🔍 Filtrar</button>
                    </div>
                </form>
            </div>

            <hr>

            <!-- Tarjetas de resumen detallado con colores vivos -->
            <div class="row text-center g-3">
                <div class="col-md-4 col-12">
                    <div class="p-3 rounded-3 shadow-sm h-100" style="background: #ecfdf5; border: 2.5px solid #10b981 !important;">
                        <span class="text-success small fw-bold text-uppercase d-block">💰 Total de Ventas en la Fecha</span>
                        <h3 class="fw-bold text-success mt-2 mb-0">Q <?= number_format((float)$ventas_hoy, 2) ?></h3>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="p-3 rounded-3 shadow-sm h-100" style="background: #eff6ff; border: 2.5px solid #3b82f6 !important;">
                        <span class="text-primary small fw-bold text-uppercase d-block">📋 Total de Reservas Activas</span>
                        <h3 class="fw-bold text-primary mt-2 mb-0"><?= (int)$reservas_hoy ?></h3>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="p-3 rounded-3 shadow-sm h-100" style="background: #faf5ff; border: 2.5px solid #8b5cf6 !important;">
                        <span class="small fw-bold text-uppercase d-block" style="color: #7c3aed;">👥 Usuarios que Reservaron</span>
                        <h3 class="fw-bold mt-2 mb-0" style="color: #6d28d9;">
                            <?= (int)$usuarios_con_reserva ?> <small class="fs-6 opacity-75">/ <?= (int)$total_usuarios ?></small>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

</div>
