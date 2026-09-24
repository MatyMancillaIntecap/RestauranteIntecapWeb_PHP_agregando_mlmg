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

    // Crea una solicitud de restablecimiento de contraseña para un usuario existente
    /** Crea una solicitud pendiente si el usuario existe y esta activo. */
    public function crearSolicitudRestablecimiento(string $identificador): array
    {
        $correo = strtolower(trim($identificador));

        if ($correo === '' || !str_contains($correo, '@')) {
            return [false, 'Ingresa el correo electrónico registrado para solicitar el restablecimiento.'];
        }

        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE LOWER(email) = :email LIMIT 1');
        $stmt->execute(['email' => $correo]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return [false, 'No se encontró un usuario con ese correo electrónico.'];
        }

        if (!(bool) $usuario['activo']) {
            return [false, 'Tu cuenta se encuentra desactivada. Contacta al Administrador.'];
        }

        $stmtPendiente = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM solicitudes_restablecimiento_password
             WHERE usuario_id = :usuario_id AND estado = 'Pendiente'"
        );
        $stmtPendiente->execute(['usuario_id' => $usuario['id']]);

        if ((int) $stmtPendiente->fetch()['total'] > 0) {
            return [true, 'Ya existe una solicitud pendiente para este usuario. El administrador la revisará pronto.'];
        }

        $insert = $this->db->prepare(
            "INSERT INTO solicitudes_restablecimiento_password (usuario_id, estado, fecha_solicitud)
             VALUES (:usuario_id, 'Pendiente', NOW())"
        );
        $insert->execute(['usuario_id' => $usuario['id']]);

        return [true, 'Tu solicitud fue enviada correctamente. Debes esperar a que un administrador gestione el cambio.'];
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
