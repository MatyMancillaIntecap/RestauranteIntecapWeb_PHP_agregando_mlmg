<?php
/**
 * Servicio de notificaciones por correo.
 *
 * Construye mensajes de cuenta y delega el transporte SMTP en SmtpMailer,
 * devolviendo resultados que el controlador puede mostrar como aviso o error.
 */
declare(strict_types=1);

class CorreoService
{
    private array $opciones;

    /** Carga las opciones SMTP desde config/correo.php. */
    public function __construct()
    {
        $this->opciones = require ROOT_PATH . '/config/correo.php';
    }

    /**
     * Notifica al usuario que su contraseña fue actualizada por el administrador.
     *
     * @param array{nombre?: string, email?: string} $usuario Datos del usuario receptor.
     * @param string $nuevaContrasenaPlano Contraseña en texto plano establecida temporalmente para el correo.
     * @return array{0: bool, 1: string} [éxito, mensaje de resultado]
     */
    public function enviarCorreoNotificacionCambio(array $usuario, string $nuevaContrasenaPlano): array
    {
        $nombre = !empty($usuario['nombre']) ? (string) $usuario['nombre'] : 'Usuario';
        $email = !empty($usuario['email']) ? trim((string) $usuario['email']) : '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'El usuario no tiene una dirección de correo electrónico válida registrada.'];
        }

        $asunto = 'Contraseña actualizada';
        $cuerpoHtml = $this->generarPlantillaNotificacionPassword($nombre, $nuevaContrasenaPlano);

        return $this->enviarCorreoGenerico($email, $asunto, $cuerpoHtml);
    }

    /**
     * Envía las credenciales temporales de un restablecimiento de contraseña.
     *
     * @param array{nombre?: string, email?: string} $usuario
     * @param string $nuevaPasswordTemporal
     * @return array{0: bool, 1: string}
     */
    public function enviarRestablecimientoPassword(array $usuario, string $nuevaPasswordTemporal): array
    {
        return $this->enviarCorreoNotificacionCambio($usuario, $nuevaPasswordTemporal);
    }

    /**
     * Construye la plantilla HTML del correo para la notificación de cambio de contraseña.
     */
    private function generarPlantillaNotificacionPassword(string $nombre, string $nuevaContrasenaPlano): string
    {
        $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $passwordSegura = htmlspecialchars($nuevaContrasenaPlano, ENT_QUOTES, 'UTF-8');

        // Construcción de la URL de inicio de sesión
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $protocolo = $esHttps ? 'https://' : 'http://';
        $loginUrl = $protocolo . $host . $baseUrl . '/account/login';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contraseña actualizada</title>
</head>
<body style="margin: 0; padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">
        <div style="background-color: #0d6efd; color: #ffffff; padding: 24px 20px; text-align: center;">
            <h2 style="margin: 0; font-size: 22px; font-weight: 700;">Restaurante Escuela INTECAP</h2>
        </div>
        <div style="padding: 30px 28px;">
            <p style="font-size: 16px; margin-top: 0;">Hola, <strong>{$nombreSeguro}</strong>:</p>
            <p style="font-size: 15px;">Le informamos que su contraseña ha sido actualizada por el administrador.</p>
            <p style="font-size: 15px; color: #198754; font-weight: 600;">El cambio se realizó correctamente.</p>
            
            <div style="background-color: #f8fafc; border-left: 4px solid #0d6efd; padding: 16px 20px; margin: 25px 0; border-radius: 4px;">
                <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;">Nueva contraseña:</span>
                <span style="font-size: 20px; font-weight: bold; color: #0f172a; font-family: 'Consolas', 'Courier New', monospace; letter-spacing: 1px;">{$passwordSegura}</span>
            </div>

            <p style="font-size: 15px;">Debe utilizar esta nueva contraseña para iniciar sesión en la plataforma.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{$loginUrl}" style="background-color: #0d6efd; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 15px;">Iniciar Sesión</a>
            </div>

            <p style="font-size: 13px; color: #64748b; word-break: break-all; margin-bottom: 25px;">
                O copie y pegue el siguiente enlace en su navegador:<br>
                <a href="{$loginUrl}" style="color: #0d6efd; text-decoration: underline;">{$loginUrl}</a>
            </p>

            <div style="border-top: 1px solid #e2e8f0; margin-top: 25px; padding-top: 20px;">
                <p style="font-size: 13px; color: #b02a37; background-color: #f8d7da; border: 1px solid #f5c2c7; padding: 12px 16px; border-radius: 6px; margin: 0 0 20px 0;">
                    🔒 <strong>Aviso de seguridad:</strong> No comparta su contraseña con otras personas. Le recomendamos modificarla tras iniciar sesión.
                </p>
                <p style="font-size: 14px; margin: 0; color: #475569;">
                    Atentamente,<br>
                    <strong>Restaurante Escuela INTECAP</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /** Valida configuracion y envia un mensaje HTML mediante SMTP. */
    private function enviarCorreoGenerico(string $destinatario, string $asunto, string $cuerpoHtml): array
    {
        $remitente = $this->opciones['remitente_correo'] ?: $this->opciones['usuario'];

        if ($this->opciones['host'] === '' || $this->opciones['puerto'] <= 0 || $remitente === '' || $this->opciones['usuario'] === '' || $this->opciones['contrasena'] === '') {
            return [false, 'Configuración SMTP incompleta.'];
        }

        $mailer = new SmtpMailer(
            $this->opciones['host'],
            $this->opciones['puerto'],
            $this->opciones['usar_ssl'],
            $this->opciones['usuario'],
            $this->opciones['contrasena']
        );

        return $mailer->enviarHtml($remitente, $this->opciones['remitente_nombre'], $destinatario, $asunto, $cuerpoHtml);
    }
}
