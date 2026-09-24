<?php
/**
 * Formulario autenticado para cambiar la contrasena propia.
 *
 * @var string|null $error
 */
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Cambiar Contraseña</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>/account/cambiar-password">
                    <div class="mb-3">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" name="contrasena_actual" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" name="nueva_contrasena" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="confirmar_contrasena" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
