<?php
/**
 * Formulario autenticado para cambiar la contrasena propia.
 *
 * @var string|null $error
 */
?>
<div class="container-fluid px-3 px-sm-4">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8 col-12">
            <!-- // Tarjeta enmarcada con límites claros y cabecera institucional -->
            <div class="card shadow mt-4 mb-5 rounded-3 border-2" style="border: 2.5px solid #1e3a8a !important;">
                <div class="card-header text-white py-3 text-center"
                     style="background: linear-gradient(135deg, #0a2540 0%, #123d6b 50%, #1e40af 100%) !important;">
                    <h4 class="mb-0 fw-bold d-flex align-items-center justify-content-center gap-2">
                        <span>🔑</span> Cambiar Mi Contraseña
                    </h4>
                </div>
                <div class="card-body p-3 p-sm-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger fw-bold mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>/account/cambiar-password">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Contraseña actual</label>
                        <input type="password" name="contrasena_actual" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nueva contraseña</label>
                        <input type="password" name="nueva_contrasena" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Confirmar nueva contraseña</label>
                        <input type="password" name="confirmar_contrasena" class="form-control" required minlength="8" placeholder="Repite la nueva contraseña">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 fs-6 shadow"
                            style="background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important; border: 2px solid #047857;">
                        💾 Guardar Nueva Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
