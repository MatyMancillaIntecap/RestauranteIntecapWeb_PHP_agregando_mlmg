<?php
/**
 * Formulario publico para solicitar el restablecimiento de una cuenta.
 *
 * @var string|null $mensaje
 * @var string|null $error
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar restablecimiento - Restaurante INTECAP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 0; padding: 0; min-height: 100vh;
            background-image: url('<?= BASE_URL ?>/images/logo_intecap/logo_fondo_intecap.png');
            background-size: cover; background-position: center; background-repeat: no-repeat;
            display: flex; align-items: center; justify-content: center;
        }
        .login-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,.5); z-index: 1; }
        .card-container { position: relative; z-index: 2; width: 100%; max-width: 460px; padding: 15px; }
        .card-custom { background: rgba(255,255,255,.96); border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(0,0,0,.3); }
    </style>
</head>
<body>
<div class="login-overlay"></div>
<div class="card-container">
    <div class="card card-custom border-0 p-4">
        <div class="text-center mb-3">
            <img src="<?= BASE_URL ?>/images/logo_intecap/Logo-Azul-Intecap.png" alt="Logo INTECAP" style="max-height:60px;object-fit:contain;" class="mb-2">
            <h3 class="fw-bold text-dark mb-1" style="font-size:1.4rem;">Solicitar restablecimiento</h3>
            <p class="text-muted small mb-0">Ingrese su correo electrónico registrado para que un administrador revise la solicitud.</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success small mb-3"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/account/recuperar-password">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Correo electrónico</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">👤</span>
                    <input type="email" name="identificador" class="form-control" placeholder="correo@intecap.edu.gt" autocomplete="username" required>
                </div>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary fw-bold py-2" style="background-color:#1e68f7;border:none;">ENVIAR SOLICITUD</button>
            </div>
            <div class="text-center mt-2">
                <a href="<?= BASE_URL ?>/account/login" class="text-decoration-none">Volver al login</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
