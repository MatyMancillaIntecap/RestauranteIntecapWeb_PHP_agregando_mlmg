<?php
/**
 * Vista de error generico; recibe el mensaje opcional del controlador.
 *
 * @var string|null $mensaje
 */
?>
<div class="container mt-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="fs-1 mb-3">⚠️</div>
            <h2 class="text-danger fw-bold">Ha ocurrido un error</h2>
            <p class="text-muted">
                <?= htmlspecialchars($mensaje ?? 'Ha ocurrido un error inesperado en el servidor.') ?>
            </p>
            <a href="<?= BASE_URL ?>/account/login" class="btn btn-primary mt-2">
                🏠 Volver al inicio
            </a>
        </div>
    </div>
</div>
