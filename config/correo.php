<?php
declare(strict_types=1);
/**
 * Configuracion SMTP de la aplicacion.
 *
 * Devuelve un arreglo sin credenciales hardcodeadas; los valores se obtienen
 * del entorno para separar la configuracion local de la logica de correo.
 */

// Configuración SMTP leída de variables de entorno (nunca hardcodear credenciales reales en el código)
// En XAMPP puedes definirlas en el propio Apache (SetEnv) o cargarlas con un .env antes de este archivo.
return [
    'host' => getenv('CORREO_HOST') ?: 'smtp.gmail.com',
    'puerto' => (int) (getenv('CORREO_PUERTO') ?: 587),
    'usar_ssl' => filter_var(getenv('CORREO_USAR_SSL') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'usuario' => getenv('CORREO_USUARIO') ?: '',
    'contrasena' => getenv('CORREO_CONTRASENA') ?: '',
    'remitente_correo' => getenv('CORREO_REMITENTE') ?: '',
    'remitente_nombre' => getenv('CORREO_REMITENTE_NOMBRE') ?: 'Restaurante Escuela INTECAP',
];
