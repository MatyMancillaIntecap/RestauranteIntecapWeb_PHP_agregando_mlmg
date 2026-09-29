<?php
/**
 * Vista de La Carta para usuarios y clientes.
 *
 * Muestra los productos disponibles organizados por las 4 categorías:
 * Entrada, Plato fuerte, Bebida y Postre.
 * Permite seleccionar como máximo 1 producto por categoría y calcula
 * el total acumulado en tiempo real con validación del lado del servidor.
 *
 * @var array<string, array<int, array<string, mixed>>> $productosPorCategoria
 * @var array<int, string> $categorias
 * @var bool $esAdmin
 */

$iconosCategorias = [
    'Entrada' => '🥗',
    'Plato fuerte' => '🥩',
    'Bebida' => '🥤',
    'Postre' => '🍰',
];

$coloresCategorias = [
    'Entrada' => 'border-success',
    'Plato fuerte' => 'border-danger',
    'Bebida' => 'border-info',
    'Postre' => 'border-warning',
];
?>
<div class="container-fluid mt-3 mb-5">

    <!-- BARRA SUPERIOR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body bg-light p-3">
            <div class="row align-items-center">
                <div class="col-md-8 col-12">
                    <h3 class="text-primary fw-bold mb-1">📖 La Carta del Restaurante</h3>
                    <p class="text-muted mb-0">
                        Selecciona libremente los platillos de tu preferencia (máximo 1 por categoría). El total se calcula automáticamente.
                    </p>
                </div>
                <div class="col-md-4 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
                    <?php if ($esAdmin): ?>
                        <a href="<?= BASE_URL ?>/carta/admin" class="btn btn-warning fw-bold text-dark">
                            ⚙️ Administrar Productos
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary fw-bold" onclick="limpiarSeleccion()">
                        🧹 Limpiar Selección
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL: CATEGORÍAS (COL-LG-8) + RESUMEN STICKY (COL-LG-4) -->
    <div class="row g-4">

        <!-- LISTADO DE CATEGORÍAS Y PRODUCTOS -->
        <div class="col-lg-8 col-12">

            <?php foreach ($categorias as $cat): ?>
                <?php
                $catKey = strtolower(str_replace(' ', '_', $cat));
                $productos = $productosPorCategoria[$cat] ?? [];
                $icono = $iconosCategorias[$cat] ?? '🍽️';
                $bordeColor = $coloresCategorias[$cat] ?? 'border-primary';
                ?>

                <div class="card shadow-sm border-0 rounded-3 mb-4 category-section" id="seccion-<?= $catKey ?>">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4"><?= $icono ?></span>
                            <h4 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;"><?= htmlspecialchars($cat) ?></h4>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill small">
                            Máx. 1 opción
                        </span>
                    </div>

                    <div class="card-body p-3">
                        <div class="row g-3">

                            <!-- OPCIÓN: NINGUNA / DESELECCIONAR -->
                            <div class="col-12 col-md-6 col-xl-4">
                                <label class="card h-100 border-2 rounded-3 p-3 option-card selected-default cursor-pointer text-center d-flex flex-column justify-content-center align-items-center bg-light"
                                       id="card_opt_none_<?= $catKey ?>"
                                       style="cursor: pointer; transition: all 0.2s ease;">
                                    <input type="radio"
                                           name="sel_<?= $catKey ?>"
                                           value="0"
                                           data-categoria="<?= htmlspecialchars($cat) ?>"
                                           data-key="<?= $catKey ?>"
                                           data-nombre="Ninguna"
                                           data-precio="0"
                                           class="form-check-input visually-hidden radio-carta"
                                           checked
                                           onchange="actualizarSeleccion()">
                                    <div class="fs-3 text-muted mb-1">🚫</div>
                                    <div class="fw-bold text-dark">Sin <?= htmlspecialchars($cat) ?></div>
                                    <small class="text-muted">Q 0.00</small>
                                </label>
                            </div>

                            <!-- PRODUCTOS DE LA CATEGORÍA -->
                            <?php if (empty($productos)): ?>
                                <div class="col-12 col-md-6 col-xl-8 d-flex align-items-center">
                                    <div class="alert alert-light border w-100 mb-0 text-muted small text-center">
                                        No hay productos disponibles en esta categoría por el momento.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($productos as $p): ?>
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <label class="card h-100 border-2 rounded-3 option-card cursor-pointer shadow-sm position-relative overflow-hidden"
                                               id="card_opt_<?= (int)$p['id'] ?>"
                                               style="cursor: pointer; transition: all 0.2s ease;">
                                            <input type="radio"
                                                   name="sel_<?= $catKey ?>"
                                                   value="<?= (int)$p['id'] ?>"
                                                   data-categoria="<?= htmlspecialchars($cat) ?>"
                                                   data-key="<?= $catKey ?>"
                                                   data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                                   data-precio="<?= (float)$p['precio'] ?>"
                                                   class="form-check-input visually-hidden radio-carta"
                                                   onchange="actualizarSeleccion()">

                                            <!-- IMAGEN DEL PRODUCTO -->
                                            <?php if (!empty($p['imagen'])): ?>
                                                <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>"
                                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                                     class="card-img-top"
                                                     style="height: 150px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center text-secondary"
                                                     style="height: 150px;">
                                                    <span class="fs-1"><?= $icono ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- BADGE DE SELECCIÓN -->
                                            <div class="selection-badge position-absolute top-0 end-0 m-2 d-none">
                                                <span class="badge bg-success shadow-sm">✓ Seleccionado</span>
                                            </div>

                                            <div class="card-body p-3 d-flex flex-column">
                                                <h6 class="card-title fw-bold text-dark mb-1">
                                                    <?= htmlspecialchars($p['nombre']) ?>
                                                </h6>
                                                <?php if (!empty($p['descripcion'])): ?>
                                                    <p class="card-text text-muted small mb-3 flex-grow-1" style="font-size: 0.85rem;">
                                                        <?= htmlspecialchars($p['descripcion']) ?>
                                                    </p>
                                                <?php else: ?>
                                                    <div class="flex-grow-1"></div>
                                                <?php endif; ?>

                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                    <span class="text-muted small">Precio:</span>
                                                    <span class="fs-6 fw-bold text-primary">
                                                        Q <?= number_format((float)$p['precio'], 2) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

        <!-- RESUMEN DE SELECCIÓN Y TOTAL (STICKY SIDEBAR) -->
        <div class="col-lg-4 col-12">
            <div class="card shadow-sm border-0 rounded-3 sticky-top" style="top: 1.5rem; z-index: 10;">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <span>🧾</span> Resumen de Selección
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Tu combinación actual de La Carta. Las categorías no seleccionadas contabilizan <strong>Q 0.00</strong>.
                    </p>

                    <!-- DESGLOSE POR CATEGORÍA -->
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <span class="me-1">🥗</span> <strong>Entrada:</strong>
                                <div class="small text-muted" id="txt-nombre-entrada">No seleccionada</div>
                            </div>
                            <span class="fw-bold text-dark" id="txt-precio-entrada">Q 0.00</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <span class="me-1">🥩</span> <strong>Plato fuerte:</strong>
                                <div class="small text-muted" id="txt-nombre-plato_fuerte">No seleccionado</div>
                            </div>
                            <span class="fw-bold text-dark" id="txt-precio-plato_fuerte">Q 0.00</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <span class="me-1">🥤</span> <strong>Bebida:</strong>
                                <div class="small text-muted" id="txt-nombre-bebida">No seleccionada</div>
                            </div>
                            <span class="fw-bold text-dark" id="txt-precio-bebida">Q 0.00</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <span class="me-1">🍰</span> <strong>Postre:</strong>
                                <div class="small text-muted" id="txt-nombre-postre">No seleccionado</div>
                            </div>
                            <span class="fw-bold text-dark" id="txt-precio-postre">Q 0.00</span>
                        </li>
                    </ul>

                    <!-- TOTAL ACUMULADO -->
                    <div class="p-3 bg-light rounded-3 border text-center mb-3">
                        <span class="text-uppercase small fw-bold text-muted d-block">TOTAL ACUMULADO</span>
                        <div class="fs-2 fw-bold text-success mt-1" id="lbl-total-acumulado">
                            Q 0.00
                        </div>
                    </div>

                    <!-- BOTÓN PARA VALIDAR CON BACKEND -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold py-2" onclick="validarTotalConServidor()">
                            ✅ Validar Selección con el Sistema
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarSeleccion()">
                            Desmarcar todo
                        </button>
                    </div>

                    <!-- MENSAJE DE VALIDACIÓN DEL SERVIDOR -->
                    <div id="box-validacion-servidor" class="mt-3 d-none"></div>

                </div>
            </div>
        </div>

    </div>

</div>

<!-- ESTILOS VISUALES PARA SELECCIÓN DE TARJETAS -->
<style>
    .option-card:hover {
        border-color: #215ca8 !important;
        transform: translateY(-2px);
    }
    .option-card.card-selected {
        border-color: #198754 !important;
        background-color: #f0fdf4 !important;
        box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.15) !important;
    }
    .option-card.card-selected-none {
        border-color: #6c757d !important;
        background-color: #f8f9fa !important;
    }
</style>

<!-- JAVASCRIPT DE CÁLCULO Y VALIDACIÓN -->
<script>
    // Actualiza en tiempo real el cálculo del total y el estado visual de las tarjetas
    function actualizarSeleccion() {
        const categorias = ['entrada', 'plato_fuerte', 'bebida', 'postre'];
        let total = 0.0;

        // Ocultar mensaje previo de validación si cambia la selección
        const boxValidacion = document.getElementById('box-validacion-servidor');
        if (boxValidacion) {
            boxValidacion.classList.add('d-none');
        }

        // Limpiar clases de todas las tarjetas
        document.querySelectorAll('.option-card').forEach(card => {
            card.classList.remove('card-selected', 'card-selected-none');
            const badge = card.querySelector('.selection-badge');
            if (badge) badge.classList.add('d-none');
        });

        categorias.forEach(cat => {
            const radioSeleccionado = document.querySelector(`input[name="sel_${cat}"]:checked`);
            const txtNombre = document.getElementById(`txt-nombre-${cat}`);
            const txtPrecio = document.getElementById(`txt-precio-${cat}`);

            if (radioSeleccionado) {
                const id = parseInt(radioSeleccionado.value) || 0;
                const precio = parseFloat(radioSeleccionado.dataset.precio) || 0;
                const nombre = radioSeleccionado.dataset.nombre || 'Ninguno';

                // Marcar tarjeta seleccionada visualmente
                const card = radioSeleccionado.closest('.option-card');
                if (card) {
                    if (id === 0) {
                        card.classList.add('card-selected-none');
                    } else {
                        card.classList.add('card-selected');
                        const badge = card.querySelector('.selection-badge');
                        if (badge) badge.classList.remove('d-none');
                    }
                }

                // Actualizar textos del resumen
                if (id === 0) {
                    txtNombre.textContent = 'No seleccionado';
                    txtNombre.className = 'small text-muted';
                    txtPrecio.textContent = 'Q 0.00';
                } else {
                    txtNombre.textContent = nombre;
                    txtNombre.className = 'small fw-bold text-dark';
                    txtPrecio.textContent = 'Q ' + precio.toFixed(2);
                    total += precio;
                }
            }
        });

        // Actualizar total visual acumulado
        const lblTotal = document.getElementById('lbl-total-acumulado');
        if (lblTotal) {
            lblTotal.textContent = 'Q ' + total.toFixed(2);
        }
    }

    // Restablece todas las categorías a "Sin selección"
    function limpiarSeleccion() {
        const categorias = ['entrada', 'plato_fuerte', 'bebida', 'postre'];
        categorias.forEach(cat => {
            const radioNone = document.querySelector(`input[name="sel_${cat}"][value="0"]`);
            if (radioNone) {
                radioNone.checked = true;
            }
        });
        actualizarSeleccion();
    }

    // Envía la selección al backend para su estricta revalidación
    function validarTotalConServidor() {
        const payload = {
            entrada_id: document.querySelector('input[name="sel_entrada"]:checked')?.value || 0,
            plato_fuerte_id: document.querySelector('input[name="sel_plato_fuerte"]:checked')?.value || 0,
            bebida_id: document.querySelector('input[name="sel_bebida"]:checked')?.value || 0,
            postre_id: document.querySelector('input[name="sel_postre"]:checked')?.value || 0,
        };

        const box = document.getElementById('box-validacion-servidor');
        box.classList.remove('d-none');
        box.innerHTML = '<div class="alert alert-info py-2 small mb-0">⏳ Validando selección con el servidor...</div>';

        fetch('<?= BASE_URL ?>/carta/calcular-total', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.valido) {
                box.innerHTML = `
                    <div class="alert alert-success py-2 small mb-0 border border-success">
                        <strong>✅ Selección Validada por el Servidor</strong><br>
                        Total oficial: <strong>Q ${parseFloat(res.body.total).toFixed(2)}</strong><br>
                        Reglas de categoría cumplidas satisfactoriamente.
                    </div>
                `;
            } else {
                box.innerHTML = `
                    <div class="alert alert-danger py-2 small mb-0 border border-danger">
                        <strong>⚠️ Error de Validación:</strong><br>
                        ${res.body.mensaje || 'La selección no cumple con las restricciones del sistema.'}
                    </div>
                `;
            }
        })
        .catch(err => {
            box.innerHTML = `
                <div class="alert alert-danger py-2 small mb-0">
                    Ocurrió un error al contactar al servidor.
                </div>
            `;
        });
    }

    // Inicializar estilos en la carga inicial
    document.addEventListener('DOMContentLoaded', function() {
        actualizarSeleccion();
    });
</script>
