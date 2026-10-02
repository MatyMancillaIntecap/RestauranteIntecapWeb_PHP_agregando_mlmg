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
            margin: 0; padding: 1.5rem 1rem; min-height: 100vh; min-height: 100dvh;
            background-image: url('<?= BASE_URL ?>/images/logo_intecap/logo_fondo_intecap.png');
            background-size: cover; background-position: center; background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex; align-items: center; justify-content: center;
            box-sizing: border-box;
        }
        .login-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,.52); z-index: 1; }
        .login-card-container { position: relative; z-index: 2; width: 100%; max-width: 440px; margin: auto; }
        .card-custom { background: rgba(255,255,255,.96); border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(0,0,0,.35); }
        .password-toggle {
            border-left: 0;
            background: #fff;
            cursor: pointer;
            min-width: 42px;
        }
        .password-toggle:focus { box-shadow: none; }
        .anuncios-contenedor { position: relative; }
        .anuncio-card {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #f59e0b;
            border-radius: 0.75rem;
            text-align: left;
        }
        .anuncio-badge {
            background: #d97706;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            padding: 3px 8px;
            border-radius: 12px;
        }
        .anuncio-texto {
            color: #78350f;
            font-size: 0.83rem;
            font-weight: 600;
            line-height: 1.35;
            margin-top: 3px;
        }
        .anuncio-punto-activo {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
            animation: pulse-verde 2s infinite;
        }
        @keyframes pulse-verde {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body>
<div class="login-overlay"></div>
<div class="login-card-container">
    <div class="card card-custom border-0 p-4">
        <div class="text-center mb-3">
            <img src="<?= BASE_URL ?>/images/logo_intecap/Logo-Azul-Intecap.png" alt="Logo INTECAP" style="max-height:60px;object-fit:contain;" class="mb-2">
        </div>

        <!-- ═══════════════════════════════════════════════════════
             APARTADO DE ANUNCIOS ACTIVOS (ENTRE LOGO Y CORREO)
        ═══════════════════════════════════════════════════════ -->
        <?php if (!empty($anuncios)): ?>
            <div class="anuncios-contenedor mb-3">
                <?php if (count($anuncios) === 1): ?>
                    <?php $a = $anuncios[0]; ?>
                    <div class="anuncio-card p-2 px-3 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge anuncio-badge d-inline-flex align-items-center gap-1">
                                <span>📢</span>
                                <span><?= htmlspecialchars($a['titulo']) ?></span>
                            </span>
                            <span class="anuncio-punto-activo" title="Aviso activo"></span>
                        </div>
                        <div class="anuncio-texto">
                            <?= nl2br(htmlspecialchars($a['mensaje'])) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Carrusel automático para múltiples avisos -->
                    <div id="carouselAnunciosLogin" class="carousel slide carousel-fade shadow-sm rounded-3 overflow-hidden" data-bs-ride="carousel" data-bs-interval="4500">
                        <div class="carousel-inner">
                            <?php foreach ($anuncios as $idx => $a): ?>
                                <div class="carousel-item <?= ($idx === 0) ? 'active' : '' ?>">
                                    <div class="anuncio-card p-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge anuncio-badge d-inline-flex align-items-center gap-1">
                                                <span>📢</span>
                                                <span><?= htmlspecialchars($a['titulo']) ?></span>
                                            </span>
                                            <span class="badge bg-white text-secondary border small px-2 py-0" style="font-size: 0.65rem;">
                                                <?= ($idx + 1) ?> / <?= count($anuncios) ?>
                                            </span>
                                        </div>
                                        <div class="anuncio-texto">
                                            <?= nl2br(htmlspecialchars($a['mensaje'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($anuncios) > 1): ?>
                            <div class="carousel-indicators position-static mt-1 mb-0 pb-0">
                                <?php foreach ($anuncios as $idx => $a): ?>
                                    <button type="button" data-bs-target="#carouselAnunciosLogin" data-bs-slide-to="<?= $idx ?>"
                                            class="<?= ($idx === 0) ? 'active' : '' ?>"
                                            aria-label="Aviso <?= ($idx + 1) ?>"
                                            style="width: 8px; height: 8px; border-radius: 50%; background-color: #d97706; margin: 0 3px;"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
