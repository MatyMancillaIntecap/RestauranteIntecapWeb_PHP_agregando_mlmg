/**
 * site.js — JavaScript global del Restaurante Escuela INTECAP
 * Equivalente funcional a wwwroot/js/site.js del proyecto C#
 */
document.addEventListener('DOMContentLoaded', function () {

    // --- Auto-cierre de alertas flash después de 6 segundos ----------------
    document.querySelectorAll('.alert[role="alert"]').forEach(function (alerta) {
        setTimeout(function () {
            // Usar el método dismiss de Bootstrap si está disponible
            const bsAlert = window.bootstrap && bootstrap.Alert.getOrCreateInstance
                ? bootstrap.Alert.getOrCreateInstance(alerta)
                : null;
            if (bsAlert) {
                bsAlert.close();
            } else {
                alerta.classList.add('d-none');
            }
        }, 6000);
    });

    // --- Confirmación global para botones con data-confirm -----------------
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const mensaje = btn.getAttribute('data-confirm') || '¿Estás seguro?';
            if (!confirm(mensaje)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    });

    // --- Activar tooltips de Bootstrap ------------------------------------
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    // --- Formatear inputs de tipo number para evitar valores negativos -----
    document.querySelectorAll('input[type="number"][min="0"]').forEach(function (input) {
        input.addEventListener('change', function () {
            if (parseFloat(this.value) < 0) this.value = 0;
        });
    });

});
