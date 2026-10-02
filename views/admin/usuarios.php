<?php
/**
 * Gestion de usuarios: busqueda, tabla, estado AJAX y formularios de alta y edicion.
 *
 * @var array $usuarios
 * @var string $email_busqueda
 * @var array $roles
 */
?>
<div class="container-fluid mt-3 mb-5">

    <!-- // BARRA SUPERIOR CON COLORES FUERTES INTECAP -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6 col-12">
                    <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>⚙️</span> Gestión General de Usuarios
                    </h3>
                    <p class="text-white-50 mb-0">
                        Administra accesos, roles, estados, límites de platillos y NITs del personal.
                    </p>
                </div>
                <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <a href="<?= BASE_URL ?>/admin/descargar-usuarios-excel"
                       class="btn btn-success fw-bold shadow-sm">
                        📊 Exportar Excel
                    </a>
                    <a href="<?= BASE_URL ?>/admin/descargar-usuarios-pdf"
                       class="btn btn-danger fw-bold shadow-sm">
                        📄 PDF
                    </a>
                    <button class="btn btn-warning text-dark fw-bold shadow-sm" onclick="abrirModalNuevoUsuario()">
                        ➕ Crear Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- // BUSCADOR POR CORREO ENMARCADO CON COLOR -->
    <div class="card shadow-sm mb-4 rounded-3 border-2" style="background: #f8fafc; border: 2px solid #cbd5e1 !important;">
        <div class="card-body p-3">
            <form method="get" action="<?= BASE_URL ?>/admin/usuarios" class="row g-2 align-items-center">
                <div class="col-md-6 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-2">🔍</span>
                        <input type="email" name="correo" class="form-control"
                               placeholder="Buscar usuario por correo electrónico..."
                               value="<?= htmlspecialchars($email_busqueda ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm flex-grow-1 flex-md-grow-0">Buscar</button>
                    <a href="<?= BASE_URL ?>/admin/usuarios" class="btn btn-outline-secondary fw-bold flex-grow-1 flex-md-grow-0">Limpiar Filtro</a>
                </div>
            </form>
        </div>
    </div>

    <!-- // TABLA DE USUARIOS CON ENCABEZADO VIBRANTE -->
    <div class="card shadow rounded-3 border-2">
        <div class="card-header text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                <span>👥</span> Padrón General de Usuarios Registrados
            </h5>
            <span class="badge bg-warning text-dark fw-bold shadow-sm"><?= count($usuarios) ?> usuarios</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive border-0">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
                            <th class="cell-nowrap">Teléfono</th>
                            <th class="cell-nowrap">Rol</th>
                            <th class="cell-nowrap text-center">Límite Almuerzos</th>
                            <th class="cell-nowrap text-center">NIT Facturación</th>
                            <th class="cell-nowrap text-center">Estado</th>
                            <th class="text-center cell-nowrap pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se registran usuarios en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr id="fila-usuario-<?= (int)$u['id'] ?>">
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($u['nombre']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td class="cell-nowrap"><?= htmlspecialchars($u['telefono'] ?? '') ?: '<span class="text-muted">Sin registrar</span>' ?></td>
                                <td class="cell-nowrap">
                                    <?php
                                    $bgRol = match($u['nombre_rol']) {
                                        'Administrador' => 'background: #7c3aed; color: #fff;',
                                        'Cocina'        => 'background: #ea580c; color: #fff;',
                                        default         => 'background: #0284c7; color: #fff;',
                                    };
                                    ?>
                                    <span class="badge fw-bold shadow-sm" style="<?= $bgRol ?>">
                                        <?= htmlspecialchars($u['nombre_rol']) ?>
                                    </span>
                                </td>
                                <td class="cell-nowrap text-center">
                                    <span class="badge bg-secondary fs-6">
                                        <?= (int)$u['max_almuerzos'] === 0
                                            ? 'Ilimitado'
                                            : (int)$u['max_almuerzos'] . ' / día' ?>
                                    </span>
                                </td>
                                <td class="cell-nowrap text-center">
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($u['nit_facturacion']) ?>
                                    </span>
                                </td>
                                <td class="cell-nowrap text-center">
                                    <!-- Toggle switch AJAX -->
                                    <div class="form-check form-switch d-inline-block">
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
                                <td class="text-center cell-nowrap pe-3">
                                    <div class="btn-action-group justify-content-center">
                                        <a href="<?= BASE_URL ?>/admin/detalle-usuario/<?= (int)$u['id'] ?>"
                                           class="btn btn-sm btn-outline-info fw-bold" title="Ver Ficha Completa">
                                            🔍 Detalle
                                        </a>
                                        <button class="btn btn-sm btn-outline-primary fw-bold"
                                                onclick="abrirModalEditarUsuario(<?= (int)$u['id'] ?>)"
                                                title="Editar Usuario">
                                            ✏️ Editar
                                        </button>
                                        <?php if ((int) $u['id'] !== Auth::id()): ?>
                                            <button class="btn btn-sm btn-outline-danger fw-bold"
                                                    onclick="eliminarUsuario(<?= (int)$u['id'] ?>)"
                                                    title="Eliminar Usuario">
                                                🗑️ Eliminar
                                            </button>
                                        <?php endif; ?>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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
                        <label class="form-label fw-bold">Número de Teléfono</label>
                        <input type="text" id="user_telefono" name="telefono" class="form-control"
                               placeholder="Ej. 5555-1234">
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
    document.getElementById('user_telefono').value          = '';
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
            document.getElementById('user_telefono').value      = data.telefono || '';
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
