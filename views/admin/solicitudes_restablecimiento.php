<?php
/**
 * Bandeja de solicitudes de restablecimiento separada por estado.
 *
 * @var array $solicitudes
 * @var int $solicitudes_pendientes
 */
?>
<div class="container-fluid mt-3 mb-5">

    <!-- BARRA SUPERIOR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body bg-light p-3">
            <div class="row align-items-center">
                <div class="col-md-8 col-12">
                    <h3 class="text-primary fw-bold mb-1">🔐 Solicitudes de Restablecimiento de Contraseña</h3>
                    <p class="text-muted mb-0">
                        Revisa las solicitudes pendientes y asigna la contraseña temporal de forma segura.
                    </p>
                </div>
                <div class="col-md-4 col-12 text-md-end mt-2 mt-md-0">
                    <span class="badge bg-warning text-dark fs-6 p-2">
                        Pendientes: <?= (int)$solicitudes_pendientes ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Separar estados permite mostrar la accion solo en solicitudes pendientes.
    $pendientes  = array_filter($solicitudes, fn($s) => $s['estado'] === 'Pendiente');
    $realizadas  = array_filter($solicitudes, fn($s) => in_array($s['estado'], ['Realizado', 'Atendida']));
    ?>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN: SOLICITUDES PENDIENTES
    ═══════════════════════════════════════════════════════ -->
    <?php if (!empty($pendientes)): ?>
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-warning text-dark fw-bold py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-5">📋 SOLICITUDES PENDIENTES</span>
                    <span class="badge bg-danger"><?= count($pendientes) ?> pendiente<?= count($pendientes) > 1 ? 's' : '' ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Fecha Solicitud</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $s): ?>
                                <tr class="table-warning">
                                    <td><strong>#<?= (int)$s['id'] ?></strong></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['nombre_usuario']) ?></td>
                                    <td><?= htmlspecialchars($s['email_usuario']) ?></td>
                                    <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                                    <td><span class="badge bg-warning text-dark">📋 Pendiente</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN: SOLICITUDES REALIZADAS
    ═══════════════════════════════════════════════════════ -->
    <?php if (!empty($realizadas)): ?>
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-success text-white fw-bold py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-5">✅ SOLICITUDES REALIZADAS</span>
                    <span class="badge bg-dark"><?= count($realizadas) ?> realizada<?= count($realizadas) > 1 ? 's' : '' ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Fecha Solicitud</th>
                                <th>Fecha Atención</th>
                                <th>Atendido por</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($realizadas as $s): ?>
                                <tr>
                                    <td><strong>#<?= (int)$s['id'] ?></strong></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['nombre_usuario']) ?></td>
                                    <td><?= htmlspecialchars($s['email_usuario']) ?></td>
                                    <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                                    <td><?= !empty($s['fecha_atencion']) ? htmlspecialchars($s['fecha_atencion']) : '—' ?></td>
                                    <td><?= htmlspecialchars($s['nombre_admin_atendio'] ?? '—') ?></td>
                                    <td><span class="badge bg-success">✅ Realizado</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sin solicitudes -->
    <?php if (empty($pendientes) && empty($realizadas)): ?>
        <div class="card shadow-sm border-0 rounded-3 text-center">
            <div class="card-body p-5">
                <div class="fs-4 mb-3">📭</div>
                <h5 class="text-muted">No hay solicitudes de restablecimiento registradas.</h5>
                <p class="text-muted mb-0">
                    Los usuarios pueden solicitar el restablecimiento de contraseña desde la pantalla de login.
                </p>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /container -->

<!-- ═══════════════════════════════════════════════════════
     MODAL — CONFIRMAR RESTABLECIMIENTO
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAtencionSolicitud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">🔐 Restablecer Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/admin/atender-solicitud-restablecimiento">
                <div class="modal-body">
                    <input type="hidden" name="solicitud_id" id="modal_solicitud_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">👤 Usuario</label>
                        <input type="text" id="modal_usuario" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">📧 Correo</label>
                        <input type="text" id="modal_correo" class="form-control" readonly>
                    </div>
                    <div class="alert alert-info small" role="alert">
                        <strong>ℹ️ Información:</strong><br>
                        La contraseña será restablecida automáticamente a <code>87654321</code>.<br>
                        El usuario podrá cambiarla después de iniciar sesión.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        ✅ Confirmar Restablecimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAtencion(id, usuario, correo) {
    document.getElementById('modal_solicitud_id').value = id;
    document.getElementById('modal_usuario').value      = usuario;
    document.getElementById('modal_correo').value       = correo;
    new bootstrap.Modal(document.getElementById('modalAtencionSolicitud')).show();
}
</script>
