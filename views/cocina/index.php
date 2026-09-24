<?php
/**
 * Panel de cocina: menu, consolidado, detalle diario y exportaciones.
 *
 * @var string $fecha_consulta
 * @var array $menus
 * @var array $consolidado
 * @var array $reservas_detalle
 */
?>
<div class="container-fluid mt-3 mb-5">

    <!-- BARRA SUPERIOR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body bg-light rounded-3 p-3">
            <div class="row align-items-center">
                <div class="col-md-5 col-12">
                    <h3 class="text-primary fw-bold mb-1">👨‍🍳 Área de Cocina</h3>
                    <p class="text-muted mb-0">Gestión de platillos, consolidado de pedidos y descarga de reportes.</p>
                </div>
                <div class="col-md-7 col-12 mt-3 mt-md-0">
                    <form method="get" action="<?= BASE_URL ?>/cocina/index"
                          class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                        <label class="fw-bold text-nowrap">Fecha Consulta:</label>
                        <input type="date" name="fecha" class="form-control w-auto"
                               value="<?= htmlspecialchars($fecha_consulta) ?>"
                               onchange="this.form.submit()">
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/cocina/descargar-excel?fecha=<?= urlencode($fecha_consulta) ?>"
                               class="btn btn-success fw-bold text-nowrap">
                                📊 Excel
                            </a>
                            <a href="<?= BASE_URL ?>/cocina/descargar-pdf?fecha=<?= urlencode($fecha_consulta) ?>"
                               class="btn btn-outline-danger fw-bold text-nowrap">
                                📄 PDF
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS DE NAVEGACIÓN (igual que en C#) -->
    <?php
    // Estos totales alimentan las etiquetas de las pestanas y se calculan una vez.
    $totalConsolidado = 0;
    foreach ($consolidado as $c) { $totalConsolidado += (int)$c['total_solicitado']; }
    $totalReservas = count($reservas_detalle);
    ?>
    <ul class="nav nav-tabs nav-justified mb-4 fw-bold" id="cocinaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="menu-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-menu" type="button" role="tab">
                📋 Gestión de Menú
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="consolidado-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-consolidado" type="button" role="tab">
                📊 Recuento Consolidado (<?= $totalConsolidado ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reservas-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-reservas" type="button" role="tab">
                👥 Detalle de Reservas (<?= $totalReservas ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="cocinaTabsContent">

        <!-- ═══════════════════════════════════════════════════════
             PESTAÑA 1 — GESTIÓN DE MENÚ
        ═══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="tab-menu" role="tabpanel">
            <div class="row">

                <!-- Formulario lateral de NUEVO PLATILLO -->
                <div class="col-lg-4 col-12 mb-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">➕ Publicar Opción del Día</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?= BASE_URL ?>/cocina/guardar-menu"
                                  enctype="multipart/form-data">
                                <input type="hidden" name="id" value="0">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre del Platillo <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre_plato" class="form-control"
                                           placeholder="Ej. Pollo en crema" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2"
                                              placeholder="Detalles del platillo..."></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Precio (Q) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" name="precio"
                                               class="form-control" placeholder="20.00" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold">Stock Inicial <span class="text-danger">*</span></label>
                                        <input type="number" min="0" name="stock"
                                               class="form-control" placeholder="150" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Programar Publicación (Fecha y Hora) <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="hora_habilitacion"
                                           class="form-control" required>
                                    <div class="form-text text-muted small">
                                        Los empleados verán este platillo a partir de esta fecha/hora.
                                    </div>
                                </div>

                                <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha_consulta) ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fotografía</label>
                                    <input type="file" name="imagen_file" class="form-control" accept="image/*">
                                </div>

                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="es_dieta" id="chkDieta" value="1">
                                    <label class="form-check-label fw-bold" for="chkDieta">🌿 Es Dieta</label>
                                </div>

                                <button type="submit" class="btn btn-success w-100 fw-bold">
                                    💾 Publicar Menú
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tabla de platillos registrados -->
                <div class="col-lg-8 col-12">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">📋 Platillos Registrados</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Imagen</th>
                                            <th>Platillo</th>
                                            <th>Precio</th>
                                            <th>Hora de habilitación</th>
                                            <th>Stock (Disp / Total)</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($menus)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    No hay platillos registrados para esta fecha.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($menus as $m): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <?php if (!empty($m['imagen_url'])): ?>
                                                        <img src="<?= resolve_image_url($m['imagen_url']) ?>"
                                                             class="rounded"
                                                             style="width:50px;height:50px;object-fit:cover;"
                                                             alt="<?= htmlspecialchars($m['nombre_plato']) ?>">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white"
                                                             style="width:50px;height:50px;font-size:.7rem;">
                                                            Sin img
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark">
                                                        <?= htmlspecialchars($m['nombre_plato']) ?>
                                                    </span>
                                                    <?php if ($m['es_dieta']): ?>
                                                        <br><span class="badge bg-info text-dark" style="font-size:.7rem;">🌿 Dieta</span>
                                                    <?php endif; ?>
                                                    <br>
                                                    <?php if ($m['estado'] === 'Disponible'): ?>
                                                        <span class="badge bg-success" style="font-size:.7rem;">✅ Habilitado</span>
                                                    <?php elseif ($m['estado'] === 'Inactivo'): ?>
                                                        <span class="badge bg-secondary" style="font-size:.7rem;">🚫 Deshabilitado</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark" style="font-size:.7rem;">
                                                            <?= htmlspecialchars($m['estado']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-success fw-bold">
                                                    Q <?= number_format((float)$m['precio'], 2) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$m['hora_habilitacion']))) ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary px-2 py-1">
                                                        <?= (int)$m['stock'] ?> / <?= (int)$m['stock'] + (int)$m['cantidad_solicitada'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1"
                                                            onclick="abrirModalEditar(<?= (int)$m['id'] ?>)"
                                                            title="Editar">
                                                        ✏️
                                                    </button>
                                                    <?php if ($m['estado'] === 'Disponible'): ?>
                                                        <button class="btn btn-sm btn-outline-warning me-1"
                                                                onclick="cambiarEstadoPlatillo(<?= (int)$m['id'] ?>, 'Inactivo')"
                                                                title="Deshabilitar">🚫</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-success me-1"
                                                                onclick="cambiarEstadoPlatillo(<?= (int)$m['id'] ?>, 'Disponible')"
                                                                title="Habilitar">✅</button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            onclick="eliminarMenu(<?= (int)$m['id'] ?>)"
                                                            title="Eliminar">🗑️</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             PESTAÑA 2 — RECUENTO CONSOLIDADO
        ═══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-consolidado" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📊 Consolidados por Platillo Solicitado</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($consolidado)): ?>
                        <div class="row">
                            <?php foreach ($consolidado as $c): ?>
                                <?php $inactivo = ($c['estado_platillo'] === 'Inactivo'); ?>
                                <div class="col-md-4 col-12 mb-3">
                                    <div class="card <?= $inactivo ? 'border-warning' : 'border-primary' ?> h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h5 class="card-title fw-bold <?= $inactivo ? 'text-warning' : 'text-primary' ?>">
                                                    <?= htmlspecialchars($c['nombre_plato']) ?>
                                                </h5>
                                                <?php if ($inactivo): ?>
                                                    <span class="badge bg-warning text-dark">⚠️ Deshabilitado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">✅ Activo</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($c['es_dieta']): ?>
                                                <span class="badge bg-info text-dark mb-2">🌿 Dieta</span>
                                            <?php endif; ?>
                                            <p class="text-muted mb-1">
                                                Precio Unitario: Q <?= number_format((float)$c['precio'], 2) ?>
                                            </p>
                                            <?php if ($inactivo): ?>
                                                <div class="alert alert-warning py-1 px-2 mb-2 small">
                                                    <strong>Nota:</strong> Este platillo fue deshabilitado. Las reservas se mantienen.
                                                </div>
                                            <?php endif; ?>
                                            <hr>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fs-6 text-secondary">Solicitudes:</span>
                                                <span class="badge bg-success fs-5">
                                                    <?= (int)$c['total_solicitado'] ?> unidades
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="fs-6 text-secondary">Total Acumulado:</span>
                                                <span class="fw-bold text-dark fs-5">
                                                    Q <?= number_format((float)$c['total_recaudado'], 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-4 mb-0">
                            No se registran solicitudes activas para la fecha seleccionada.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             PESTAÑA 3 — DETALLE DE RESERVAS
        ═══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-reservas" role="tabpanel">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">👥 Detalle de Personas que Reservaron</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Empleado</th>
                                    <th>Plato Elegido</th>
                                    <th>Cantidad</th>
                                    <th>¿Dónde Consume?</th>
                                    <th>Forma de Pago</th>
                                    <th>Hora Reserva</th>
                                    <th>Estado Platillo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reservas_detalle)): ?>
                                    <?php $correlativo = 1; ?>
                                    <?php foreach ($reservas_detalle as $r): ?>
                                        <tr>
                                            <td><?= $correlativo++ ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($r['nombre_empleado']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r['email_empleado']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary fs-6">
                                                    <?= htmlspecialchars($r['nombre_plato']) ?>
                                                </span>
                                            </td>
                                            <td><span class="badge bg-primary fs-6"><?= (int)$r['cantidad'] ?></span></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['donde_consume']) ?></span></td>
                                            <td><span class="badge bg-success"><?= htmlspecialchars($r['forma_pago']) ?></span></td>
                                            <td><?= htmlspecialchars(substr($r['fecha_reserva'], 11, 8)) ?></td>
                                            <td>
                                                <?php if ($r['estado_platillo'] === 'Disponible'): ?>
                                                    <span class="badge bg-success">✅ Disponible</span>
                                                <?php elseif ($r['estado_platillo'] === 'Inactivo'): ?>
                                                    <span class="badge bg-warning text-dark">⚠️ Deshabilitado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($r['estado_platillo']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            Sin registros de reservas para esta fecha.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /tab-content -->
</div><!-- /container -->

<!-- ═══════════════════════════════════════════════════════
     MODAL — EDITAR PLATILLO EXISTENTE
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditarMenu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">✏️ Editar Platillo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/cocina/guardar-menu" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nombre del Platillo <span class="text-danger">*</span></label>
                            <input type="text" id="edit_nombre_plato" name="nombre_plato"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" id="edit_fecha" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea id="edit_descripcion" name="descripcion"
                                      class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Precio (Q) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="edit_precio"
                                   name="precio" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock <span class="text-danger">*</span></label>
                            <input type="number" min="0" id="edit_stock"
                                   name="stock" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hora Habilitación</label>
                            <input type="datetime-local" id="edit_hora" name="hora_habilitacion"
                                   class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Cambiar Fotografía (Opcional)</label>
                            <input type="file" name="imagen_file" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="edit_es_dieta" name="es_dieta" value="1">
                                <label class="form-check-label fw-bold" for="edit_es_dieta">
                                    🌿 Es Dieta
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL_COCINA = '<?= BASE_URL ?>';

function abrirModalEditar(id) {
    fetch(BASE_URL_COCINA + '/cocina/obtener-menu-por-id/' + id)
        .then(r => { if (!r.ok) throw new Error('Error'); return r.json(); })
        .then(data => {
            document.getElementById('edit_id').value           = data.id;
            document.getElementById('edit_nombre_plato').value = data.nombre_plato;
            document.getElementById('edit_descripcion').value  = data.descripcion || '';
            document.getElementById('edit_precio').value       = data.precio;
            document.getElementById('edit_stock').value        = data.stock;
            document.getElementById('edit_fecha').value        = data.fecha;
            document.getElementById('edit_es_dieta').checked   = !!parseInt(data.es_dieta);
            // hora_habilitacion: "YYYY-MM-DD HH:MM:SS" → "YYYY-MM-DDTHH:MM"
            if (data.hora_habilitacion) {
                document.getElementById('edit_hora').value =
                    data.hora_habilitacion.substring(0, 16).replace(' ', 'T');
            }
            new bootstrap.Modal(document.getElementById('modalEditarMenu')).show();
        })
        .catch(() => alert('No se pudo cargar los datos del platillo.'));
}

function cambiarEstadoPlatillo(id, nuevoEstado) {
    const accion    = nuevoEstado === 'Disponible' ? 'habilitar' : 'deshabilitar';
    const msg       = nuevoEstado === 'Inactivo'
        ? '¿Deshabilitar este platillo?\n\nLos empleados no podrán hacer nuevas reservas, pero las existentes se mantienen.'
        : '¿Habilitar este platillo para permitir nuevas reservas?';

    if (!confirm(msg)) return;

    fetch(BASE_URL_COCINA + '/cocina/cambiar-estado', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&nuevoEstado=' + encodeURIComponent(nuevoEstado)
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert(res.error); } else { location.reload(); }
    })
    .catch(() => alert('Error al cambiar el estado del platillo.'));
}

function eliminarMenu(id) {
    if (!confirm('¿Está seguro de eliminar este platillo?\n\nSolo es posible si no tiene reservas asociadas.')) return;

    fetch(BASE_URL_COCINA + '/cocina/eliminar-menu', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) { alert('No se puede eliminar: ' + res.error); }
        else { alert(res.mensaje); location.reload(); }
    })
    .catch(() => alert('Error al eliminar el platillo.'));
}
</script>
