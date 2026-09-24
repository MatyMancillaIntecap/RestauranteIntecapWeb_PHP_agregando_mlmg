<?php
/**
 * Ficha de usuario con datos personales, metricas acumuladas e historial.
 *
 * @var array $detalle Contiene info_usuario, historial_reservas y totales acumulados.
 */
?>
<div class="container mt-3 mb-5">

    <!-- Encabezado con info completa del usuario -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary fw-bold">👤 Detalle de Usuario</h3>
        <a href="<?= BASE_URL ?>/admin/usuarios" class="btn btn-secondary">← Volver a Usuarios</a>
    </div>

    <!-- Ficha de información del usuario -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fw-bold">📋 Información del Usuario</h5>
        </div>
        <div class="card-body">
            <?php $u = $detalle['info_usuario']; ?>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted w-40">Nombre completo:</th>
                            <td class="fw-bold"><?= htmlspecialchars($u['nombre']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Correo electrónico:</th>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Rol asignado:</th>
                            <td>
                                <span class="badge bg-primary fs-6">
                                    <?= htmlspecialchars($u['nombre_rol']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Límite de almuerzos:</th>
                            <td>
                                <strong>
                                    <?= (int)$u['max_almuerzos'] === 0 ? 'Ilimitado' : (int)$u['max_almuerzos'] ?>
                                </strong>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted w-40">NIT predeterminado:</th>
                            <td><code><?= htmlspecialchars($u['nit_facturacion']) ?></code></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Estado de la cuenta:</th>
                            <td>
                                <span class="badge bg-<?= $u['activo'] ? 'success' : 'secondary' ?> fs-6">
                                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Fecha de creación:</th>
                            <td><?= htmlspecialchars($u['fecha_creacion']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de resumen acumulado -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-success text-white">
                <div class="small fw-bold text-uppercase">💰 Total gastado acumulado</div>
                <h4 class="fw-bold mb-0">
                    Q <?= number_format((float)$detalle['total_gastado_acumulado'], 2) ?>
                </h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-primary text-white">
                <div class="small fw-bold text-uppercase">🍽️ Total platillos reservados</div>
                <h4 class="fw-bold mb-0"><?= (int)$detalle['total_platillos_reservados'] ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-secondary text-white">
                <div class="small fw-bold text-uppercase">❌ Reservas canceladas</div>
                <h4 class="fw-bold mb-0"><?= (int)$detalle['total_reservas_canceladas'] ?></h4>
            </div>
        </div>
    </div>

    <!-- Historial completo del usuario -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0 fw-bold">📜 Historial Completo de Reservas</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha consumo</th>
                            <th>Platillo</th>
                            <th>Cant.</th>
                            <th>Precio unit.</th>
                            <th>Total</th>
                            <th>Forma pago</th>
                            <th>NIT</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($detalle['historial_reservas'])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Este usuario no tiene reservas registradas.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($detalle['historial_reservas'] as $r): ?>
                            <tr>
                                <td><?= (int)$r['reserva_id'] ?></td>
                                <td><?= htmlspecialchars($r['fecha_consumo']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['nombre_plato']) ?></td>
                                <td><?= (int)$r['cantidad'] ?></td>
                                <td>Q <?= number_format((float)$r['precio_unitario'], 2) ?></td>
                                <td class="fw-bold text-success">
                                    Q <?= number_format((float)$r['cantidad'] * (float)$r['precio_unitario'], 2) ?>
                                </td>
                                <td><?= htmlspecialchars($r['forma_pago']) ?></td>
                                <td><code><?= htmlspecialchars($r['nit_facturacion']) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $r['estado'] === 'Activa' ? 'success' : 'secondary' ?>">
                                        <?= htmlspecialchars($r['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
