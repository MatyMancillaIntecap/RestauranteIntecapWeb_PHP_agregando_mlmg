<?php /** Vista mostrada cuando el rol no permite acceder a una ruta. */ ?>
<div class="container my-5 py-4">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8 col-12">
            <div class="card shadow rounded-3 border-2 border-danger text-center p-4">
                <div class="card-body">
                    <div class="display-3 text-danger mb-3">⛔</div>
                    <h3 class="fw-bold text-danger mb-2">Acceso Denegado</h3>
                    <p class="text-muted mb-4">
                        Tu cuenta no cuenta con los permisos o el rol requerido para acceder a esta sección del sistema.
                    </p>
                    <a href="<?= BASE_URL ?>/account/login" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                        🏠 Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
