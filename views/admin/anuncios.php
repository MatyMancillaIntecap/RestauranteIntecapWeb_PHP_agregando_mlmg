<?php
/**
 * Panel de Administración de Anuncios y Avisos Informativos.
 *
 * Permite crear, editar, activar/desactivar y eliminar anuncios,
 * programar rangos de fechas (inicio y fin) y monitorear su estado (activo, programado, vencido o inactivo).
 *
 * @var array<int, array<string, mixed>> $anuncios
 * @var array<string, int> $totales
 * @var array<string, array<string, string>> $presets
 */

$ahoraLocal = date('Y-m-d\TH:i');
$hoyFinLocal = date('Y-m-d\T23:59');
$semanaFinLocal = date('Y-m-d\T23:59', strtotime('+7 days'));
?>
<div class="container-fluid mt-3 mb-5">

    <!-- // BARRA SUPERIOR CON COLORES FUERTES INTECAP -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7 col-12">
                    <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>📢</span> Gestión de Anuncios y Avisos
                    </h3>
                    <p class="text-white-50 mb-0">
                        Configura avisos informativos para la pantalla de inicio y login. Controla horarios de vigencia y activación en tiempo real.
                    </p>
                </div>
                <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <a href="<?= BASE_URL ?>/account/login" target="_blank" class="btn btn-light text-primary fw-bold shadow-sm flex-fill flex-md-grow-0 text-center" title="Ver cómo se visualizan los anuncios activos en el login">
                        👁️ Probar en Login ↗
                    </a>
                    <a href="<?= BASE_URL ?>/admin/index" class="btn btn-outline-light fw-bold shadow-sm flex-fill flex-md-grow-0 text-center">
                        ← Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE INDICADORES DE ESTADO (KPIS) -->
    <div class="row g-3 mb-4">
        <!-- Activos en Login -->
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm rounded-3 p-3 h-100 border-2" style="background:#ecfdf5; border-color:#10b981 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-success text-white mb-1 fw-bold text-uppercase">🟢 Activos en Login</span>
                        <h3 class="fw-bold text-success mb-0"><?= (int)($totales['activos'] ?? 0) ?></h3>
                        <small class="text-muted">Visibles ahora en pantalla</small>
                    </div>
                    <div class="fs-1 text-success">📢</div>
                </div>
            </div>
        </div>

        <!-- Programados -->
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm rounded-3 p-3 h-100 border-2" style="background:#f0f9ff; border-color:#0284c7 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-info text-dark mb-1 fw-bold text-uppercase">⏰ Programados</span>
                        <h3 class="fw-bold text-primary mb-0"><?= (int)($totales['programados'] ?? 0) ?></h3>
                        <small class="text-muted">Iniciarán en fecha futura</small>
                    </div>
                    <div class="fs-1 text-primary">⏳</div>
                </div>
            </div>
        </div>

        <!-- Vencidos -->
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm rounded-3 p-3 h-100 border-2" style="background:#fff1f2; border-color:#f43f5e !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-danger text-white mb-1 fw-bold text-uppercase">⌛ Vencidos</span>
                        <h3 class="fw-bold text-danger mb-0"><?= (int)($totales['vencidos'] ?? 0) ?></h3>
                        <small class="text-muted">Fecha fin superada</small>
                    </div>
                    <div class="fs-1 text-danger">⚠️</div>
                </div>
            </div>
        </div>

        <!-- Desactivados -->
        <div class="col-xl-3 col-sm-6">
            <div class="card shadow-sm rounded-3 p-3 h-100 border-2" style="background:#f8fafc; border-color:#94a3b8 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary text-white mb-1 fw-bold text-uppercase">⏸️ Desactivados</span>
                        <h3 class="fw-bold text-secondary mb-0"><?= (int)($totales['inactivos'] ?? 0) ?></h3>
                        <small class="text-muted">Apagados manualmente</small>
                    </div>
                    <div class="fs-1 text-secondary">🚫</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL: FORMULARIO LATERAL + LISTADO DE ANUNCIOS -->
    <div class="row">

        <!-- ═══════════════════════════════════════════════════════
             COLUMNA LATERAL: FORMULARIO CREAR / EDITAR ANUNCIO
        ═══════════════════════════════════════════════════════ -->
        <div class="col-xl-4 col-lg-5 col-12 mb-4">
            <div class="card shadow rounded-3 border-2 sticky-panel-lg" id="cardFormularioAnuncio">
                <div class="card-header text-white py-3" id="panelFormHeader"
                     style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important;">
                    <h5 class="mb-0 fw-bold d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span id="panelFormTitulo">➕ Publicar Anuncio</span>
                        <span class="badge bg-white text-success small shadow-sm" id="badgeModoForm">Nuevo</span>
                    </h5>
                </div>
                <div class="card-body p-3 p-md-4">

                    <!-- SELECTOR DE PLANTILLAS PREDETERMINADAS -->
                    <div class="mb-3 p-2 rounded-3 border bg-light">
                        <label class="form-label fw-bold small text-dark mb-1 d-flex align-items-center gap-1">
                            <span>⚡</span> Anuncios Predeterminados:
                        </label>
                        <select id="sel_plantilla" class="form-select form-select-sm" onchange="aplicarPlantilla(this.value)">
                            <option value="personalizar">✏️ Personalizar (Escribir mensaje propio)</option>
                            <option value="servicio_carta">🍽️ Esta semana servicio a la carta, reserva martes a las 11:00 A.M.</option>
                            <option value="sin_desayuno">☕ Hoy no hay servicio de desayuno</option>
                            <option value="sin_almuerzo">🍲 Hoy no hay servicio de almuerzo</option>
                            <option value="sin_cafe">🍰 Hoy no hay servicio de café/escuela</option>
                        </select>
                        <div class="form-text small" style="font-size:0.75rem;">
                            Puedes seleccionar un aviso predeterminado y personalizar cualquier texto o fecha.
                        </div>
                    </div>

                    <form method="post" action="<?= BASE_URL ?>/admin/guardar-anuncio" id="formAnuncio">
                        <input type="hidden" id="anuncio_id" name="id" value="0">
                        <input type="hidden" id="anuncio_tipo" name="tipo" value="Personalizar">

                        <!-- Título del Anuncio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Título o Tipo de Anuncio <span class="text-danger">*</span></label>
                            <input type="text" id="anuncio_titulo" name="titulo" class="form-control form-control-sm"
                                   placeholder="Ej. Servicio a La Carta, Aviso de Menú..." required maxlength="150"
                                   oninput="actualizarVistaPrevia()">
                        </div>

                        <!-- Mensaje -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Mensaje del Anuncio <span class="text-danger">*</span></label>
                            <textarea id="anuncio_mensaje" name="mensaje" class="form-control form-control-sm" rows="3"
                                      placeholder="Escribe el aviso que verán los usuarios en la pantalla de inicio..." required
                                      oninput="actualizarVistaPrevia()"></textarea>
                        </div>

                        <!-- Rango de Fechas -->
                        <div class="card border p-3 mb-3 bg-light rounded-3">
                            <h6 class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                                <span>📅</span> Horario de Vigencia
                            </h6>
                            <div class="row g-2">
                                <div class="col-12 mb-2">
                                    <label class="form-label small fw-bold mb-1">Fecha y Hora de Inicio <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="anuncio_fecha_inicio" name="fecha_inicio"
                                           class="form-control form-control-sm" value="<?= $ahoraLocal ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-1">Fecha y Hora de Fin <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="anuncio_fecha_fin" name="fecha_fin"
                                           class="form-control form-control-sm" value="<?= $semanaFinLocal ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Estado Activo -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="anuncio_activo" name="activo" value="1" checked>
                            <label class="form-check-label fw-bold small" for="anuncio_activo">
                                Activar Anuncio (Mostrar si está en fecha)
                            </label>
                        </div>

                        <!-- VISTA PREVIA EN VIVO -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted d-flex align-items-center gap-1">
                                <span>👁️</span> Vista previa en Login:
                            </label>
                            <div class="p-3 rounded-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #f59e0b; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.15);">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span style="font-size: 0.95rem;">📢</span>
                                    <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.7rem;" id="prev_titulo">Servicio a La Carta</span>
                                </div>
                                <div class="small fw-semibold text-dark mb-0" id="prev_mensaje" style="font-size: 0.82rem; line-height: 1.35; color: #78350f !important;">
                                    Esta semana servicio a la carta, reserva martes a las 11:00 A.M.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm" id="btnGuardarAnuncio">
                            💾 Guardar Anuncio
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100 fw-bold mt-2 d-none" id="btnCancelarEdicion" onclick="cancelarEdicionAnuncio()">
                            ✖️ Cancelar Edición
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             COLUMNA PRINCIPAL: TABLA DE ANUNCIOS
        ═══════════════════════════════════════════════════════ -->
        <div class="col-xl-8 col-lg-7 col-12">
            <div class="card shadow rounded-3 mb-4 border-2">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <span>📋</span> Listado de Anuncios Registrados
                    </h5>
                    <span class="badge bg-warning text-dark fw-bold shadow-sm">
                        <?= count($anuncios) ?> Anuncio<?= count($anuncios) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($anuncios)): ?>
                        <div class="text-center py-5 text-muted">
                            <div class="fs-1 mb-2">📢</div>
                            <h6>No hay anuncios registrados actualmente.</h6>
                            <p class="small mb-0">Utiliza el formulario de la izquierda para publicar tu primer anuncio.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="cell-nowrap" style="width: 140px;">Estado</th>
                                        <th>Título y Mensaje</th>
                                        <th class="cell-nowrap" style="width: 180px;">Vigencia</th>
                                        <th class="text-center cell-nowrap" style="width: 90px;">Activo</th>
                                        <th class="text-center cell-nowrap" style="width: 160px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($anuncios as $a): ?>
                                        <?php
                                        $id = (int)$a['id'];
                                        $estadoCalc = $a['estado_calculado'];
                                        $badgeColor = $a['estado_badge'];
                                        $icono = $a['icono'];
                                        $label = $a['estado_label'];
                                        $esActivo = (bool)$a['activo'];
                                        ?>
                                        <tr id="fila-anuncio-<?= $id ?>" class="<?= ($estadoCalc === 'activo') ? 'table-success-subtle' : '' ?>">
                                            <td class="cell-nowrap">
                                                <span class="badge bg-<?= $badgeColor ?> shadow-sm d-inline-flex align-items-center gap-1" id="badge-estado-<?= $id ?>" style="font-size: 0.75rem;">
                                                    <span><?= $icono ?></span>
                                                    <span><?= htmlspecialchars($label) ?></span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark d-flex align-items-center gap-2 flex-wrap">
                                                    <span><?= htmlspecialchars($a['titulo']) ?></span>
                                                    <?php if ($a['tipo'] !== 'Personalizar'): ?>
                                                        <span class="badge bg-light text-secondary border small" style="font-size: 0.68rem;">Predeterminado</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small mt-1" style="line-height: 1.35;">
                                                    <?= nl2br(htmlspecialchars($a['mensaje'])) ?>
                                                </div>
                                            </td>
                                            <td class="small cell-nowrap">
                                                <div><strong>Inicio:</strong> <?= date('d/m/Y H:i', strtotime((string)$a['fecha_inicio'])) ?></div>
                                                <div><strong>Fin:</strong> <?= date('d/m/Y H:i', strtotime((string)$a['fecha_fin'])) ?></div>
                                            </td>
                                            <td class="text-center cell-nowrap">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           id="sw_anuncio_<?= $id ?>"
                                                           <?= $esActivo ? 'checked' : '' ?>
                                                           onchange="cambiarEstadoAnuncio(<?= $id ?>, this.checked, this)">
                                                </div>
                                            </td>
                                            <td class="text-center cell-nowrap">
                                                <div class="btn-action-group justify-content-center">
                                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold"
                                                            onclick="cargarAnuncioEnFormulario(<?= $id ?>)" title="Editar Anuncio">
                                                        ✏️ Editar
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold"
                                                            onclick="eliminarAnuncio(<?= $id ?>, '<?= htmlspecialchars($a['titulo'], ENT_QUOTES) ?>')" title="Eliminar Anuncio">
                                                        🗑️ Borrar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TARJETA INFORMATIVA SOBRE VISIBILIDAD -->
            <div class="alert alert-info border-info d-flex align-items-center gap-3 py-3 shadow-sm rounded-3">
                <span class="fs-2">💡</span>
                <div class="small">
                    <strong>¿Cómo funciona la visualización en el Login?</strong><br>
                    Un anuncio <strong>únicamente se muestra</strong> cuando el interruptor está <span class="badge bg-success">Activo</span> y la fecha y hora actual se encuentra dentro del rango de inicio y fin. Si no existen anuncios activos, el espacio de anuncios se oculta automáticamente.
                </div>
            </div>
        </div>

    </div><!-- /row -->

</div><!-- /container-fluid -->

<script>
    const baseUrl = '<?= BASE_URL ?>';

    const plantillas = {
        'servicio_carta': {
            'titulo': 'Servicio a La Carta',
            'mensaje': 'Esta semana servicio a la carta, reserva martes a las 11:00 A.M.',
            'tipo': 'Esta semana servicio a la carta, reserva martes a las 11:00 A.M.'
        },
        'sin_desayuno': {
            'titulo': 'Aviso de Desayuno',
            'mensaje': 'Hoy no hay servicio de desayuno',
            'tipo': 'Hoy no hay servicio de desayuno'
        },
        'sin_almuerzo': {
            'titulo': 'Aviso de Almuerzo',
            'mensaje': 'Hoy no hay servicio de almuerzo',
            'tipo': 'Hoy no hay servicio de almuerzo'
        },
        'sin_cafe': {
            'titulo': 'Aviso Café / Escuela',
            'mensaje': 'Hoy no hay servicio de café/escuela',
            'tipo': 'Hoy no hay servicio de café/escuela'
        },
        'personalizar': {
            'titulo': '',
            'mensaje': '',
            'tipo': 'Personalizar'
        }
    };

    function aplicarPlantilla(clave) {
        if (plantillas[clave]) {
            const data = plantillas[clave];
            if (clave !== 'personalizar') {
                document.getElementById('anuncio_titulo').value = data.titulo;
                document.getElementById('anuncio_mensaje').value = data.mensaje;
                document.getElementById('anuncio_tipo').value = data.tipo;
            } else {
                document.getElementById('anuncio_tipo').value = 'Personalizar';
            }
            actualizarVistaPrevia();
        }
    }

    function actualizarVistaPrevia() {
        const tit = document.getElementById('anuncio_titulo').value.trim() || 'Título del Anuncio';
        const msg = document.getElementById('anuncio_mensaje').value.trim() || 'Mensaje del aviso visible en el login.';
        document.getElementById('prev_titulo').textContent = tit;
        document.getElementById('prev_mensaje').textContent = msg;
    }

    function cargarAnuncioEnFormulario(id) {
        fetch(baseUrl + '/admin/obtener-anuncio-por-id/' + id)
            .then(res => {
                if (!res.ok) throw new Error('No se pudo cargar el anuncio');
                return res.json();
            })
            .then(data => {
                // Actualizar encabezado a Modo Edición
                document.getElementById('panelFormTitulo').innerHTML = '✏️ Editar Anuncio';
                const badge = document.getElementById('badgeModoForm');
                if (badge) {
                    badge.className = 'badge bg-warning text-dark small shadow-sm';
                    badge.textContent = 'Edición';
                }
                const header = document.getElementById('panelFormHeader');
                if (header) {
                    header.style.setProperty('background', 'linear-gradient(135deg, #b45309 0%, #d97706 100%)', 'important');
                }

                document.getElementById('anuncio_id').value = data.id;
                document.getElementById('anuncio_tipo').value = data.tipo || 'Personalizar';
                document.getElementById('anuncio_titulo').value = data.titulo;
                document.getElementById('anuncio_mensaje').value = data.mensaje;

                // Formatear fechas para input datetime-local (YYYY-MM-DDTHH:MM)
                if (data.fecha_inicio) {
                    document.getElementById('anuncio_fecha_inicio').value = data.fecha_inicio.substring(0, 16).replace(' ', 'T');
                }
                if (data.fecha_fin) {
                    document.getElementById('anuncio_fecha_fin').value = data.fecha_fin.substring(0, 16).replace(' ', 'T');
                }

                document.getElementById('anuncio_activo').checked = (parseInt(data.activo) === 1);

                // Seleccionar plantilla correspondiente si coincide
                const sel = document.getElementById('sel_plantilla');
                let coincidio = false;
                for (let k in plantillas) {
                    if (plantillas[k].tipo === data.tipo) {
                        sel.value = k;
                        coincidio = true;
                        break;
                    }
                }
                if (!coincidio) sel.value = 'personalizar';

                const btnGuardar = document.getElementById('btnGuardarAnuncio');
                if (btnGuardar) {
                    btnGuardar.innerHTML = '💾 Actualizar Anuncio';
                    btnGuardar.className = 'btn btn-warning text-dark w-100 fw-bold py-2 shadow-sm';
                }

                const btnCancel = document.getElementById('btnCancelarEdicion');
                if (btnCancel) {
                    btnCancel.classList.remove('d-none');
                }

                actualizarVistaPrevia();

                const cardForm = document.getElementById('cardFormularioAnuncio');
                if (cardForm) {
                    cardForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                const inputTitulo = document.getElementById('anuncio_titulo');
                if (inputTitulo) {
                    setTimeout(() => inputTitulo.focus(), 300);
                }
            })
            .catch(err => {
                alert('Error al obtener la información del anuncio.');
            });
    }

    function cancelarEdicionAnuncio() {
        document.getElementById('anuncio_id').value = '0';
        document.getElementById('anuncio_tipo').value = 'Personalizar';
        document.getElementById('anuncio_titulo').value = '';
        document.getElementById('anuncio_mensaje').value = '';
        document.getElementById('anuncio_fecha_inicio').value = '<?= $ahoraLocal ?>';
        document.getElementById('anuncio_fecha_fin').value = '<?= $semanaFinLocal ?>';
        document.getElementById('anuncio_activo').checked = true;
        document.getElementById('sel_plantilla').value = 'personalizar';

        const titulo = document.getElementById('panelFormTitulo');
        if (titulo) titulo.innerHTML = '➕ Publicar Anuncio';

        const badge = document.getElementById('badgeModoForm');
        if (badge) {
            badge.className = 'badge bg-white text-success small shadow-sm';
            badge.textContent = 'Nuevo';
        }

        const header = document.getElementById('panelFormHeader');
        if (header) {
            header.style.setProperty('background', 'linear-gradient(135deg, #15803d 0%, #16a34a 100%)', 'important');
        }

        const btnGuardar = document.getElementById('btnGuardarAnuncio');
        if (btnGuardar) {
            btnGuardar.innerHTML = '💾 Guardar Anuncio';
            btnGuardar.className = 'btn btn-success w-100 fw-bold py-2 shadow-sm';
        }

        const btnCancel = document.getElementById('btnCancelarEdicion');
        if (btnCancel) {
            btnCancel.classList.add('d-none');
        }

        actualizarVistaPrevia();
    }

    function cambiarEstadoAnuncio(id, activo, switchElem) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('activo', activo ? '1' : '0');

        fetch(baseUrl + '/admin/cambiar-estado-anuncio', {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                location.reload();
            } else {
                switchElem.checked = !activo;
                alert('No se pudo actualizar el estado del anuncio.');
            }
        })
        .catch(err => {
            switchElem.checked = !activo;
            alert('Error de red al actualizar el estado.');
        });
    }

    function eliminarAnuncio(id, titulo) {
        if (!confirm('¿Confirma que desea eliminar el anuncio "' + titulo + '"? Esta acción no se puede deshacer.')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        fetch(baseUrl + '/admin/eliminar-anuncio', {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                location.reload();
            } else {
                alert(data.error || 'No se pudo eliminar el anuncio.');
            }
        })
        .catch(err => {
            alert('Ocurrió un error al contactar al servidor.');
        });
    }

    // Inicializar vista previa al cargar
    document.addEventListener('DOMContentLoaded', () => {
        actualizarVistaPrevia();
    });
</script>
