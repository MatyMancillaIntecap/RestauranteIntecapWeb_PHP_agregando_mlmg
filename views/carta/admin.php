<?php
/**
 * Panel de administración de La Carta (exclusivo para Administrador).
 *
 * Permite crear, editar, activar/desactivar, eliminar y subir imágenes
 * para los productos organizados por las 4 categorías:
 * Entrada, Plato fuerte, Bebida y Postre.
 *
 * @var array<string, array<int, array<string, mixed>>> $productosPorCategoria
 * @var array<int, string> $categorias
 */

$iconosCategorias = [
    'Entrada' => '🥗',
    'Plato fuerte' => '🥩',
    'Bebida' => '🥤',
    'Postre' => '🍰',
];
?>
<div class="container-fluid mt-3 mb-5">

    <!-- BARRA SUPERIOR -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body bg-light p-3">
            <div class="row align-items-center">
                <div class="col-md-7 col-12">
                    <h3 class="text-primary fw-bold mb-1">⚙️ Administración de La Carta</h3>
                    <p class="text-muted mb-0">
                        Gestiona los productos disponibles para los comensales, organizados por categoría, precios e imágenes.
                    </p>
                </div>
                <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                    <a href="<?= BASE_URL ?>/carta/index" class="btn btn-outline-primary fw-bold">
                        👁️ Ver Vista de Cliente
                    </a>
                    <button type="button" class="btn btn-primary fw-bold" onclick="abrirModalNuevoProducto()">
                        ➕ Nuevo Producto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS POR CATEGORÍA -->
    <ul class="nav nav-pills mb-4 nav-fill bg-white p-2 rounded-3 shadow-sm border" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="tab-todas" data-bs-toggle="pill" data-bs-target="#pane-todas" type="button" role="tab">
                📋 Todas las Categorías
            </button>
        </li>
        <?php foreach ($categorias as $cat): ?>
            <?php $catKey = strtolower(str_replace(' ', '_', $cat)); ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-<?= $catKey ?>" data-bs-toggle="pill" data-bs-target="#pane-<?= $catKey ?>" type="button" role="tab">
                    <?= $iconosCategorias[$cat] ?? '🍽️' ?> <?= htmlspecialchars($cat) ?>
                    <span class="badge bg-secondary ms-1"><?= count($productosPorCategoria[$cat] ?? []) ?></span>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- CONTENIDO DE LAS PESTAÑAS -->
    <div class="tab-content" id="pills-tabContent">

        <!-- PESTAÑA: TODAS LAS CATEGORÍAS -->
        <div class="tab-pane fade show active" id="pane-todas" role="tabpanel">
            <?php foreach ($categorias as $cat): ?>
                <?php
                $catKey = strtolower(str_replace(' ', '_', $cat));
                $productos = $productosPorCategoria[$cat] ?? [];
                ?>
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <?= $iconosCategorias[$cat] ?? '🍽️' ?> <?= htmlspecialchars($cat) ?>
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="abrirModalNuevoProducto('<?= htmlspecialchars($cat, ENT_QUOTES) ?>')">
                            ➕ Agregar a <?= htmlspecialchars($cat) ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php renderizarTablaProductos($productos, $cat); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PESTAÑAS INDIVIDUALES POR CATEGORÍA -->
        <?php foreach ($categorias as $cat): ?>
            <?php
            $catKey = strtolower(str_replace(' ', '_', $cat));
            $productos = $productosPorCategoria[$cat] ?? [];
            ?>
            <div class="tab-pane fade" id="pane-<?= $catKey ?>" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <?= $iconosCategorias[$cat] ?? '🍽️' ?> Productos de <?= htmlspecialchars($cat) ?>
                        </h5>
                        <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="abrirModalNuevoProducto('<?= htmlspecialchars($cat, ENT_QUOTES) ?>')">
                            ➕ Nuevo Producto en <?= htmlspecialchars($cat) ?>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php renderizarTablaProductos($productos, $cat); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>

<?php
/**
 * Función auxiliar para renderizar la tabla de productos de una categoría.
 */
function renderizarTablaProductos(array $productos, string $cat): void
{
    if (empty($productos)): ?>
        <div class="text-center py-4 text-muted small">
            No se han registrado productos en la categoría <strong><?= htmlspecialchars($cat) ?></strong>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Imagen</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th style="width: 120px;">Precio</th>
                        <th style="width: 130px;">Estado</th>
                        <th class="text-center" style="width: 170px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr id="fila-producto-<?= (int)$p['id'] ?>">
                            <td>
                                <?php if (!empty($p['imagen'])): ?>
                                    <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>"
                                         alt="<?= htmlspecialchars($p['nombre']) ?>"
                                         class="rounded border"
                                         style="width: 60px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted"
                                         style="width: 60px; height: 50px; font-size: 1.3rem;">
                                        🍽️
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($p['nombre']) ?></strong>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    <?= htmlspecialchars($p['descripcion'] ?? 'Sin descripción') ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-primary">
                                    Q <?= number_format((float)$p['precio'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="sw_prod_<?= (int)$p['id'] ?>"
                                           <?= $p['estado'] ? 'checked' : '' ?>
                                           onchange="cambiarEstadoProducto(<?= (int)$p['id'] ?>, this.checked, this)">
                                    <label class="form-check-label small fw-bold"
                                           id="lbl_sw_prod_<?= (int)$p['id'] ?>"
                                           for="sw_prod_<?= (int)$p['id'] ?>">
                                        <?= $p['estado'] ? 'Activo' : 'Inactivo' ?>
                                    </label>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold me-1"
                                        onclick="abrirModalEditarProducto(<?= (int)$p['id'] ?>)">
                                    ✏️ Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold"
                                        onclick="eliminarProducto(<?= (int)$p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>')">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif;
}
?>

<!-- ═══════════════════════════════════════════════════════
     MODAL — CREAR / EDITAR PRODUCTO DE LA CARTA
═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTitulo">➕ Nuevo Producto para La Carta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= BASE_URL ?>/carta/guardar" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="prod_id" name="id" value="0">

                    <!-- Categoría -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                        <select id="prod_categoria" name="categoria" class="form-select" required>
                            <option value="">-- Selecciona una categoría --</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                                    <?= $iconosCategorias[$c] ?? '' ?> <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Nombre del Producto -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" id="prod_nombre" name="nombre" class="form-control"
                               placeholder="Ej. Ensalada César, Pasta Alfredo, Limonada..." required maxlength="100">
                    </div>

                    <!-- Descripción -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea id="prod_descripcion" name="descripcion" class="form-control" rows="2"
                                  placeholder="Detalles sobre ingredientes, preparación o porción..."></textarea>
                    </div>

                    <!-- Precio -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Precio (Quetzales) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Q</span>
                            <input type="number" id="prod_precio" name="precio" class="form-control"
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- Imagen -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fotografía / Imagen (Opcional)</label>
                        <input type="file" id="prod_imagen_file" name="imagen_file" class="form-control"
                               accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        <div id="preview_imagen_actual" class="mt-2 d-none">
                            <span class="small text-muted d-block mb-1">Imagen actual:</span>
                            <img src="" id="img_preview" class="rounded border" style="max-height: 80px;">
                        </div>
                        <small class="text-muted d-block mt-1">Formatos soportados: JPG, PNG, GIF, WEBP o AVIF.</small>
                    </div>

                    <!-- Estado Activo -->
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="prod_estado" name="estado" value="1" checked>
                        <label class="form-check-label fw-bold" for="prod_estado">
                            Producto Activo / Visible para comensales
                        </label>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="btnGuardar">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?= BASE_URL ?>';

    function abrirModalNuevoProducto(categoriaPrevia = '') {
        document.getElementById('modalTitulo').textContent = '➕ Nuevo Producto para La Carta';
        document.getElementById('prod_id').value = '0';
        document.getElementById('prod_nombre').value = '';
        document.getElementById('prod_descripcion').value = '';
        document.getElementById('prod_precio').value = '';
        document.getElementById('prod_imagen_file').value = '';
        document.getElementById('prod_estado').checked = true;
        document.getElementById('preview_imagen_actual').classList.add('d-none');

        if (categoriaPrevia) {
            document.getElementById('prod_categoria').value = categoriaPrevia;
        } else {
            document.getElementById('prod_categoria').value = '';
        }

        new bootstrap.Modal(document.getElementById('modalProducto')).show();
    }

    function abrirModalEditarProducto(id) {
        fetch(baseUrl + '/carta/obtener-producto-por-id/' + id)
            .then(res => {
                if (!res.ok) throw new Error('No se pudo cargar el producto');
                return res.json();
            })
            .then(data => {
                document.getElementById('modalTitulo').textContent = '✏️ Editar Producto: ' + data.nombre;
                document.getElementById('prod_id').value = data.id;
                document.getElementById('prod_categoria').value = data.categoria;
                document.getElementById('prod_nombre').value = data.nombre;
                document.getElementById('prod_descripcion').value = data.descripcion || '';
                document.getElementById('prod_precio').value = data.precio;
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

                new bootstrap.Modal(document.getElementById('modalProducto')).show();
            })
            .catch(err => {
                alert('Error al obtener la información del producto.');
            });
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
                // Eliminar fila de la tabla si existe
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
