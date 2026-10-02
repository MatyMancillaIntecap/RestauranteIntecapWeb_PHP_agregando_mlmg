<?php
/**
 * Panel de administración de La Carta.
 *
 * Muestra el panel organizado en dos pestañas principales:
 * 1. Catálogo de La Carta: administración unificada de productos en todas las categorías.
 * 2. Recuento Consolidado: estadísticas de platillos reservados, recaudación, detalle
 *    individual de reservas y exportaciones oficiales a PDF y Excel (idéntico a Cocina).
 *
 * @var array<string, array<int, array<string, mixed>>> $productosPorCategoria
 * @var array<string, array<int, array<string, mixed>>> $productosDisponibles
 * @var array<int, string> $categorias
 * @var array<string, mixed> $consolidado
 * @var array<int, array<string, mixed>> $reservasDetalladas
 * @var string $fechaFiltro
 * @var string $tabActiva
 * @var array<int, array<string, mixed>> $usuarios
 */

$iconosCategorias = [
    'Entrada' => '🥗',
    'Plato fuerte' => '🥩',
    'Bebida' => '🥤',
    'Postre' => '🍰',
];

// // DEFINICIÓN DE COLORES LLAMATIVOS Y FUERTES POR CATEGORÍA
$estilosCategorias = [
    'Entrada' => [
        'bg_gradient' => 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
        'border' => '#d97706',
        'bg_card' => '#fffdf5',
        'badge' => '#b45309',
        'btn_class' => 'btn-light text-dark'
    ],
    'Plato fuerte' => [
        'bg_gradient' => 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)',
        'border' => '#dc2626',
        'bg_card' => '#fff5f5',
        'badge' => '#991b1b',
        'btn_class' => 'btn-light text-dark'
    ],
    'Bebida' => [
        'bg_gradient' => 'linear-gradient(135deg, #0284c7 0%, #06b6d4 100%)',
        'border' => '#0284c7',
        'bg_card' => '#f0f9ff',
        'badge' => '#075985',
        'btn_class' => 'btn-light text-dark'
    ],
    'Postre' => [
        'bg_gradient' => 'linear-gradient(135deg, #7e22ce 0%, #a855f7 100%)',
        'border' => '#7e22ce',
        'bg_card' => '#faf5ff',
        'badge' => '#581c87',
        'btn_class' => 'btn-light text-dark'
    ],
];
?>
<div class="container-fluid mt-3 mb-5">

    <!-- // BARRA SUPERIOR CON COLORES FUERTES Y LLAMATIVOS -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6 col-12">
                    <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>⚙️</span> Administración de La Carta
                    </h3>
                    <p class="text-white-50 mb-0">
                        Catálogo de todas las categorías, habilitación por horarios/stock, reservas directas y recuento consolidado.
                    </p>
                </div>
                <div class="col-md-6 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <!-- Opción Vista Cliente -->
                    <a href="<?= BASE_URL ?>/carta/index" class="btn btn-light text-primary fw-bold shadow-sm flex-fill flex-md-grow-0 text-center">
                        👁️ Ver Vista Cliente
                    </a>
                    <!-- El administrador también puede realizar una reserva desde aquí -->
                    <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm flex-fill flex-md-grow-0 text-center" onclick="abrirModalReservaAdmin()">
                        🍽️ Realizar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- // PESTAÑAS DE NAVEGACIÓN MARCADAS (CATÁLOGO vs RECUENTO CONSOLIDADO) -->
    <ul class="nav nav-tabs nav-justified mb-4 fw-bold shadow-sm" id="cartaAdminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($tabActiva !== 'consolidado') ? 'active' : '' ?>" id="catalogo-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-catalogo" type="button" role="tab">
                🍽️ Catálogo de La Carta
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($tabActiva === 'consolidado') ? 'active' : '' ?>" id="consolidado-tab" data-bs-toggle="tab"
                    data-bs-target="#tab-consolidado" type="button" role="tab">
                📊 Recuento Consolidado <span class="badge rounded-pill tab-badge ms-1"><?= (int) ($consolidado['total_platillos_reservados'] ?? 0) ?> platillos</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="cartaAdminTabsContent">

        <!-- ═══════════════════════════════════════════════════════
             PESTAÑA 1: CATÁLOGO GENERAL (TODAS LAS CATEGORÍAS)
        ═══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade <?= ($tabActiva !== 'consolidado') ? 'show active' : '' ?>" id="tab-catalogo" role="tabpanel">
            <div class="row">

                <!-- Formulario lateral de NUEVO PRODUCTO / EDITAR PRODUCTO (AL LADO COMO EN COCINA) -->
                <div class="col-lg-4 col-12 mb-4">
                    <div class="card shadow rounded-3 border-2 sticky-top" id="cardFormularioLateral" style="top: 20px; z-index: 10;">
                        <div class="card-header text-white py-3" id="panelFormHeader"
                             style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important;">
                            <h5 class="mb-0 fw-bold d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span id="panelFormTitulo">➕ Registrar Producto</span>
                                <span class="badge bg-white text-success small shadow-sm" id="badgeModoForm">Nuevo</span>
                            </h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <form method="post" action="<?= BASE_URL ?>/carta/guardar" enctype="multipart/form-data" id="formProductoLateral">
                                <input type="hidden" id="prod_id" name="id" value="0">

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Categoría <span class="text-danger">*</span></label>
                                    <select id="prod_categoria" name="categoria" class="form-select form-select-sm" required>
                                        <option value="">-- Selecciona una categoría --</option>
                                        <?php foreach ($categorias as $c): ?>
                                            <option value="<?= htmlspecialchars($c) ?>">
                                                <?= $iconosCategorias[$c] ?? '' ?> <?= htmlspecialchars($c) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Nombre del Producto <span class="text-danger">*</span></label>
                                    <input type="text" id="prod_nombre" name="nombre" class="form-control form-control-sm"
                                           placeholder="Ej. Ensalada César, Pollo a la Plancha..." required maxlength="100">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Descripción</label>
                                    <textarea id="prod_descripcion" name="descripcion" class="form-control form-control-sm" rows="2"
                                              placeholder="Detalles sobre ingredientes, preparación o porción..."></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold small">Precio (Q) <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold">Q</span>
                                            <input type="number" id="prod_precio" name="precio" class="form-control form-control-sm"
                                                   step="0.01" min="0.01" placeholder="0.00" required>
                                        </div>
                                    </div>

                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-bold small">Stock Inicial <span class="text-danger">*</span></label>
                                        <input type="number" id="prod_stock" name="stock" class="form-control form-control-sm"
                                               min="0" max="9999" value="10" required>
                                    </div>
                                </div>

                                <!-- CONTROL DE HABILITACIÓN Y HORARIOS -->
                                <div class="card border p-3 mb-3 bg-light rounded-3">
                                    <h6 class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                                        <span>⏰</span> Días y Horarios de Habilitación
                                    </h6>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Días habilitados</label>
                                        <select id="prod_dias_habilitados" name="dias_habilitados" class="form-select form-select-sm">
                                            <option value="Todos">Todos los días</option>
                                            <option value="Lunes">Solo Lunes</option>
                                            <option value="Martes">Solo Martes</option>
                                            <option value="Miércoles">Solo Miércoles</option>
                                            <option value="Jueves">Solo Jueves</option>
                                            <option value="Viernes">Solo Viernes</option>
                                            <option value="Sábado">Solo Sábado</option>
                                            <option value="Domingo">Solo Domingo</option>
                                            <option value="Lunes,Martes,Miércoles,Jueves,Viernes">De Lunes a Viernes</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Fecha específica (Opcional)</label>
                                        <input type="date" id="prod_fecha_habilitacion" name="fecha_habilitacion" class="form-control form-control-sm">
                                        <small class="text-muted" style="font-size: 0.72rem;">Vacío para aplicar siempre.</small>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1">Apertura</label>
                                            <input type="time" id="prod_hora_inicio" name="hora_inicio" class="form-control form-control-sm" value="00:00">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1">Cierre</label>
                                            <input type="time" id="prod_hora_fin" name="hora_fin" class="form-control form-control-sm" value="23:59">
                                        </div>
                                    </div>
                                </div>

                                <!-- Imagen -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Fotografía / Imagen (Opcional)</label>
                                    <input type="file" id="prod_imagen_file" name="imagen_file" class="form-control form-control-sm"
                                           accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                                    <div id="preview_imagen_actual" class="mt-2 d-none">
                                        <span class="small text-muted d-block mb-1">Imagen actual:</span>
                                        <img src="" id="img_preview" class="rounded border" style="max-height: 70px;">
                                    </div>
                                </div>

                                <!-- Estado Activo -->
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="prod_estado" name="estado" value="1" checked>
                                    <label class="form-check-label fw-bold small" for="prod_estado">
                                        Producto Activo en el Catálogo
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm" id="btnGuardar">
                                    💾 Guardar Producto
                                </button>
                                <button type="button" class="btn btn-outline-secondary w-100 fw-bold mt-2 d-none" id="btnCancelarEdicion" onclick="cancelarEdicionProducto()">
                                    ✖️ Cancelar Edición
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Catálogo general de categorías -->
                <div class="col-lg-8 col-12">
                    <div class="card shadow rounded-3 mb-4 border-2">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center"
                             style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <span>🍽️</span> Catálogo General de La Carta (Todas las Categorías)
                            </h5>
                            <span class="badge bg-warning text-dark fw-bold shadow-sm">
                                <?= count($categorias) ?> Categorías
                            </span>
                        </div>

                        <div class="card-body p-3 p-md-4">
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
                                    'btn_class' => 'btn-light text-dark'
                                ];
                                ?>

                                <!-- // TARJETA DE CATEGORÍA CON COLOR LLAMATIVO Y ENCABEZADO VIBRANTE -->
                                <div class="card mb-4 rounded-3 shadow" style="border: 2.5px solid <?= $estilo['border'] ?> !important; background: <?= $estilo['bg_card'] ?>;">
                                    <div class="card-header text-white py-3 d-flex justify-content-between align-items-center shadow-sm flex-wrap gap-2"
                                         style="background: <?= $estilo['bg_gradient'] ?> !important;">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fs-4"><?= $icono ?></span>
                                            <h5 class="mb-0 fw-bold text-white text-uppercase" style="letter-spacing: 0.5px;"><?= htmlspecialchars($cat) ?></h5>
                                            <span class="badge bg-white text-dark ms-2 fw-bold shadow-sm"><?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-light text-dark fw-bold shadow-sm" onclick="seleccionarCategoriaEnFormulario('<?= htmlspecialchars($cat, ENT_QUOTES) ?>')">
                                            ➕ Agregar a <?= htmlspecialchars($cat) ?>
                                        </button>
                                    </div>

                            <div class="card-body p-0">
                                <?php if (empty($productos)): ?>
                                    <div class="text-center py-4 text-muted small">
                                        No hay productos registrados en <strong><?= htmlspecialchars($cat) ?></strong>. Haz clic en "Agregar" para crear uno.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 70px;">Foto</th>
                                                    <th>Nombre del Producto</th>
                                                    <th>Descripción</th>
                                                    <th>Precio</th>
                                                    <th>Stock</th>
                                                    <th>Días y Horario de Habilitación</th>
                                                    <th>Estado</th>
                                                    <th class="text-center" style="width: 170px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productos as $p): ?>
                                                    <?php
                                                    $prodId = (int) $p['id'];
                                                    $stockDisp = (int) ($p['stock_disponible'] ?? 0);
                                                    $habilitado = (bool) ($p['habilitado_ahora'] ?? true);
                                                    ?>
                                                    <tr id="fila-producto-<?= $prodId ?>">
                                                        <td>
                                                            <?php if (!empty($p['imagen'])): ?>
                                                                <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>"
                                                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                                                     class="rounded border"
                                                                     style="width: 60px; height: 50px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted"
                                                                     style="width: 60px; height: 50px; font-size: 1.3rem;">
                                                                    <?= $icono ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                                        </td>
                                                        <td>
                                                            <span class="small text-muted">
                                                                <?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción') ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-primary">
                                                                Q <?= number_format((float) $p['precio'], 2) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="small">
                                                                <strong><?= $stockDisp ?></strong> disp. / <?= (int)$p['stock'] ?> tot.
                                                            </div>
                                                            <?php if ($stockDisp <= 0): ?>
                                                                <span class="badge bg-danger small">Agotado</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="small fw-bold text-dark">
                                                                <?= htmlspecialchars($p['dias_habilitados'] ?: 'Todos los días') ?>
                                                            </div>
                                                            <div class="small text-muted">
                                                                ⏰ <?= substr((string)$p['hora_inicio'], 0, 5) ?> a <?= substr((string)$p['hora_fin'], 0, 5) ?>
                                                            </div>
                                                            <?php if (!empty($p['fecha_habilitacion'])): ?>
                                                                <div class="small text-info">📅 <?= htmlspecialchars($p['fecha_habilitacion']) ?></div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <?php if ($habilitado): ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.72rem;">🟢 Disponible ahora</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size: 0.72rem;">⏰ Fuera de horario</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch"
                                                                       id="sw_prod_<?= $prodId ?>"
                                                                       <?= $p['estado'] ? 'checked' : '' ?>
                                                                       onchange="cambiarEstadoProducto(<?= $prodId ?>, this.checked, this)">
                                                                <label class="form-check-label small fw-bold"
                                                                       id="lbl_sw_prod_<?= $prodId ?>"
                                                                       for="sw_prod_<?= $prodId ?>">
                                                                    <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1"
                                                                    onclick="abrirModalEditarProducto(<?= $prodId ?>)">
                                                                ✏️ Editar
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold"
                                                                    onclick="eliminarProducto(<?= $prodId ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')">
                                                                🗑️ Eliminar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        </div><!-- /col-lg-8 -->
    </div><!-- /row -->
</div><!-- /tab-catalogo -->

        <!-- ═══════════════════════════════════════════════════════
             PESTAÑA 2: RECUENTO CONSOLIDADO DE RESERVAS Y EXPORTACIONES
        ═══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade <?= ($tabActiva === 'consolidado') ? 'show active' : '' ?>" id="tab-consolidado" role="tabpanel">

            <!-- // BARRA DE FILTRO POR FECHA Y DESCARGAS (EXCEL Y PDF) CON COLORES FUERTES -->
            <div class="card shadow mb-4 rounded-3 text-white"
                 style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 col-12">
                            <h4 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                                <span>📊</span> Reporte Consolidado de La Carta
                            </h4>
                            <p class="text-white-50 small mb-0">
                                Consulta reservas acumuladas o por fecha y descarga los reportes oficiales en Excel y PDF.
                            </p>
                        </div>
                        <div class="col-md-6 col-12 mt-3 mt-md-0">
                            <form method="get" action="<?= BASE_URL ?>/carta/admin"
                                  class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                <input type="hidden" name="tab" value="consolidado">
                                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
                                    <label class="fw-bold text-nowrap small text-white">Fecha:</label>
                                    <input type="date" name="fecha" class="form-control form-control-sm flex-grow-1"
                                           value="<?= htmlspecialchars($fechaFiltro) ?>"
                                           onchange="this.form.submit()">
                                </div>
                                <?php if ($fechaFiltro !== ''): ?>
                                    <a href="<?= BASE_URL ?>/carta/admin?tab=consolidado&fecha=" class="btn btn-sm btn-light text-dark fw-bold text-nowrap flex-grow-1 flex-md-grow-0 text-center" title="Ver acumulado de todas las fechas">
                                        Histórico General
                                    </a>
                                <?php endif; ?>
                                <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
                                    <a href="<?= BASE_URL ?>/carta/descargar-excel?fecha=<?= urlencode($fechaFiltro) ?>"
                                       class="btn btn-sm btn-success fw-bold text-nowrap shadow-sm flex-fill text-center">
                                        📊 Excel
                                    </a>
                                    <a href="<?= BASE_URL ?>/carta/descargar-pdf?fecha=<?= urlencode($fechaFiltro) ?>"
                                       class="btn btn-sm btn-danger fw-bold text-nowrap shadow-sm flex-fill text-center">
                                        📄 PDF
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- // TARJETA CONSOLIDADO DE PRODUCTOS CON ENCABEZADO VIBRANTE -->
            <div class="card shadow rounded-3 mb-4 border-2">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">📊</span>
                        <h5 class="mb-0 fw-bold">Recuento Consolidado por Producto</h5>
                    </div>
                    <div class="d-flex gap-2 flex-wrap small fw-bold">
                        <span class="badge bg-white text-primary fs-6 shadow-sm">
                            Total Reservados: <?= (int) ($consolidado['total_platillos_reservados'] ?? 0) ?> unidades
                        </span>
                        <span class="badge bg-success text-white fs-6 shadow-sm">
                            Recaudación: Q <?= number_format((float) ($consolidado['total_recaudado'] ?? 0), 2) ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoría</th>
                                    <th>Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Stock Inicial</th>
                                    <th class="text-center">Reservados</th>
                                    <th class="text-center">Stock Disponible</th>
                                    <th>Horario de Habilitación</th>
                                    <th class="text-end pe-3">Total Recaudado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($consolidado['items'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted small">
                                            No hay productos registrados en el recuento consolidado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consolidado['items'] as $item): ?>
                                        <?php
                                        $stockDisp = (int) $item['stock_disponible'];
                                        $solicitados = (int) $item['cantidad_solicitada'];
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= $iconosCategorias[$item['categoria']] ?? '🍽️' ?> <?= htmlspecialchars($item['categoria']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark">
                                                <?= htmlspecialchars($item['nombre']) ?>
                                            </td>
                                            <td class="text-center">
                                                Q <?= number_format((float) $item['precio'], 2) ?>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= (int) $item['stock'] ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info text-dark">
                                                    <?= $solicitados ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($stockDisp <= 0): ?>
                                                    <span class="badge bg-danger">Agotado (0)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?= $stockDisp ?> disponibles</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <div><strong>Días:</strong> <?= htmlspecialchars($item['dias_habilitados'] ?: 'Todos') ?></div>
                                                <div><strong>Horario:</strong> <?= substr((string)$item['hora_inicio'], 0, 5) ?> - <?= substr((string)$item['hora_fin'], 0, 5) ?></div>
                                                <?php if (!empty($item['fecha_habilitacion'])): ?>
                                                    <div><strong>Fecha:</strong> <?= htmlspecialchars($item['fecha_habilitacion']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3 fw-bold text-success">
                                                Q <?= number_format((float) $item['total_recaudado'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- // DETALLE DE RESERVAS REALIZADAS CON ENCABEZADO VIBRANTE -->
            <div class="card shadow rounded-3 mb-4 border-2">
                <div class="card-header text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                    <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                        <span>📋</span> Detalle de Reservas Realizadas (<?= count($reservasDetalladas) ?> registradas)
                    </h5>
                    <span class="badge bg-warning text-dark fw-bold shadow-sm">
                        <?= ($fechaFiltro !== '') ? 'Fecha: ' . htmlspecialchars($fechaFiltro) : 'Histórico General' ?>
                    </span>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($reservasDetalladas)): ?>
                        <div class="text-center text-muted small py-4">
                            No se registran reservas para la fecha seleccionada.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>#Reserva</th>
                                        <th>Usuario / Comensal</th>
                                        <th>Entrada</th>
                                        <th>Plato Fuerte</th>
                                        <th>Bebida</th>
                                        <th>Postre</th>
                                        <th>Total</th>
                                        <th>Modalidad</th>
                                        <th>NIT</th>
                                        <th>Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservasDetalladas as $rd): ?>
                                        <tr>
                                            <td><strong>#<?= (int) $rd['id'] ?></strong></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($rd['usuario_nombre'] ?? 'Usuario') ?></div>
                                                <div class="text-muted"><?= htmlspecialchars($rd['usuario_email'] ?? '') ?></div>
                                                <?php if (!empty($rd['usuario_telefono'])): ?>
                                                    <div class="text-muted"><?= htmlspecialchars($rd['usuario_telefono']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($rd['entrada_nombre'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($rd['plato_fuerte_nombre'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($rd['bebida_nombre'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($rd['postre_nombre'] ?? '—') ?></td>
                                            <td class="fw-bold text-success fs-6">Q <?= number_format((float) $rd['total'], 2) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rd['donde_consume']) ?></span></td>
                                            <td><span class="badge bg-secondary-subtle text-dark"><?= htmlspecialchars($rd['nit_facturacion'] ?? 'C/F') ?></span></td>
                                            <td class="text-muted"><?= htmlspecialchars($rd['fecha_reserva']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>



<!-- ═══════════════════════════════════════════════════════
     MODAL — REALIZAR RESERVA DIRECTA (ADMINISTRADOR)
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalReservaAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">🍽️ Realizar Reserva desde Administración</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Selecciona los platillos para la reserva (máximo 1 opción con <strong>Sí ✓</strong> por categoría). El recuento consolidado se actualizará automáticamente.
                </p>

                <!-- Selección de usuario / comensal -->
                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <label class="form-label fw-bold small text-dark mb-1">👤 Reservar en nombre de:</label>
                    <select id="admin_reserva_usuario" class="form-select form-select-sm">
                        <option value="<?= Auth::id() ?>" selected>Mí mismo (Administrador: <?= htmlspecialchars(Auth::user()['nombre'] ?? 'Admin') ?><?= !empty(Auth::user()['telefono']) ? ' | ' . htmlspecialchars(Auth::user()['telefono']) : '' ?>)</option>
                        <?php if (!empty($usuarios)): ?>
                            <optgroup label="Otros Comensales / Empleados">
                                <?php foreach ($usuarios as $u): ?>
                                    <?php if ((int)$u['id'] !== (int)Auth::id()): ?>
                                        <option value="<?= (int)$u['id'] ?>">
                                            <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['email'] ?? '') ?><?= !empty($u['telefono']) ? ' | ' . htmlspecialchars($u['telefono']) : '' ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Opciones por categoría -->
                <?php foreach ($categorias as $cat): ?>
                    <?php
                    $catKey = strtolower(str_replace(' ', '_', $cat));
                    $disponiblesCat = $productosDisponibles[$cat] ?? [];
                    $icono = $iconosCategorias[$cat] ?? '🍽️';
                    ?>
                    <div class="card border rounded-3 mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-uppercase text-dark">
                                <?= $icono ?> <?= htmlspecialchars($cat) ?>
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary small">Máx. 1 opción</span>
                        </div>
                        <div class="card-body p-2">
                            <?php if (empty($disponiblesCat)): ?>
                                <div class="text-muted small text-center py-2">
                                    No hay opciones disponibles en este horario o stock en <strong><?= htmlspecialchars($cat) ?></strong>.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($disponiblesCat as $p): ?>
                                        <?php
                                        $pId = (int)$p['id'];
                                        $stockDisp = (int)($p['stock_disponible'] ?? 0);
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 flex-wrap gap-2">
                                            <div class="me-auto">
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                                <div class="text-muted small">
                                                    <span class="text-primary fw-bold">Q <?= number_format((float)$p['precio'], 2) ?></span>
                                                    &bull; <span class="badge bg-success-subtle text-success border border-success"><?= $stockDisp ?> disponibles</span>
                                                </div>
                                            </div>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button"
                                                        class="btn btn-outline-success fw-bold btn-admin-sino btn-admin-si"
                                                        id="admin_btn_si_<?= $pId ?>"
                                                        data-cat="<?= $catKey ?>"
                                                        data-id="<?= $pId ?>"
                                                        data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                                        data-precio="<?= (float)$p['precio'] ?>"
                                                        onclick="marcarOpcionAdmin('<?= $catKey ?>', <?= $pId ?>, true)">
                                                    ✓ Sí
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger text-white fw-bold btn-admin-sino btn-admin-no active"
                                                        id="admin_btn_no_<?= $pId ?>"
                                                        data-cat="<?= $catKey ?>"
                                                        data-id="<?= $pId ?>"
                                                        onclick="marcarOpcionAdmin('<?= $catKey ?>', <?= $pId ?>, false)">
                                                    ✗ No
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Resumen y total de la reserva -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="row align-items-center">
                        <div class="col-sm-6 mb-2 mb-sm-0">
                            <span class="small fw-bold text-muted text-uppercase d-block">TOTAL ACUMULADO</span>
                            <div class="fs-3 fw-bold text-success" id="admin_lbl_total">Q 0.00</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold mb-1">¿Dónde consume?</label>
                            <select id="admin_reserva_donde" class="form-select form-select-sm mb-2">
                                <option value="En restaurante" selected>🍽️ En restaurante</option>
                                <option value="Para llevar">🛍️ Para llevar</option>
                            </select>
                            <label class="form-label small fw-bold mb-1">NIT Facturación</label>
                            <input type="text" id="admin_reserva_nit" class="form-control form-control-sm" value="C/F">
                        </div>
                    </div>
                </div>

                <div id="admin_box_mensaje_reserva" class="d-none mb-3"></div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold" id="btnAdminConfirmarReserva" onclick="confirmarReservaAdmin()">
                    🍽️ Confirmar Reserva
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?= BASE_URL ?>';

    // Estado de reserva para administrador
    const seleccionAdmin = {
        entrada: { id: 0, nombre: '', precio: 0.0 },
        plato_fuerte: { id: 0, nombre: '', precio: 0.0 },
        bebida: { id: 0, nombre: '', precio: 0.0 },
        postre: { id: 0, nombre: '', precio: 0.0 }
    };

    function abrirModalReservaAdmin() {
        limpiarSeleccionAdmin();
        const box = document.getElementById('admin_box_mensaje_reserva');
        if (box) box.classList.add('d-none');
        new bootstrap.Modal(document.getElementById('modalReservaAdmin')).show();
    }

    function marcarOpcionAdmin(catKey, prodId, esSi) {
        const modal = document.getElementById('modalReservaAdmin');
        const btnSi = document.getElementById('admin_btn_si_' + prodId);
        const btnNo = document.getElementById('admin_btn_no_' + prodId);

        if (esSi) {
            // Desmarcar todos los demás en esta categoría
            modal.querySelectorAll(`.btn-admin-si[data-cat="${catKey}"]`).forEach(b => {
                b.classList.remove('btn-success', 'text-white');
                b.classList.add('btn-outline-success');
            });
            modal.querySelectorAll(`.btn-admin-no[data-cat="${catKey}"]`).forEach(b => {
                b.classList.add('btn-danger', 'text-white');
                b.classList.remove('btn-outline-danger');
            });

            // Activar Sí en este producto
            btnSi.classList.remove('btn-outline-success');
            btnSi.classList.add('btn-success', 'text-white');

            btnNo.classList.remove('btn-danger', 'text-white');
            btnNo.classList.add('btn-outline-danger');

            seleccionAdmin[catKey] = {
                id: prodId,
                nombre: btnSi.dataset.nombre || 'Producto',
                precio: parseFloat(btnSi.dataset.precio) || 0.0
            };
        } else {
            btnSi.classList.remove('btn-success', 'text-white');
            btnSi.classList.add('btn-outline-success');

            btnNo.classList.add('btn-danger', 'text-white');
            btnNo.classList.remove('btn-outline-danger');

            if (seleccionAdmin[catKey].id === prodId) {
                seleccionAdmin[catKey] = { id: 0, nombre: '', precio: 0.0 };
            }
        }

        actualizarTotalAdmin();
    }

    function actualizarTotalAdmin() {
        let total = 0.0;
        ['entrada', 'plato_fuerte', 'bebida', 'postre'].forEach(cat => {
            if (seleccionAdmin[cat] && seleccionAdmin[cat].id > 0) {
                total += seleccionAdmin[cat].precio;
            }
        });
        const lbl = document.getElementById('admin_lbl_total');
        if (lbl) lbl.textContent = 'Q ' + total.toFixed(2);
    }

    function limpiarSeleccionAdmin() {
        const modal = document.getElementById('modalReservaAdmin');
        if (modal) {
            modal.querySelectorAll('.btn-admin-si').forEach(b => {
                b.classList.remove('btn-success', 'text-white');
                b.classList.add('btn-outline-success');
            });
            modal.querySelectorAll('.btn-admin-no').forEach(b => {
                b.classList.add('btn-danger', 'text-white');
                b.classList.remove('btn-outline-danger');
            });
        }
        seleccionAdmin.entrada = { id: 0, nombre: '', precio: 0.0 };
        seleccionAdmin.plato_fuerte = { id: 0, nombre: '', precio: 0.0 };
        seleccionAdmin.bebida = { id: 0, nombre: '', precio: 0.0 };
        seleccionAdmin.postre = { id: 0, nombre: '', precio: 0.0 };
        actualizarTotalAdmin();
    }

    function confirmarReservaAdmin() {
        const haySeleccion = seleccionAdmin.entrada.id > 0 ||
                             seleccionAdmin.plato_fuerte.id > 0 ||
                             seleccionAdmin.bebida.id > 0 ||
                             seleccionAdmin.postre.id > 0;

        const box = document.getElementById('admin_box_mensaje_reserva');
        box.classList.remove('d-none');

        if (!haySeleccion) {
            box.innerHTML = `
                <div class="alert alert-warning py-2 small mb-0 border border-warning">
                    ⚠️ Debe seleccionar al menos un producto (marcar <strong>Sí ✓</strong>) para confirmar la reserva.
                </div>
            `;
            return;
        }

        const payload = {
            usuario_id: parseInt(document.getElementById('admin_reserva_usuario').value) || <?= Auth::id() ?>,
            entrada_id: seleccionAdmin.entrada.id || 0,
            plato_fuerte_id: seleccionAdmin.plato_fuerte.id || 0,
            bebida_id: seleccionAdmin.bebida.id || 0,
            postre_id: seleccionAdmin.postre.id || 0,
            donde_consume: document.getElementById('admin_reserva_donde').value,
            nit_facturacion: document.getElementById('admin_reserva_nit').value.trim() || 'C/F',
            fecha_consumo: '<?= date('Y-m-d') ?>',
        };

        const btn = document.getElementById('btnAdminConfirmarReserva');
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
            btn.innerHTML = '🍽️ Confirmar Reserva';

            if (result.status === 200 && result.body.ok) {
                box.innerHTML = `
                    <div class="alert alert-success py-2 small mb-0 border border-success">
                        <strong>🎉 ¡Reserva realizada con éxito!</strong><br>
                        ${result.body.mensaje}
                    </div>
                `;
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                box.innerHTML = `
                    <div class="alert alert-danger py-2 small mb-0 border border-danger">
                        <strong>⚠️ Error al procesar reserva:</strong><br>
                        ${result.body.error || 'No se pudo registrar la reserva.'}
                    </div>
                `;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '🍽️ Confirmar Reserva';
            box.innerHTML = `
                <div class="alert alert-danger py-2 small mb-0">
                    Ocurrió un error al contactar al servidor.
                </div>
            `;
        });
    }

    function seleccionarCategoriaEnFormulario(categoria = '') {
        cancelarEdicionProducto();
        if (categoria) {
            const catSelect = document.getElementById('prod_categoria');
            if (catSelect) catSelect.value = categoria;
        }
        const lateral = document.getElementById('cardFormularioLateral');
        if (lateral) {
            lateral.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        const inputNombre = document.getElementById('prod_nombre');
        if (inputNombre) {
            setTimeout(() => inputNombre.focus(), 300);
        }
    }

    function abrirModalNuevoProducto(categoriaPrevia = '') {
        seleccionarCategoriaEnFormulario(categoriaPrevia);
    }

    function abrirModalEditarProducto(id) {
        fetch(baseUrl + '/carta/obtener-producto-por-id/' + id)
            .then(res => {
                if (!res.ok) throw new Error('No se pudo cargar el producto');
                return res.json();
            })
            .then(data => {
                // Actualizar encabezado del formulario lateral
                const titulo = document.getElementById('panelFormTitulo');
                if (titulo) titulo.innerHTML = '✏️ Editar Producto';

                const badge = document.getElementById('badgeModoForm');
                if (badge) {
                    badge.className = 'badge bg-warning text-dark small shadow-sm';
                    badge.textContent = 'Edición';
                }

                const header = document.getElementById('panelFormHeader');
                if (header) {
                    header.style.setProperty('background', 'linear-gradient(135deg, #b45309 0%, #d97706 100%)', 'important');
                }

                document.getElementById('prod_id').value = data.id;
                document.getElementById('prod_categoria').value = data.categoria;
                document.getElementById('prod_nombre').value = data.nombre;
                document.getElementById('prod_descripcion').value = data.descripcion || '';
                document.getElementById('prod_precio').value = data.precio;
                document.getElementById('prod_stock').value = data.stock || 10;
                document.getElementById('prod_dias_habilitados').value = data.dias_habilitados || 'Todos';
                document.getElementById('prod_fecha_habilitacion').value = data.fecha_habilitacion ? data.fecha_habilitacion.substring(0, 10) : '';
                document.getElementById('prod_hora_inicio').value = data.hora_inicio ? data.hora_inicio.substring(0, 5) : '00:00';
                document.getElementById('prod_hora_fin').value = data.hora_fin ? data.hora_fin.substring(0, 5) : '23:59';
                document.getElementById('prod_estado').checked = (parseInt(data.estado) === 1);
                document.getElementById('prod_imagen_file').value = '';

                const previewBox = document.getElementById('preview_imagen_actual');
                const imgPreview = document.getElementById('img_preview');
                if (data.imagen) {
                    imgPreview.src = baseUrl + data.imagen;
                    previewBox.classList.remove('d-none');
                } else {
                    previewBox.classList.add('d-none');
                }

                const btnGuardar = document.getElementById('btnGuardar');
                if (btnGuardar) {
                    btnGuardar.innerHTML = '💾 Actualizar Producto';
                    btnGuardar.className = 'btn btn-warning text-dark w-100 fw-bold py-2 shadow-sm';
                }

                const btnCancel = document.getElementById('btnCancelarEdicion');
                if (btnCancel) {
                    btnCancel.classList.remove('d-none');
                }

                const lateral = document.getElementById('cardFormularioLateral');
                if (lateral) {
                    lateral.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                const inputNombre = document.getElementById('prod_nombre');
                if (inputNombre) {
                    setTimeout(() => inputNombre.focus(), 300);
                }
            })
            .catch(err => {
                alert('Error al obtener la información del producto.');
            });
    }

    function cancelarEdicionProducto() {
        document.getElementById('prod_id').value = '0';
        document.getElementById('prod_categoria').value = '';
        document.getElementById('prod_nombre').value = '';
        document.getElementById('prod_descripcion').value = '';
        document.getElementById('prod_precio').value = '';
        document.getElementById('prod_stock').value = '10';
        document.getElementById('prod_dias_habilitados').value = 'Todos';
        document.getElementById('prod_fecha_habilitacion').value = '';
        document.getElementById('prod_hora_inicio').value = '00:00';
        document.getElementById('prod_hora_fin').value = '23:59';
        document.getElementById('prod_imagen_file').value = '';
        document.getElementById('prod_estado').checked = true;

        const previewBox = document.getElementById('preview_imagen_actual');
        if (previewBox) previewBox.classList.add('d-none');

        const titulo = document.getElementById('panelFormTitulo');
        if (titulo) titulo.innerHTML = '➕ Registrar Producto';

        const badge = document.getElementById('badgeModoForm');
        if (badge) {
            badge.className = 'badge bg-white text-success small shadow-sm';
            badge.textContent = 'Nuevo';
        }

        const header = document.getElementById('panelFormHeader');
        if (header) {
            header.style.setProperty('background', 'linear-gradient(135deg, #15803d 0%, #16a34a 100%)', 'important');
        }

        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.innerHTML = '💾 Guardar Producto';
            btnGuardar.className = 'btn btn-success w-100 fw-bold py-2 shadow-sm';
        }

        const btnCancel = document.getElementById('btnCancelarEdicion');
        if (btnCancel) {
            btnCancel.classList.add('d-none');
        }
    }

    function cambiarEstadoProducto(id, activo, switchElem) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('activo', activo ? '1' : '0');

        fetch(baseUrl + '/carta/cambiar-estado', {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                const label = document.getElementById('lbl_sw_prod_' + id);
                if (label) {
                    label.textContent = activo ? 'Activo' : 'Inactivo';
                }
            } else {
                switchElem.checked = !activo;
                alert('No se pudo actualizar el estado del producto.');
            }
        })
        .catch(err => {
            switchElem.checked = !activo;
            alert('Error de red al actualizar el estado.');
        });
    }

    function eliminarProducto(id, nombre) {
        if (!confirm('¿Confirma que desea eliminar el producto "' + nombre + '" de La Carta? Esta acción no se puede deshacer.')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        fetch(baseUrl + '/carta/eliminar', {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                document.querySelectorAll('#fila-producto-' + id).forEach(row => row.remove());
                location.reload();
            } else {
                alert(data.error || 'No se pudo eliminar el producto.');
            }
        })
        .catch(err => {
            alert('Ocurrió un error al intentar eliminar el producto.');
        });
    }
</script>
