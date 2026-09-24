<?php
/**
 * Formulario independiente de inicio de sesion.
 *
 * Muestra errores, permite recordar la sesion y alternar la visibilidad de la
 * contrasena antes de enviar las credenciales al AccountController.
 *
 * @var string|null $error
 * @var string $email
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Restaurante INTECAP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 0; padding: 0; height: 100vh;
            background-image: url('<?= BASE_URL ?>/images/logo_intecap/logo_fondo_intecap.png');
            background-size: cover; background-position: center; background-repeat: no-repeat;
            display: flex; align-items: center; justify-content: center;
        }
        .login-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,.5); z-index: 1; }
        .login-card-container { position: relative; z-index: 2; width: 100%; max-width: 420px; padding: 15px; }
        .card-custom { background: rgba(255,255,255,.95); border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(0,0,0,.3); }
        .password-toggle {
            border-left: 0;
            background: #fff;
            cursor: pointer;
            min-width: 42px;
        }
        .password-toggle:focus { box-shadow: none; }
    </style>
</head>
<body>
<div class="login-overlay"></div>
<div class="login-card-container">
    <div class="card card-custom border-0 p-4">
        <div class="text-center mb-3">
            <img src="<?= BASE_URL ?>/images/logo_intecap/Logo-Azul-Intecap.png" alt="Logo INTECAP" style="max-height:60px;object-fit:contain;" class="mb-2">
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/account/login">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">👤</span>
                    <input type="email" name="email" class="form-control" placeholder="admin@intecap.com" autocomplete="username" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Contraseña</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">🔒</span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••" autocomplete="current-password" required>
                    <button type="button" class="btn btn-outline-secondary password-toggle" id="togglePassword" aria-label="Mostrar contraseña" title="Mostrar contraseña">
                        👁️
                    </button>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="recordarme" value="1" class="form-check-input" id="recordarme">
                <label class="form-check-label small" for="recordarme">Recordarme</label>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary fw-bold py-2" style="background-color:#1e68f7;border:none;">ACCEDER / LOG IN</button>
            </div>
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/account/recuperar-password" class="text-decoration-none">Restablecer Contraseña</a>
            </div>
        </form>
    </div>
</div>
<script>
    // Alterna la visibilidad del campo sin alterar el valor enviado.
    const passwordInput = document.getElementById('passwordInput');
    const togglePassword = document.getElementById('togglePassword');

    if (passwordInput && togglePassword) {
        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.textContent = isPassword ? '🙈' : '👁️';
            togglePassword.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            togglePassword.setAttribute('title', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    }
</script>
</body>
</html>
