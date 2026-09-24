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

    <!-- CABECERA DEL HISTORIAL -->
    <div class="mb-4">
        <div>
            <h3 class="text-primary fw-bold mb-0">📜 Mi Historial de Reservas</h3>
            <p class="text-muted small mb-0">
                Consulta tus solicitudes de almuerzos realizadas.
            </p>
        </div>
    </div>

    <!-- FILTRO POR RANGO DE FECHAS -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body bg-light p-3">
            <form method="get" action="<?= BASE_URL ?>/empleado/historial"
                  class="row g-3 align-items-end">
                <div class="col-md-4 col-6">
                    <label class="form-label small fw-bold text-secondary">Fecha Inicio:</label>
                    <input type="date" name="fechaInicio" class="form-control"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-4 col-6">
                    <label class="form-label small fw-bold text-secondary">Fecha Fin:</label>
                    <input type="date" name="fechaFin" class="form-control"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-4 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100">
                        🔍 Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/empleado/historial" class="btn btn-outline-secondary fw-bold">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"># Reserva</th>
                            <th>Fecha Consumo</th>
                            <th>Platillo</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Total Pagado</th>
                            <th class="text-center">Forma Pago</th>
                            <th class="text-center">NIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historial)): ?>
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <!-- # RESERVA -->
                                    <td class="ps-3 fw-bold text-primary">
                                        #<?= (int)$item['reserva_id'] ?>
                                    </td>

                                    <!-- FECHA -->
                                    <td>
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
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-3">
                                            <?= (int)$item['cantidad'] ?>
                                        </span>
                                    </td>

                                    <!-- TOTAL PAGADO -->
                                    <td class="text-end fw-bold text-success">
                                        Q <?= number_format((float)$item['cantidad'] * (float)$item['precio_unitario'], 2) ?>
                                    </td>

                                    <!-- FORMA PAGO -->
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($item['forma_pago']) ?>
                                        </span>
                                    </td>

                                    <!-- NIT -->
                                    <td class="text-center">
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

