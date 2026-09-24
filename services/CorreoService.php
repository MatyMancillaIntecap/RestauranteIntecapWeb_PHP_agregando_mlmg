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

    /** Notifica al usuario que una clave existente fue modificada. */
    public function enviarCorreoNotificacionCambio(array $usuario, string $nuevaContrasenaPlano): bool
    {
        $html = "<h3>Hola, {$usuario['nombre']}</h3>"
            . '<p>Te informamos que tu contraseña ha sido actualizada exitosamente.</p>'
            . "<p>Tu nueva contraseña en texto plano es: <b>{$nuevaContrasenaPlano}</b></p>"
            . '<p>Te recomendamos cambiarla al iniciar sesión.</p>';

        [$exito] = $this->enviarCorreoGenerico($usuario['email'], 'Notificación de Cambio de Contraseña - Restaurante INTECAP', $html);
        return $exito;
    }

    // Devuelve [exito, mensaje] para que el controlador pueda informar WARN/OK como en el proyecto original
    /** Envia las credenciales temporales de un restablecimiento. */
    public function enviarRestablecimientoPassword(array $usuario, string $nuevaPasswordTemporal): array
    {
        $html = "<h3>Hola, {$usuario['nombre']}</h3>"
            . '<p>Has solicitado o un administrador ha restablecido tu contraseña.</p>'
            . "<p>Tus nuevas credenciales temporales son: <b>{$nuevaPasswordTemporal}</b></p>";

        return $this->enviarCorreoGenerico($usuario['email'], 'Notificación de Cambio de Contraseña - Restaurante INTECAP', $html);
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
