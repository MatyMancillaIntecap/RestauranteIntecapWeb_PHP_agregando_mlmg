<?php
/**
 * Menu y carrito del empleado para seleccionar y confirmar almuerzos.
 *
 * @var array $menus
 * @var int $usuario_id
 * @var int $limite_maximo
 * @var int $reservas_hoy
 * @var string $nit_usuario
 */
?>
<div class="container-fluid container-lg mt-3 mb-5 px-3 px-sm-4">

    <!-- // BARRA SUPERIOR CON COLORES FUERTES INTECAP -->
    <div class="card shadow mb-4 rounded-3 text-white"
         style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%); border: 2.5px solid #0f2b48 !important;">
        <div class="card-body p-4">
            <h3 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                <span>🍽️</span> Menú del Día (<?= date('d/m/Y') ?>)
            </h3>
            <p class="text-white-50 mb-0">
                Selecciona hasta un máximo de
                <strong class="text-white"><?= (int)$limite_maximo === 0 ? 'ilimitado' : (int)$limite_maximo ?></strong>
                almuerzos por día e indica la forma de pago para cada uno.
            </p>
        </div>
    </div>

    <div class="row">
        <?php if ($limite_maximo > 0 && $reservas_hoy >= $limite_maximo): ?>
            <div class="col-12 mb-3">
                <div class="alert alert-warning text-center py-3 fw-bold mb-0">
                    ⚠️ Has alcanzado tu límite diario de <?= (int)$limite_maximo ?> almuerzos. Ya no puedes reservar más platillos hoy.
                </div>
            </div>
        <?php endif; ?>

        <!-- Cada tarjeta expone los datos que el carrito necesita para el POST JSON. -->
        <!-- LISTA DE PLATILLOS -->
        <div class="col-xl-8 col-lg-7 col-12 mb-4">
            <div class="row">
                <?php if (empty($menus)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center py-4">
                            No hay platillos disponibles para reservar en este momento.
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($menus as $m): ?>
                    <div class="col-12 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm rounded-3 overflow-hidden">
                            <?php if (!empty($m['imagen_url'])): ?>
                                <img src="<?= resolve_image_url($m['imagen_url']) ?>"
                                     class="card-img-top" style="height:180px;object-fit:cover;"
                                     alt="<?= htmlspecialchars($m['nombre_plato']) ?>">
                            <?php else: ?>
                                <div class="bg-secondary text-white text-center py-5">Sin Imagen</div>
                            <?php endif; ?>

                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title fw-bold mb-0">
                                        <?= htmlspecialchars($m['nombre_plato']) ?>
                                    </h5>
                                    <?php if ($m['es_dieta']): ?>
                                        <span class="badge bg-info text-dark">🌿 Dieta</span>
                                    <?php endif; ?>
                                    <span class="badge bg-success">Disponible</span>
                                </div>

                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($m['descripcion'] ?? '') ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-5 fw-bold text-primary">
                                        Q <?= number_format((float)$m['precio'], 2) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        Stock: <?= (int)$m['stock'] ?>
                                    </span>
                                </div>

                                <div class="border-top pt-3">
                                    <!-- Selector de cantidad con botones +/- táctiles -->
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">Cantidad:</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary px-3 py-2 fw-bold" type="button"
                                                    onclick="cambiarCantidad(<?= (int)$m['id'] ?>, -1)">−</button>
                                            <input type="number" id="cantidad_<?= (int)$m['id'] ?>"
                                                   class="form-control text-center fw-bold fs-6"
                                                   value="1" min="1" max="<?= (int)$m['stock'] ?>" readonly>
                                            <button class="btn btn-outline-secondary px-3 py-2 fw-bold" type="button"
                                                    onclick="cambiarCantidad(<?= (int)$m['id'] ?>, 1, <?= (int)$m['stock'] ?>)">+</button>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">Forma de Pago:</label>
                                        <select id="pago_<?= (int)$m['id'] ?>" class="form-select">
                                            <option value="1">💵 Efectivo</option>
                                            <option value="2">💳 Carnet</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">Consumo:</label>
                                        <select id="lugar_<?= (int)$m['id'] ?>" class="form-select">
                                            <option value="En restaurante">🍽️ En restaurante</option>
                                            <option value="Para llevar">🛍️ Para llevar</option>
                                        </select>
                                    </div>

                                    <button class="btn btn-primary w-100 fw-bold py-2 mt-2"
                                            onclick="agregarAlCarrito(<?= (int)$m['id'] ?>, '<?= addslashes(htmlspecialchars($m['nombre_plato'])) ?>', <?= (float)$m['precio'] ?>, <?= (int)$m['stock'] ?>)">
                                        🛒 Seleccionar Platillo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PANEL LATERAL: CARRITO ENMARCADO -->
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card shadow rounded-3 sticky-panel-lg border-2">
                <div class="card-header text-white py-3" style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%) !important;">
                    <h5 class="mb-0 fw-bold d-flex justify-content-between align-items-center">
                        <span>🛍️ Mi Solicitud</span>
                        <span class="badge bg-warning text-dark fw-bold shadow-sm">
                            <span id="countPlatillos">0</span>/<?= (int)$limite_maximo === 0 ? '∞' : (int)$limite_maximo ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div id="listaCarrito" class="mb-3">
                        <p class="text-muted text-center py-3">No has seleccionado platillos aún.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIT para Facturación:</label>
                        <input type="text" id="nitInput" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($nit_usuario) ?>"
                               placeholder="C/F o 1-13 dígitos">
                    </div>

                    <hr>

                    <!-- TOTAL A PAGAR ENMARCADO CON COLOR FUERTE -->
                    <div class="p-3 rounded-3 text-center mb-3 text-white shadow-sm"
                         style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: 2px solid #047857;">
                        <span class="text-uppercase small fw-bold text-white-50 d-block" style="letter-spacing: 1px;">TOTAL A PAGAR</span>
                        <div id="totalPagar" class="fs-2 fw-bold text-white mt-1">Q 0.00</div>
                    </div>

                    <button id="btnConfirmar" class="btn btn-success w-100 fw-bold fs-6 py-2 shadow"
                            onclick="confirmarReserva()" disabled
                            style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%) !important; border: 2px solid #14532d;">
                        ✅ Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Espaciador para evitar solapamiento con la barra móvil -->
    <div class="d-lg-none" style="height: 70px;"></div>
</div>

<!-- BARRA FLOTANTE MÓVIL DEL CARRITO (Sólo visible en móviles cuando hay platillos seleccionados) -->
<div id="barraMovilEmpleado" class="fixed-bottom bg-white border-top shadow-lg p-2 d-lg-none d-none" style="z-index: 1040; padding-bottom: max(0.5rem, env(safe-area-inset-bottom)) !important;">
    <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
        <div>
            <div class="small text-muted"><span id="cantMovil">0</span> platillo(s) en orden</div>
            <div class="fw-bold text-success fs-5" id="totalMovil">Q 0.00</div>
        </div>
        <button class="btn btn-success fw-bold px-3 py-2 shadow-sm" onclick="irAlCarritoMovil()">
            🛍️ Ver Mi Solicitud
        </button>
    </div>
</div>

<!-- MODAL LÍMITE ALCANZADO -->
<div class="modal fade" id="modalLimiteAlcanzado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                <div class="fs-1 text-warning mb-2">⚠️</div>
                <h4 class="fw-bold text-dark mb-2">Límite de Reservas Alcanzado</h4>
                <p id="mensajeLimite" class="text-muted mb-4">
                    Has alcanzado el límite máximo permitido de almuerzos por día para tu cuenta.
                </p>
                <button type="button" class="btn btn-primary fw-bold px-4" data-bs-dismiss="modal">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const USUARIO_ID = <?= (int)$usuario_id ?>;
const BASE_URL   = '<?= BASE_URL ?>';
const LIMITE_MAX = <?= (int)$limite_maximo ?>;   // 0 = ilimitado
const RESERVAS_HOY = <?= (int)($reservas_hoy ?? 0) ?>;
const RESTANTE_HOY = LIMITE_MAX > 0 ? Math.max(0, LIMITE_MAX - RESERVAS_HOY) : 999999;

let carrito = [];

function cambiarCantidad(idPlatillo, cambio, stockMaximo) {
    const input = document.getElementById('cantidad_' + idPlatillo);
    if (!input) return;

    let nuevoValor = (parseInt(input.value) || 1) + cambio;
    if (nuevoValor < 1) nuevoValor = 1;

    let techo = stockMaximo || 9999;
    if (LIMITE_MAX > 0) {
        techo = Math.min(techo, RESTANTE_HOY);
    }
    if (RESTANTE_HOY <= 0) {
        nuevoValor = 0;
    } else if (nuevoValor > techo) {
        nuevoValor = techo;
    }

    input.value = nuevoValor;
}

function agregarAlCarrito(id, nombre, precio, stockMaximo) {
    const cantidad = parseInt(document.getElementById('cantidad_' + id).value) || 1;
    const pagoSel  = document.getElementById('pago_' + id);
    const pagoId   = parseInt(pagoSel.value);
    const pagoTxt  = pagoSel.options[pagoSel.selectedIndex].text;
    const lugar    = document.getElementById('lugar_' + id).value;

    if (LIMITE_MAX > 0 && RESTANTE_HOY <= 0) {
        document.getElementById('mensajeLimite').textContent =
            'Ya has alcanzado tu límite diario de ' + LIMITE_MAX + ' almuerzos.';
        new bootstrap.Modal(document.getElementById('modalLimiteAlcanzado')).show();
        return;
    }

    if (cantidad > stockMaximo) {
        alert('No puedes pedir más de ' + stockMaximo + ' unidades disponibles.');
        return;
    }

    const totalActual = carrito.reduce((s, i) => s + i.cantidad, 0);
    const totalConCantidad = totalActual + cantidad;
    if (LIMITE_MAX > 0 && (totalConCantidad + RESERVAS_HOY) > LIMITE_MAX) {
        const yaUsados = Math.min(RESERVAS_HOY, LIMITE_MAX);
        document.getElementById('mensajeLimite').textContent =
            'No puedes agregar esta cantidad. El sistema permite un máximo de ' + LIMITE_MAX +
            ' almuerzos por día para tu cuenta y ya has consumido ' + yaUsados + ' en este día.';
        new bootstrap.Modal(document.getElementById('modalLimiteAlcanzado')).show();
        return;
    }

    const existente = carrito.find(i => i.menuId === id && i.formaPagoId === pagoId && i.dondeConsume === lugar);
    if (existente) {
        if (existente.cantidad + cantidad > stockMaximo) {
            alert('La cantidad total en el carrito supera el stock disponible.');
            return;
        }
        existente.cantidad += cantidad;
    } else {
        carrito.push({ menuId: id, nombre, precio, cantidad, formaPagoId: pagoId, formaPagoTexto: pagoTxt, dondeConsume: lugar });
    }

    renderizarCarrito();
}

function renderizarCarrito() {
    const contenedor = document.getElementById('listaCarrito');
    const barraMovil = document.getElementById('barraMovilEmpleado');

    if (carrito.length === 0) {
        contenedor.innerHTML = '<p class="text-muted text-center py-3">No has seleccionado platillos aún.</p>';
        document.getElementById('totalPagar').textContent  = 'Q 0.00';
        document.getElementById('countPlatillos').textContent = '0';
        document.getElementById('btnConfirmar').disabled  = true;
        if (barraMovil) barraMovil.classList.add('d-none');
        return;
    }

    let html = '';
    let total = 0, cantTotal = 0;

    carrito.forEach((item, idx) => {
        total    += item.precio * item.cantidad;
        cantTotal += item.cantidad;
        html += `<div class="p-2 mb-2 bg-light rounded border">
            <div class="d-flex justify-content-between align-items-center">
                <strong class="text-primary">${item.nombre}</strong>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${idx})">❌</button>
            </div>
            <div class="small text-muted">
                Cant: ${item.cantidad} | Q ${item.precio.toFixed(2)} c/u<br>
                Pago: <span class="badge bg-info text-dark">${item.formaPagoTexto}</span>
                (${item.dondeConsume})
            </div>
        </div>`;
    });

    contenedor.innerHTML = html;
    document.getElementById('totalPagar').textContent     = 'Q ' + total.toFixed(2);
    document.getElementById('countPlatillos').textContent = cantTotal;
    document.getElementById('btnConfirmar').disabled      = false;

    if (barraMovil) {
        barraMovil.classList.remove('d-none');
        const cantSpan = document.getElementById('cantMovil');
        const totalSpan = document.getElementById('totalMovil');
        if (cantSpan) cantSpan.textContent = cantTotal;
        if (totalSpan) totalSpan.textContent = 'Q ' + total.toFixed(2);
    }
}

function irAlCarritoMovil() {
    const el = document.getElementById('listaCarrito');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function eliminarDelCarrito(idx) {
    carrito.splice(idx, 1);
    renderizarCarrito();
}

function confirmarReserva() {
    if (carrito.length === 0) return;

    let nit = document.getElementById('nitInput').value.trim() || 'C/F';
    if (!/^(?:C\/F|\d{1,13}(?:-\d)?)$/i.test(nit)) {
        document.getElementById('mensajeLimite').innerHTML =
            '<strong>Formato de NIT Incorrecto</strong><br>El NIT debe contener entre 1 y 13 dígitos, con guion y dígito verificador opcional, o indicar \'C/F\'.';
        new bootstrap.Modal(document.getElementById('modalLimiteAlcanzado')).show();
        return;
    }

    const btn = document.getElementById('btnConfirmar');
    btn.disabled = true;
    btn.textContent = '⏳ Procesando...';

    fetch(BASE_URL + '/empleado/realizar-reserva', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            usuarioId: USUARIO_ID,
            nitFacturacion: nit,
            platillos: carrito.map(i => ({
                menuId: i.menuId,
                cantidad: i.cantidad,
                formaPagoId: i.formaPagoId,
                dondeConsume: i.dondeConsume
            }))
        })
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.error || 'Error al reservar');
        return data;
    })
    .then(data => {
        alert(data.mensaje);
        window.location.href = BASE_URL + '/empleado/historial';
    })
    .catch(err => {
        document.getElementById('mensajeLimite').textContent = err.message;
        new bootstrap.Modal(document.getElementById('modalLimiteAlcanzado')).show();
        btn.disabled = false;
        btn.textContent = '✅ Confirmar Reserva';
    });
}
</script>
