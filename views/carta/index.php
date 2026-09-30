<?php
/**
 * Vista de La Carta para usuarios y comensales.
 *
 * Muestra los productos disponibles organizados por las 4 categorías:
 * Entrada, Plato fuerte, Bebida y Postre.
 * Cada opción presenta dos respuestas (Sí ✓ o No ✗) con stock disponible visible.
 * Permite seleccionar como máximo 1 producto por categoría y calcula el total automáticamente.
 *
 * @var array<string, array<int, array<string, mixed>>> $productosPorCategoria
 * @var array<int, string> $categorias
 * @var bool $esAdmin
 * @var string $nitUsuario
 */

$iconosCategorias = [
    'Entrada' => '🥗',
    'Plato fuerte' => '🥩',
    'Bebida' => '🥤',
    'Postre' => '🍰',
];

// DEFINICIÓN DE COLORES LLAMATIVOS Y FUERTES POR CATEGORÍA
$estilosCategorias = [
    'Entrada' => [
        'bg_gradient' => 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
        'border' => '#d97706',
        'bg_card' => '#fffdf5',
        'badge' => '#b45309',
        'badge_label' => 'bg-white text-dark',
    ],
    'Plato fuerte' => [
        'bg_gradient' => 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)',
        'border' => '#dc2626',
        'bg_card' => '#fff5f5',
        'badge' => '#991b1b',
        'badge_label' => 'bg-white text-dark',
    ],
    'Bebida' => [
        'bg_gradient' => 'linear-gradient(135deg, #0284c7 0%, #06b6d4 100%)',
        'border' => '#0284c7',
        'bg_card' => '#f0f9ff',
        'badge' => '#075985',
        'badge_label' => 'bg-white text-dark',
    ],
    'Postre' => [
        'bg_gradient' => 'linear-gradient(135deg, #7e22ce 0%, #a855f7 100%)',
        'border' => '#7e22ce',
        'bg_card' => '#faf5ff',
        'badge' => '#581c87',
        'badge_label' => 'bg-white text-dark',
    ],
];
?>
<!-- // Contenedor responsivo optimizado para PC y móviles con colores vivos -->
<div class="container-fluid mt-3 mb-5 carta-container-movil">

    <!-- // BARRA SUPERIOR CON COLORES FUERTES INTECAP -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7 col-12">
                    <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>📖</span> La Carta del Restaurante
                    </h3>
                    <p class="text-white-50 mb-0">
                        Selecciona <strong>Sí (✓)</strong> o <strong>No (✗)</strong> en cada opción (máximo 1 por categoría). El total se calcula en tiempo real.
                    </p>
                </div>
                <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <?php if ($esAdmin): ?>
                        <a href="<?= BASE_URL ?>/carta/admin" class="btn btn-warning fw-bold text-dark shadow-sm">
                            ⚙️ Administrar Productos
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-light text-dark fw-bold shadow-sm" onclick="limpiarTodaLaSeleccion()">
                        🧹 Limpiar Selección
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL: CATEGORÍAS (COL-LG-8) + RESUMEN STICKY (COL-LG-4) -->
    <div class="row g-4">

        <!-- LISTADO DE LAS 4 CATEGORÍAS -->
        <div class="col-lg-8 col-12">

            <?php foreach ($categorias as $cat): ?>
                <?php
                $catKey = strtolower(str_replace(' ', '_', $cat));
                $productos = $productosPorCategoria[$cat] ?? [];
                $icono = $iconosCategorias[$cat] ?? '🍽️';
                $estilo = $estilosCategorias[$cat] ?? [
                    'bg_gradient' => 'linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%)',
                    'border' => '#1d4ed8',
                    'bg_card' => '#ffffff',
                    'badge' => '#1e40af',
                    'badge_label' => 'bg-white text-dark'
                ];
                ?>

                <!-- // TARJETA DE CATEGORÍA CON COLOR LLAMATIVO Y ENCABEZADO VIBRANTE -->
                <div class="card shadow rounded-3 mb-4 category-card" id="seccion-<?= $catKey ?>"
                     style="border: 2.5px solid <?= $estilo['border'] ?> !important; background: <?= $estilo['bg_card'] ?>;">
                    <div class="card-header text-white py-3 d-flex justify-content-between align-items-center shadow-sm"
                         style="background: <?= $estilo['bg_gradient'] ?> !important;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-4"><?= $icono ?></span>
                            <h4 class="mb-0 fw-bold text-white text-uppercase" style="letter-spacing: 0.5px;"><?= htmlspecialchars($cat) ?></h4>
                        </div>
                        <span class="badge bg-white text-dark px-3 py-2 rounded-pill small fw-bold shadow-sm">
                            Máx. 1 opción
                        </span>
                    </div>

                    <div class="card-body p-3">
                        <?php if (empty($productos)): ?>
                            <div class="alert alert-light border text-muted small text-center my-2">
                                No hay opciones disponibles en este momento para la categoría <strong><?= htmlspecialchars($cat) ?></strong> (por horario o stock).
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($productos as $p): ?>
                                    <?php
                                    $prodId = (int) $p['id'];
                                    $stockDisp = (int) ($p['stock_disponible'] ?? 0);
                                    $agotado = ($stockDisp <= 0);
                                    ?>
                                    <div class="col-12 col-md-6">
                                        <div class="card h-100 border-2 rounded-3 shadow-sm product-card position-relative overflow-hidden <?= $agotado ? 'opacity-75 bg-light' : '' ?>"
                                             id="card_prod_<?= $prodId ?>"
                                             style="transition: all 0.25s ease;">

                                            <!-- IMAGEN DEL PRODUCTO -->
                                            <?php if (!empty($p['imagen'])): ?>
                                                <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>"
                                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                                     class="card-img-top"
                                                     style="height: 160px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center text-secondary border-bottom"
                                                     style="height: 160px;">
                                                    <span class="fs-1"><?= $icono ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- BADGE DE SELECCIÓN ACTIVA -->
                                            <div class="badge-seleccion position-absolute top-0 end-0 m-2 d-none" id="badge_sel_<?= $prodId ?>">
                                                <span class="badge bg-success shadow-sm fs-6 px-2 py-1">✓ Seleccionado</span>
                                            </div>

                                            <div class="card-body p-3 d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <h5 class="card-title fw-bold text-dark mb-0">
                                                        <?= htmlspecialchars($p['nombre']) ?>
                                                    </h5>
                                                    <span class="fs-5 fw-bold text-primary ms-2">
                                                        Q <?= number_format((float)$p['precio'], 2) ?>
                                                    </span>
                                                </div>

                                                <?php if (!empty($p['descripcion'])): ?>
                                                    <p class="card-text text-muted small mb-2 flex-grow-1" style="font-size: 0.88rem;">
                                                        <?= htmlspecialchars($p['descripcion']) ?>
                                                    </p>
                                                <?php else: ?>
                                                    <div class="flex-grow-1"></div>
                                                <?php endif; ?>

                                                <!-- DISPONIBILIDAD DE STOCK -->
                                                <div class="mb-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                                    <small class="text-muted fw-bold">Stock:</small>
                                                    <?php if ($agotado): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">
                                                            Agotado (0 disponibles)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success fw-bold">
                                                            <?= $stockDisp ?> disponibles
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- BOTONES DE RESPUESTA: SÍ (✓) / NO (✗) -->
                                                <div class="sino-container">
                                                    <label class="form-label small fw-bold text-secondary mb-1 d-block text-center">
                                                        ¿Desea incluir esta opción?
                                                    </label>
                                                    <div class="btn-group w-100" role="group" aria-label="Selección Sí o No">
                                                        <button type="button"
                                                                class="btn btn-outline-success fw-bold py-2 btn-sino btn-si"
                                                                id="btn_si_<?= $prodId ?>"
                                                                data-cat="<?= $catKey ?>"
                                                                data-id="<?= $prodId ?>"
                                                                data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                                                data-precio="<?= (float)$p['precio'] ?>"
                                                                onclick="marcarOpcion('<?= $catKey ?>', <?= $prodId ?>, true)"
                                                                <?= $agotado ? 'disabled' : '' ?>>
                                                            ✓ Sí
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-secondary text-white fw-bold py-2 btn-sino btn-no active"
                                                                id="btn_no_<?= $prodId ?>"
                                                                data-cat="<?= $catKey ?>"
                                                                data-id="<?= $prodId ?>"
                                                                onclick="marcarOpcion('<?= $catKey ?>', <?= $prodId ?>, false)">
                                                            ✗ No
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

        <!-- // RESUMEN DE SELECCIÓN Y CONFIRMACIÓN DE RESERVA CON ENCABEZADO VIBRANTE (STICKY) -->
        <div class="col-lg-4 col-12">
            <div class="card shadow rounded-3 sticky-top border-2" id="resumen-solicitud-card" style="top: 1.5rem; z-index: 10;">
                <div class="card-header bg-primary text-white py-3" style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%) !important;">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <span>🧾</span> Resumen de la Solicitud
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Opciones marcadas con <strong>Sí (✓)</strong>. Las no seleccionadas contabilizan <strong>Q 0.00</strong>.
                    </p>

                    <!-- DESGLOSE POR CATEGORÍA -->
                    <ul class="list-group list-group-flush mb-3">
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

                    <!-- // TOTAL ACUMULADO CON COLOR FUERTE Y LLAMATIVO -->
                    <div class="p-3 rounded-3 text-center mb-3 text-white shadow"
                         style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: 2.5px solid #047857;">
                        <span class="text-uppercase small fw-bold text-white-50 d-block" style="letter-spacing: 1px;">TOTAL ACUMULADO</span>
                        <div class="fs-1 fw-bold text-white mt-1" id="lbl-total-acumulado">
                            Q 0.00
                        </div>
                    </div>

                    <!-- DATOS PARA LA RESERVA -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">¿Dónde consume?</label>
                        <select id="sel_donde_consume" class="form-select form-select-sm">
                            <option value="En restaurante" selected>🍽️ En restaurante</option>
                            <option value="Para llevar">🛍️ Para llevar</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">NIT de Facturación</label>
                        <input type="text" id="txt_nit" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($nitUsuario) ?>" placeholder="C/F">
                    </div>

                    <!-- BOTÓN DE CONFIRMACIÓN CON COLOR FUERTE -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold py-3 fs-6 shadow" id="btnRealizarReserva"
                                onclick="confirmarReserva()"
                                style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important; border: 2px solid #14532d;">
                            🍽️ Confirmar y Realizar Reserva
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarTodaLaSeleccion()">
                            Desmarcar todo
                        </button>
                    </div>

                    <!-- MENSAJE DE VALIDACIÓN Y RESERVA -->
                    <div id="box-mensaje-reserva" class="mt-3 d-none"></div>

                </div>
            </div>
        </div>

    </div>

</div>

<!-- // BARRA FLOTANTE FIJA PARA DISPOSITIVOS MÓVILES CON COLORES FUERTES -->
<div class="d-lg-none fixed-bottom shadow-lg p-2 px-3 d-flex justify-content-between align-items-center text-white"
     id="barra-movil-carta"
     style="z-index: 1030; background: linear-gradient(135deg, #0a2540 0%, #123d6b 100%) !important; border-top: 2.5px solid #1e40af;">
    <div>
        <small class="text-white-50 d-block fw-bold" style="font-size: 0.72rem; line-height: 1;">TOTAL ACUMULADO</small>
        <span class="fs-4 fw-bold text-warning" id="lbl-total-movil">Q 0.00</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-light btn-sm" onclick="limpiarTodaLaSeleccion()" title="Limpiar selección">
            🧹
        </button>
        <button type="button" class="btn btn-success fw-bold btn-sm px-3 py-2 shadow d-flex align-items-center gap-1"
                onclick="irAlResumenMovil()">
            <span>🍽️ Ver Resumen</span>
            <i class="bi bi-arrow-down-circle-fill"></i>
        </button>
    </div>
</div>

<!-- // ESTILOS VISUALES CON COLORES FUERTES Y LLAMATIVOS PARA SELECCIÓN -->
<style>
    .product-card.card-selected {
        border: 2.5px solid #16a34a !important;
        background-color: #f0fdf4 !important;
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3) !important;
        transform: translateY(-2px);
    }
    .btn-sino {
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .btn-si {
        border: 2px solid #16a34a !important;
        color: #15803d;
        background-color: #ffffff;
    }
    .btn-si:hover {
        background-color: #dcfce7;
        color: #15803d;
    }
    .btn-si.active-si {
        background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important;
        color: #ffffff !important;
        border-color: #15803d !important;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.45) !important;
        font-weight: 800 !important;
    }
    .btn-no {
        border: 2px solid #dc2626 !important;
        color: #dc2626;
        background-color: #ffffff;
    }
    .btn-no:hover {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    .btn-no.active-no {
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%) !important;
        color: #ffffff !important;
        border-color: #b91c1c !important;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.45) !important;
        font-weight: 800 !important;
    }
</style>

<!-- JAVASCRIPT DE MANEJO SÍ / NO, CÁLCULO Y RESERVA -->
<script>
    const baseUrl = '<?= BASE_URL ?>';

    // Almacena la selección actual de cada una de las 4 categorías:
    // { id: 0, nombre: '', precio: 0.0 }
    const seleccionActual = {
        entrada: { id: 0, nombre: 'No seleccionada', precio: 0.0 },
        plato_fuerte: { id: 0, nombre: 'No seleccionado', precio: 0.0 },
        bebida: { id: 0, nombre: 'No seleccionada', precio: 0.0 },
        postre: { id: 0, nombre: 'No seleccionado', precio: 0.0 }
    };

    /**
     * Marca una opción con Sí (✓) o No (✗).
     * En cada categoría solo puede haber 1 producto con "Sí".
     */
    function marcarOpcion(catKey, prodId, esSi) {
        const btnSi = document.getElementById('btn_si_' + prodId);
        const btnNo = document.getElementById('btn_no_' + prodId);
        const card = document.getElementById('card_prod_' + prodId);
        const badge = document.getElementById('badge_sel_' + prodId);

        if (esSi) {
            // Si el usuario marca Sí en este producto:
            // 1. Desmarcar cualquier otro producto que estuviera marcado con Sí en esta categoría
            const seccion = document.getElementById('seccion-' + catKey);
            if (seccion) {
                seccion.querySelectorAll('.product-card').forEach(c => {
                    c.classList.remove('card-selected');
                });
                seccion.querySelectorAll('.badge-seleccion').forEach(b => {
                    b.classList.add('d-none');
                });
                seccion.querySelectorAll('.btn-si').forEach(b => {
                    b.classList.remove('active-si', 'btn-success', 'text-white');
                    b.classList.add('btn-outline-success');
                });
                seccion.querySelectorAll('.btn-no').forEach(b => {
                    b.classList.add('active-no', 'btn-danger', 'text-white');
                    b.classList.remove('btn-outline-danger');
                });
            }

            // 2. Activar Sí en este producto
            btnSi.classList.remove('btn-outline-success');
            btnSi.classList.add('active-si', 'btn-success', 'text-white');

            btnNo.classList.remove('active-no', 'btn-danger', 'text-white');
            btnNo.classList.add('btn-outline-danger');

            if (card) card.classList.add('card-selected');
            if (badge) badge.classList.remove('d-none');

            // 3. Guardar en el estado
            seleccionActual[catKey] = {
                id: prodId,
                nombre: btnSi.dataset.nombre || 'Producto',
                precio: parseFloat(btnSi.dataset.precio) || 0.0
            };
        } else {
            // Si el usuario marca No en este producto:
            btnSi.classList.remove('active-si', 'btn-success', 'text-white');
            btnSi.classList.add('btn-outline-success');

            btnNo.classList.add('active-no', 'btn-danger', 'text-white');
            btnNo.classList.remove('btn-outline-danger');

            if (card) card.classList.remove('card-selected');
            if (badge) badge.classList.add('d-none');

            // Si este producto era el seleccionado en la categoría, se limpia
            if (seleccionActual[catKey].id === prodId) {
                seleccionActual[catKey] = { id: 0, nombre: 'No seleccionado', precio: 0.0 };
            }
        }

        actualizarResumenVisual();
    }

    /**
     * Actualiza el desglose visual y el total acumulado.
     */
    function actualizarResumenVisual() {
        let total = 0.0;
        const categorias = ['entrada', 'plato_fuerte', 'bebida', 'postre'];

        categorias.forEach(cat => {
            const item = seleccionActual[cat];
            const txtNombre = document.getElementById('txt-nombre-' + cat);
            const txtPrecio = document.getElementById('txt-precio-' + cat);

            if (item && item.id > 0) {
                txtNombre.textContent = item.nombre;
                txtNombre.className = 'small fw-bold text-dark';
                txtPrecio.textContent = 'Q ' + item.precio.toFixed(2);
                total += item.precio;
            } else {
                txtNombre.textContent = (cat === 'entrada' || cat === 'bebida') ? 'No seleccionada' : 'No seleccionado';
                txtNombre.className = 'small text-muted';
                txtPrecio.textContent = 'Q 0.00';
            }
        });

        const lblTotal = document.getElementById('lbl-total-acumulado');
        if (lblTotal) {
            lblTotal.textContent = 'Q ' + total.toFixed(2);
        }

        // // Sincronización con la barra flotante móvil
        const lblTotalMovil = document.getElementById('lbl-total-movil');
        if (lblTotalMovil) {
            lblTotalMovil.textContent = 'Q ' + total.toFixed(2);
        }

        // Limpiar mensaje anterior si existiera
        const box = document.getElementById('box-mensaje-reserva');
        if (box) box.classList.add('d-none');
    }

    // // Navegación fluida al resumen para teléfonos móviles
    function irAlResumenMovil() {
        const resumen = document.getElementById('resumen-solicitud-card');
        if (resumen) {
            resumen.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Restablece todas las opciones a "No".
     */
    function limpiarTodaLaSeleccion() {
        document.querySelectorAll('.product-card').forEach(c => {
            c.classList.remove('card-selected');
        });
        document.querySelectorAll('.badge-seleccion').forEach(b => {
            b.classList.add('d-none');
        });
        document.querySelectorAll('.btn-si').forEach(b => {
            b.classList.remove('active-si', 'btn-success', 'text-white');
            b.classList.add('btn-outline-success');
        });
        document.querySelectorAll('.btn-no').forEach(b => {
            b.classList.add('active-no', 'btn-danger', 'text-white');
            b.classList.remove('btn-outline-danger');
        });

        seleccionActual.entrada = { id: 0, nombre: 'No seleccionada', precio: 0.0 };
        seleccionActual.plato_fuerte = { id: 0, nombre: 'No seleccionado', precio: 0.0 };
        seleccionActual.bebida = { id: 0, nombre: 'No seleccionada', precio: 0.0 };
        seleccionActual.postre = { id: 0, nombre: 'No seleccionado', precio: 0.0 };

        actualizarResumenVisual();
    }

    /**
     * Confirma y registra la reserva en el backend.
     */
    function confirmarReserva() {
        const haySeleccion = seleccionActual.entrada.id > 0 ||
                             seleccionActual.plato_fuerte.id > 0 ||
                             seleccionActual.bebida.id > 0 ||
                             seleccionActual.postre.id > 0;

        const box = document.getElementById('box-mensaje-reserva');
        box.classList.remove('d-none');

        if (!haySeleccion) {
            box.innerHTML = `
                <div class="alert alert-warning py-2 small mb-0 border border-warning">
                    ⚠️ Debe seleccionar al menos un producto (marcar <strong>Sí ✓</strong>) para solicitar su reserva.
                </div>
            `;
            return;
        }

        const payload = {
            entrada_id: seleccionActual.entrada.id || 0,
            plato_fuerte_id: seleccionActual.plato_fuerte.id || 0,
            bebida_id: seleccionActual.bebida.id || 0,
            postre_id: seleccionActual.postre.id || 0,
            donde_consume: document.getElementById('sel_donde_consume').value,
            nit_facturacion: document.getElementById('txt_nit').value.trim() || 'C/F',
            fecha_consumo: '<?= date('Y-m-d') ?>',
        };

        const btn = document.getElementById('btnRealizarReserva');
        btn.disabled = true;
        btn.innerHTML = '⏳ Procesando reserva...';

        fetch(baseUrl + '/carta/realizar-reserva', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(result => {
            btn.disabled = false;
            btn.innerHTML = '🍽️ Confirmar y Realizar Reserva';

            if (result.status === 200 && result.body.ok) {
                box.innerHTML = `
                    <div class="alert alert-success py-3 small mb-0 border border-success">
                        <div class="fw-bold fs-6 mb-1">🎉 ¡Reserva Confirmada!</div>
                        <div>${result.body.mensaje}</div>
                        <div class="mt-2 text-muted">Tu orden fue guardada en el recuento consolidado del restaurante.</div>
                    </div>
                `;
                // Desmarcar selecciones tras éxito
                setTimeout(() => {
                    limpiarTodaLaSeleccion();
                }, 3000);
            } else {
                box.innerHTML = `
                    <div class="alert alert-danger py-2 small mb-0 border border-danger">
                        <strong>⚠️ Error al reservar:</strong><br>
                        ${result.body.error || 'No se pudo procesar la reserva.'}
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '🍽️ Confirmar y Realizar Reserva';
            box.innerHTML = `
                <div class="alert alert-danger py-2 small mb-0">
                    Ocurrió un error al contactar al servidor.
                </div>
            `;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        limpiarTodaLaSeleccion();
    });
</script>
