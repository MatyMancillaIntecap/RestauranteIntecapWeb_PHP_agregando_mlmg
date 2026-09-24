<?php /** Vista mostrada cuando el rol no permite acceder a una ruta. */ ?>
<div class="text-center mt-5">
    <h2 class="text-danger">Acceso denegado</h2>
    <p>No tienes permisos suficientes para acceder a esta sección.</p>
    <a href="<?= BASE_URL ?>/account/login" class="btn btn-primary">Volver al inicio</a>
</div>
