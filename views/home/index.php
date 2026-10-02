<?php /** Vista publica de bienvenida y acceso al login. */ ?>
<div class="container my-5 py-4">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-7 col-md-9 col-12 text-center">
            <div class="card shadow-lg rounded-4 border-0 p-4 p-md-5">
                <div class="card-body">
                    <img src="<?= BASE_URL ?>/images/intecap_logo.png" alt="INTECAP" class="mb-4" style="max-height: 80px; object-fit: contain;">
                    <h2 class="fw-bold text-dark mb-2">Restaurante Escuela INTECAP</h2>
                    <p class="text-muted lead mb-4">
                        Sistema institucional de reservas y pedidos de gastronomía y almuerzos.
                    </p>
                    <a href="<?= BASE_URL ?>/account/login" class="btn btn-primary btn-lg fw-bold px-4 py-2 shadow">
                        🔑 Iniciar Sesión en el Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
