<?php
/**
 * Gestion de usuarios: busqueda, tabla, estado AJAX y formularios de alta y edicion.
 *
 * @var array $usuarios
 * @var string $email_busqueda
 * @var array $roles
 * @var int $solicitudes_pendientes
 */
?>
<div class="container-fluid mt-3 mb-5">

    <!-- BARRA SUPERIOR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body bg-light p-3">
            <div class="row align-items-center">
                <div class="col-md-6 col-12">
                    <h3 class="text-primary fw-bold mb-1">⚙️ Gestión General de Usuarios</h3>
                    <p class="text-muted mb-0">
                        Administra accesos, roles, estados, límites de platillos y NITs del personal.
                    </p>
                </div>
                <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <a href="<?= BASE_URL ?>/admin/descargar-usuarios-excel"
                       class="btn btn-success fw-bold">
                        📊 Exportar Excel
                    </a>
                    <a href="<?= BASE_URL ?>/admin/descargar-usuarios-pdf"
                       class="btn btn-outline-danger fw-bold">
                        📄 PDF
                    </a>
                    <button class="btn btn-primary fw-bold" onclick="abrirModalNuevoUsuario()">
                        ➕ Crear Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BUSCADOR POR CORREO -->
    <div class="row mb-3">
        <div class="col-md-6 col-12">
            <form method="get" action="<?= BASE_URL ?>/admin/usuarios" class="d-flex gap-2">
                <input type="email" name="correo" class="form-control"
                       placeholder="Buscar por correo electrónico..."
                       value="<?= htmlspecialchars($email_busqueda ?? '') ?>">
                <button type="submit" class="btn btn-outline-primary">Buscar</button>
                <a href="<?= BASE_URL ?>/admin/usuarios" class="btn btn-outline-secondary">Limpiar</a>
            </form>
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"># ID</th>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
                            <th>Rol</th>
                            <th>Límite Almuerzos</th>
                            <th>NIT Facturación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se registran usuarios en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr id="fila-usuario-<?= (int)$u['id'] ?>">
                                <td class="ps-3"><strong>#<?= (int)$u['id'] ?></strong></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($u['nombre']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($u['nombre_rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary fs-6">
                                        <?= (int)$u['max_almuerzos'] === 0
                                            ? 'Ilimitado'
                                            : (int)$u['max_almuerzos'] . ' / día' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($u['nit_facturacion']) ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Toggle switch AJAX (como en el C#) -->
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="sw_<?= (int)$u['id'] ?>"
                                               <?= $u['activo'] ? 'checked' : '' ?>
                                               onchange="cambiarEstadoUsuario(<?= (int)$u['id'] ?>, this.checked, this)">
                                        <label class="form-check-label small fw-bold"
                                               id="lbl_sw_<?= (int)$u['id'] ?>"
                                               for="sw_<?= (int)$u['id'] ?>">
                                            <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /container -->

<!-- ═══════════════════════════════════════════════════════
     MODAL — CREAR / EDITAR USUARIO
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTitulo">➕ Crear Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/admin/guardar-usuario">
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="id" value="0">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" id="user_nombre" name="nombre" class="form-control"
                               placeholder="Ej. Carlos López" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" id="user_email" name="email" class="form-control"
                               placeholder="ejemplo@intecap.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">🔐 Contraseña</label>
                        <input type="password" id="user_password" name="password" class="form-control"
                               autocomplete="new-password"
                               placeholder="Nuevo usuario: se asigna 12345678 | Edición: dejar en blanco">
                        <small class="text-muted d-block mt-1" id="help_password">
                            <strong>Nuevo usuario:</strong> Se asignará automáticamente <code>12345678</code><br>
                            <strong>Editar usuario:</strong> Dejar en blanco para no cambiar
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Rol <span class="text-danger">*</span></label>
                            <select id="user_rol" name="rol_id" class="form-select" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= (int)$rol['id'] ?>">
                                        <?= htmlspecialchars($rol['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Límite Almuerzos/Día</label>
                            <input type="number" id="user_max_almuerzos" name="max_almuerzos"
                                   class="form-control" min="0" max="50" value="2" required>
                            <small class="text-muted">Coloque 0 para Ilimitado</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">NIT Predeterminado</label>
                        <input type="text" id="user_nit" name="nit_facturacion"
                               class="form-control" placeholder="13 dígitos o C/F" value="C/F">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox"
                               id="user_activo" name="activo" value="1" checked>
                        <label class="form-check-label fw-bold" for="user_activo">
                            Usuario Activo
                        </label>
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
const BASE_URL_ADMIN = '<?= BASE_URL ?>';

function abrirModalNuevoUsuario() {
    document.getElementById('modalTitulo').textContent      = '➕ Crear Usuario';
    document.getElementById('user_id').value                = 0;
    document.getElementById('user_nombre').value            = '';
    document.getElementById('user_email').value             = '';
    document.getElementById('user_password').value          = '';
    document.getElementById('user_max_almuerzos').value     = 2;
    document.getElementById('user_nit').value               = 'C/F';
    document.getElementById('user_activo').checked          = true;
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}

function abrirModalEditarUsuario(id) {
    fetch(BASE_URL_ADMIN + '/admin/obtener-usuario-por-id/' + id)
        .then(r => { if (!r.ok) throw new Error('Error'); return r.json(); })
        .then(data => {
            document.getElementById('modalTitulo').textContent  = '✏️ Editar Usuario';
            document.getElementById('user_id').value            = data.id;
            document.getElementById('user_nombre').value        = data.nombre;
            document.getElementById('user_email').value         = data.email;
            document.getElementById('user_password').value      = '';
            document.getElementById('user_rol').value           = data.rol_id;
            document.getElementById('user_max_almuerzos').value = data.max_almuerzos;
            document.getElementById('user_nit').value           = data.nit_facturacion;
            document.getElementById('user_activo').checked      = !!parseInt(data.activo);
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        })
        .catch(() => alert('Error al obtener los datos del usuario.'));
}

// Toggle AJAX (igual que C# cambiarEstadoUsuario con $.post)
function cambiarEstadoUsuario(id, activo, checkbox) {
    fetch(BASE_URL_ADMIN + '/admin/cambiar-estado-usuario', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&activo=' + (activo ? '1' : '')
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) {
            alert('Error al cambiar el estado del usuario.');
            checkbox.checked = !activo; // revertir
        } else {
            const lbl = document.getElementById('lbl_sw_' + id);
            if (lbl) lbl.textContent = activo ? 'Activo' : 'Inactivo';
        }
    })
    .catch(() => {
        alert('Error al cambiar el estado del usuario.');
        checkbox.checked = !activo;
    });
}

function eliminarUsuario(id) {
    if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción eliminará también sus reservas e historial asociado.')) {
        return;
    }

    fetch(BASE_URL_ADMIN + '/admin/eliminar-usuario', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) {
            alert(res.error);
            return;
        }

        document.getElementById('fila-usuario-' + id)?.remove();
    })
    .catch(() => alert('No se pudo eliminar el usuario.'));
}
</script>
