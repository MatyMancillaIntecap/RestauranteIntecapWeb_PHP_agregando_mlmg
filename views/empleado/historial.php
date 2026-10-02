<?php
/**
 * Historial personal filtrable de reservas.
 *
 * @var string $fecha_inicio
 * @var string $fecha_fin
 * @var array $historial
 */
?>
<div class="container-fluid mt-4 mb-5">

    <!-- // CABECERA DEL HISTORIAL CON COLORES FUERTES INTECAP -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                <span>📜</span> Mi Historial de Reservas
            </h3>
            <p class="text-white-50 mb-0">
                Consulta tus solicitudes de almuerzos realizadas y gestiona tus reservas.
            </p>
        </div>
    </div>

    <!-- FILTRO POR RANGO DE FECHAS -->
    <div class="card shadow-sm rounded-3 mb-4 border-2" style="background: #f8fafc; border: 2px solid #cbd5e1 !important;">
        <div class="card-body p-3">
            <form method="get" action="<?= BASE_URL ?>/empleado/historial"
                  class="row g-2 g-md-3 align-items-end">
                <div class="col-md-4 col-sm-6 col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Fecha Inicio:</label>
                    <input type="date" name="fechaInicio" class="form-control"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Fecha Fin:</label>
                    <input type="date" name="fechaFin" class="form-control"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-4 col-12 d-flex gap-2 mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary fw-bold flex-fill shadow-sm">
                        🔍 Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/empleado/historial" class="btn btn-outline-secondary fw-bold flex-fill text-center">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL ENMARCADA -->
    <div class="card shadow rounded-3 border-2">
        <div class="card-header text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <span>📜</span> Solicitudes y Reservas Realizadas
            </h5>
            <span class="badge bg-warning text-dark fw-bold shadow-sm"><?= count($historial) ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive border-0">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3 cell-nowrap"># Reserva</th>
                            <th class="cell-nowrap">Fecha Consumo</th>
                            <th style="min-width: 170px;">Platillo</th>
                            <th class="text-center cell-nowrap">Cantidad</th>
                            <th class="text-end cell-nowrap">Total Pagado</th>
                            <th class="text-center cell-nowrap">Forma Pago</th>
                            <th class="text-center cell-nowrap">NIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historial)): ?>
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <!-- # RESERVA -->
                                    <td class="ps-3 fw-bold text-primary cell-nowrap">
                                        #<?= (int)$item['reserva_id'] ?>
                                    </td>

                                    <!-- FECHA -->
                                    <td class="cell-nowrap">
                                        <div class="fw-bold">
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($item['fecha_consumo']))) ?>
                                        </div>
                                        <small class="text-muted">
                                            Solicitado: <?= htmlspecialchars(substr($item['fecha_reserva'], 11, 5)) ?> hrs
                                        </small>
                                    </td>

                                    <!-- PLATILLO -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item['imagen_url'])): ?>
                                                <img src="<?= resolve_image_url($item['imagen_url']) ?>"
                                                     alt="foto"
                                                     style="width:40px;height:40px;object-fit:cover;"
                                                     class="rounded"
                                                     onerror="this.style.display='none'">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold">
                                                    <?= htmlspecialchars($item['nombre_plato']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    Consumo: <?= htmlspecialchars($item['donde_consume']) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- CANTIDAD -->
                                    <td class="text-center cell-nowrap">
                                        <span class="badge bg-primary fs-6 px-3">
                                            <?= (int)$item['cantidad'] ?>
                                        </span>
                                    </td>

                                    <!-- TOTAL PAGADO -->
                                    <td class="text-end fw-bold text-success cell-nowrap">
                                        Q <?= number_format((float)$item['cantidad'] * (float)$item['precio_unitario'], 2) ?>
                                    </td>

                                    <!-- FORMA PAGO -->
                                    <td class="text-center cell-nowrap">
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($item['forma_pago']) ?>
                                        </span>
                                    </td>

                                    <!-- NIT -->
                                    <td class="text-center cell-nowrap">
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($item['nit_facturacion']) ?>
                                        </span>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="fs-4 mb-2">🍽️</div>
                                    No se encontraron reservas para el rango de fechas seleccionado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

