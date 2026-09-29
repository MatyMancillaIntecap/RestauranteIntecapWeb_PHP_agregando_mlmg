<?php
/**
 * Servicio de autenticacion y credenciales.
 *
 * Consulta usuarios, migra claves heredadas a bcrypt, registra accesos y crea
 * solicitudes de restablecimiento sin mezclar esa logica con las vistas.
 */
declare(strict_types=1);

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Valida correo, cuenta activa y contraseña contra MySQL
    /** Valida correo, estado, contrasena y registra el inicio de sesion. */
    public function validarCredenciales(string $email, string $password): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE LOWER(u.email) = LOWER(:email)
             LIMIT 1'
        );
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();

        if (!$row) {
            return [false, 'El correo electrónico ingresado no se encuentra registrado.', null];
        }

        if (!(bool) $row['activo']) {
            return [false, 'Tu cuenta se encuentra desactivada. Contacta al Administrador.', null];
        }

        $passwordValida = password_verify($password, $row['password']);

        // Compatibilidad temporal con contraseñas heredadas en texto plano
        if (!$passwordValida) {
            if ($row['password'] !== $password) {
                return [false, 'La contraseña ingresada es incorrecta.', null];
            }

            $nuevoHash = password_hash($password, PASSWORD_BCRYPT);
            $this->actualizarPassword((int) $row['id'], $nuevoHash);
        } elseif (password_needs_rehash($row['password'], PASSWORD_BCRYPT)) {
            $nuevoHash = password_hash($password, PASSWORD_BCRYPT);
            $this->actualizarPassword((int) $row['id'], $nuevoHash);
        }

        $this->registrarAcceso((int) $row['id']);

        return [true, '¡Inicio de sesión exitoso!', Usuario::fromRow($row)];
    }

    // Restablece automáticamente la contraseña del usuario a "87654321", aplica hash seguro y envía correo
    /**
     * Restablece automáticamente la contraseña a 87654321 y envía notificación por correo.
     * Sin intervención del administrador. Maneja reversión segura si el correo no puede enviarse.
     *
     * @param string $identificador Correo electrónico del usuario.
     * @return array{0: bool, 1: string} [éxito, mensaje de resultado]
     */
    public function restablecerPasswordAutomatico(string $identificador): array
    {
        $correo = strtolower(trim($identificador));

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Ingresa un correo electrónico registrado válido para restablecer la contraseña.'];
        }

        // Buscar al usuario por correo
        $stmt = $this->db->prepare('SELECT id, nombre, email, password, activo FROM usuarios WHERE LOWER(email) = :email LIMIT 1');
        $stmt->execute(['email' => $correo]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return [false, 'No se encontró un usuario con ese correo electrónico.'];
        }

        if (!(bool) $usuario['activo']) {
            return [false, 'Tu cuenta se encuentra desactivada. Contacta al Administrador.'];
        }

        // Nueva contraseña requerida exactamente: 87654321 con hash seguro bcrypt
        $nuevaPasswordPlano = '87654321';
        $nuevoHash = password_hash($nuevaPasswordPlano, PASSWORD_BCRYPT);
        $hashAnterior = (string) $usuario['password'];
        $usuarioId = (int) $usuario['id'];

        // Actualizar la contraseña en la base de datos
        $this->actualizarPassword($usuarioId, $nuevoHash);

        // Envío automático de correo con la contraseña restablecida
        require_once ROOT_PATH . '/services/CorreoService.php';
        $correoService = new CorreoService();
        [$correoEnviado, $correoMensaje] = $correoService->enviarRestablecimientoPassword(
            ['nombre' => $usuario['nombre'], 'email' => $usuario['email']],
            $nuevaPasswordPlano
        );

        // Si el correo no se pudo enviar, revertir contraseña al hash anterior para no dejar inconsistencia
        if (!$correoEnviado) {
            $this->actualizarPassword($usuarioId, $hashAnterior);
            error_log("Error al enviar correo de restablecimiento automático a {$usuario['email']}: {$correoMensaje}");
            return [false, 'No se pudo enviar el correo electrónico con su nueva contraseña. Su contraseña no fue modificada por seguridad. Intente nuevamente más tarde.'];
        }

        return [true, "Tu contraseña ha sido restablecida exitosamente a la temporal configurada. Hemos enviado los detalles a tu correo ({$usuario['email']})."];
    }

    // Compatibilidad: redirige llamadas anteriores al nuevo flujo automático sin administrador
    public function crearSolicitudRestablecimiento(string $identificador): array
    {
        return $this->restablecerPasswordAutomatico($identificador);
    }

    // Inserta el registro de auditoría en la tabla historial_login
    /** Inserta una entrada de auditoria para un inicio de sesion exitoso. */
    public function registrarAcceso(int $usuarioId): void
    {
        $stmt = $this->db->prepare('INSERT INTO historial_login (usuario_id, fecha_login) VALUES (:usuario_id, NOW())');
        $stmt->execute(['usuario_id' => $usuarioId]);
    }

    // Permite que un usuario autenticado cambie su propia contraseña
    /** Valida la clave actual y persiste una nueva clave bcrypt. */
    public function cambiarContrasena(int $usuarioId, string $actual, string $nueva, string $confirmar): array
    {
        if (trim($actual) === '') {
            return [false, 'La contraseña actual es obligatoria.'];
        }

        if (trim($nueva) === '') {
            return [false, 'La nueva contraseña es obligatoria.'];
        }

        if (trim($confirmar) === '') {
            return [false, 'Debe confirmar la nueva contraseña.'];
        }

        if ($nueva !== $confirmar) {
            return [false, 'Las contraseñas nuevas no coinciden.'];
        }

        if (strlen($nueva) < 8) {
            return [false, 'La nueva contraseña debe tener al menos 8 caracteres.'];
        }

        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $usuarioId]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return [false, 'Usuario no encontrado.'];
        }

        $passwordValida = password_verify($actual, $usuario['password']);
        if (!$passwordValida && $usuario['password'] !== $actual) {
            return [false, 'La contraseña actual es incorrecta.'];
        }

        $this->actualizarPassword($usuarioId, password_hash($nueva, PASSWORD_BCRYPT));

        return [true, 'Tu contraseña ha sido cambiada correctamente.'];
    }

    /** Actualiza el hash de contrasena de un usuario. */
    private function actualizarPassword(int $usuarioId, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hash, 'id' => $usuarioId]);
    }
}
