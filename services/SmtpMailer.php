<?php
declare(strict_types=1);
/**
 * Cliente SMTP minimo basado en sockets PHP.
 *
 * Gestiona autenticacion LOGIN, TLS opcional y envio de mensajes HTML sin
 * depender de Composer ni de una libreria externa.
 */

class SmtpMailer
{
    private string $host;
    private int $puerto;
    private bool $usarSsl;
    private string $usuario;
    private string $contrasena;

    /** Guarda los datos de conexion del servidor SMTP. */
    public function __construct(string $host, int $puerto, bool $usarSsl, string $usuario, string $contrasena)
    {
        $this->host = $host;
        $this->puerto = $puerto;
        $this->usarSsl = $usarSsl;
        $this->usuario = $usuario;
        $this->contrasena = $contrasena;
    }

    /**
     * Envia un correo HTML.
     *
     * @return array{0: bool, 1: string} Resultado y mensaje para la interfaz.
     */
    public function enviarHtml(string $remitenteCorreo, string $remitenteNombre, string $destinatario, string $asunto, string $cuerpoHtml): array
    {
        if ($this->host === '' || $this->puerto <= 0 || $this->usuario === '' || $this->contrasena === '' || $destinatario === '') {
            return [false, 'Configuración SMTP incompleta o destinatario vacío.'];
        }

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL) || !filter_var($remitenteCorreo, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Formato de correo inválido.'];
        }

        $transporte = $this->puerto === 465 ? 'ssl://' : '';
        $socket = @stream_socket_client($transporte . $this->host . ':' . $this->puerto, $errno, $errstr, 15);

        if (!$socket) {
            return [false, "No se pudo conectar al servidor SMTP: {$errstr}"];
        }

        try {
            $this->leerRespuesta($socket);
            $this->comando($socket, "EHLO {$this->host}", 250);

            if ($this->usarSsl && $this->puerto !== 465) {
                $this->comando($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return [false, 'No se pudo iniciar TLS con el servidor SMTP.'];
                }
                $this->comando($socket, "EHLO {$this->host}", 250);
            }

            $this->comando($socket, 'AUTH LOGIN', 334);
            $this->comando($socket, base64_encode($this->usuario), 334);
            $this->comando($socket, base64_encode($this->contrasena), 235);

            $this->comando($socket, "MAIL FROM:<{$remitenteCorreo}>", 250);
            $this->comando($socket, "RCPT TO:<{$destinatario}>", 250);
            $this->comando($socket, 'DATA', 354);

            $cabeceras = "From: {$remitenteNombre} <{$remitenteCorreo}>\r\n";
            $cabeceras .= "To: <{$destinatario}>\r\n";
            $cabeceras .= 'Subject: ' . '=?UTF-8?B?' . base64_encode($asunto) . "?=\r\n";
            $cabeceras .= "MIME-Version: 1.0\r\n";
            $cabeceras .= "Content-Type: text/html; charset=UTF-8\r\n";

            $cuerpo = str_replace("\n.", "\n..", $cuerpoHtml);

            $this->comando($socket, $cabeceras . "\r\n" . $cuerpo . "\r\n.", 250);
            $this->comando($socket, 'QUIT', 221);
        } catch (RuntimeException $e) {
            fclose($socket);
            return [false, $e->getMessage()];
        }

        fclose($socket);
        return [true, 'Correo aceptado por el servidor SMTP.'];
    }

    /** Envia un comando SMTP y valida el codigo esperado. */
    private function comando($socket, string $comando, int $codigoEsperado): string
    {
        fwrite($socket, $comando . "\r\n");
        return $this->leerRespuesta($socket, $codigoEsperado);
    }

    /** Lee la respuesta multilinea del servidor SMTP. */
    private function leerRespuesta($socket, ?int $codigoEsperado = null): string
    {
        $respuesta = '';
        while ($linea = fgets($socket, 515)) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }

        if ($codigoEsperado !== null && !str_starts_with($respuesta, (string) $codigoEsperado)) {
            throw new RuntimeException("El servidor SMTP respondió de forma inesperada: {$respuesta}");
        }

        return $respuesta;
    }
}
