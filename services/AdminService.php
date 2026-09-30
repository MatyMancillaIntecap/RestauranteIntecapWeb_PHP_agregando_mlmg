<?php
/**
 * Servicio administrativo.
 *
 * Gestiona usuarios, roles, solicitudes de restablecimiento, detalle historico
 * y consultas usadas por los reportes del panel de administracion.
 */
declare(strict_types=1);

class AdminService
{
    private PDO $db;
    private CorreoService $correoService;

    /** Inicializa PDO y el servicio de correo usado en operaciones administrativas. */
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->correoService = new CorreoService();
    }

    // Obtiene un usuario específico por su correo, incluyendo el nombre del rol
    /** Busca un usuario por correo e incluye datos de su rol. */
    public function obtenerUsuarioPorCorreo(string $correo): ?array
    {
        $correo = strtolower(trim($correo));
        if ($correo === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT u.id, u.nombre, u.email, u.telefono, u.rol_id, r.nombre AS nombre_rol, u.activo,
                    u.nit_facturacion, r.max_almuerzos, u.fecha_creacion
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE LOWER(u.email) = :correo'
        );
        $stmt->execute(['correo' => $correo]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Obtiene todos los usuarios agrupados por rol (Administrador, Cocina, Empleado) y ordenados alfabéticamente
    /** Devuelve todos los usuarios agrupados por rol y ordenados por nombre. */
    public function obtenerTodosLosUsuarios(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id, u.nombre, u.email, u.telefono, u.rol_id, r.nombre AS nombre_rol, u.activo,
                    u.nit_facturacion, r.max_almuerzos, u.fecha_creacion
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             ORDER BY FIELD(r.nombre, 'Administrador', 'Cocina', 'Empleado'), u.nombre ASC"
        );

        return $stmt->fetchAll();
    }

    // Obtiene un usuario por id para llenar el formulario de edición
    /** Obtiene la informacion minima necesaria para editar un usuario. */
    public function obtenerUsuarioPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nombre, u.email, u.telefono, u.rol_id, u.activo, u.nit_facturacion, r.max_almuerzos
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Crea o actualiza un usuario según si trae id (0 = nuevo)
    /** Crea o actualiza un usuario y sincroniza el limite de su rol. */
    public function guardarUsuario(array $dto): array
    {
        $id = (int) ($dto['id'] ?? 0);
        $nombre = trim((string) ($dto['nombre'] ?? ''));
        $email = trim((string) ($dto['email'] ?? ''));
        $telefono = trim((string) ($dto['telefono'] ?? '')) ?: null;
        $rolId = (int) ($dto['rol_id'] ?? 0);
        $activo = (bool) ($dto['activo'] ?? true);
        $nit = trim((string) ($dto['nit_facturacion'] ?? '')) ?: 'C/F';
        $maxAlmuerzos = (int) ($dto['max_almuerzos'] ?? 2);
        $password = trim((string) ($dto['password'] ?? ''));

        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ((int) $stmt->fetch()['total'] > 0) {
            return [false, 'El correo electrónico ya está registrado por otro usuario.'];
        }

        $contrasenaInicial = '12345678';

        if ($id === 0) {
            $hash = password_hash($contrasenaInicial, PASSWORD_BCRYPT);

            $insert = $this->db->prepare(
                'INSERT INTO usuarios (nombre, email, telefono, password, rol_id, activo, nit_facturacion, fecha_creacion)
                 VALUES (:nombre, :email, :telefono, :password, :rol_id, :activo, :nit, NOW())'
            );
            $insert->execute([
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'password' => $hash,
                'rol_id' => $rolId,
                'activo' => $activo ? 1 : 0,
                'nit' => $nit,
            ]);

            $this->actualizarLimiteRol($rolId, $maxAlmuerzos);

            return [true, 'CONTRASENA_INICIAL:' . $contrasenaInicial . '|Usuario y límites guardados correctamente.'];
        }

        $stmtExiste = $this->db->prepare('SELECT id, nombre, email FROM usuarios WHERE id = :id');
        $stmtExiste->execute(['id' => $id]);
        $usuarioExistente = $stmtExiste->fetch();
        if (!$usuarioExistente) {
            return [false, 'El usuario no existe.'];
        }

        $sql = 'UPDATE usuarios SET nombre = :nombre, email = :email, telefono = :telefono, rol_id = :rol_id, activo = :activo, nit_facturacion = :nit';
        $params = [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'rol_id' => $rolId,
            'activo' => $activo ? 1 : 0,
            'nit' => $nit,
            'id' => $id,
        ];

        $cambioPassword = ($password !== '');
        if ($cambioPassword) {
            $sql .= ', password = :password';
            $params['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= ' WHERE id = :id';

        try {
            $update = $this->db->prepare($sql);
            $update->execute($params);
            $this->actualizarLimiteRol($rolId, $maxAlmuerzos);
        } catch (Throwable $e) {
            return [false, 'No se pudo actualizar la información del usuario en la base de datos.'];
        }

        if ($cambioPassword) {
            // El flujo estricto: CAMBIO DE CONTRASEÑA → VALIDACIÓN → ACTUALIZACIÓN EXITOSA EN BD → ENVÍO DEL CORREO → CONFIRMACIÓN
            [$correoEnviado, $correoMensaje] = $this->correoService->enviarCorreoNotificacionCambio(
                ['nombre' => $nombre, 'email' => $email],
                $password
            );

            if (!$correoEnviado) {
                // Registrar el error sin exponer la contraseña en logs
                error_log("Error al enviar notificación de contraseña actualizada al usuario {$email}: {$correoMensaje}");
                // No revertir el cambio de contraseña; informar al administrador que hubo un problema con el correo
                return [true, "WARN:Contraseña actualizada correctamente, pero ocurrió un problema al enviar la notificación por correo: {$correoMensaje}"];
            }

            return [true, "OK:Contraseña actualizada correctamente. Se ha enviado una notificación por correo a {$email}."];
        }

        return [true, 'Usuario y límites guardados correctamente.'];
    }

    /** Persiste el limite diario configurado para un rol. */
    private function actualizarLimiteRol(int $rolId, int $maxAlmuerzos): void
    {
        $stmt = $this->db->prepare('UPDATE roles SET max_almuerzos = :max WHERE id = :id');
        $stmt->execute(['max' => $maxAlmuerzos, 'id' => $rolId]);
    }

    // Activa o desactiva un usuario
    /** Activa o desactiva una cuenta y devuelve si hubo cambios. */
    public function cambiarEstadoUsuario(int $id, bool $activo): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        $stmt->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un usuario y sus registros dependientes dentro de una transaccion.
     *
     * Las reservas activas devuelven previamente su cantidad al menu para no
     * alterar el stock disponible. La cuenta del administrador actual no puede
     * eliminarse desde este flujo.
     *
     * @return array{0: bool, 1: string} Resultado y mensaje para la interfaz.
     */
    public function eliminarUsuario(int $id, int $administradorId): array
    {
        if ($id <= 0) {
            return [false, 'El usuario seleccionado no es válido.'];
        }
        if ($id === $administradorId) {
            return [false, 'No puedes eliminar la cuenta con la que estás conectado.'];
        }

        $existe = $this->db->prepare('SELECT id FROM usuarios WHERE id = :id');
        $existe->execute(['id' => $id]);
        if (!$existe->fetch()) {
            return [false, 'El usuario no existe.'];
        }

        $this->db->beginTransaction();
        try {
            $reservas = $this->db->prepare(
                'SELECT menu_id, cantidad, estado
                 FROM reservas
                 WHERE usuario_id = :usuario_id
                 FOR UPDATE'
            );
            $reservas->execute(['usuario_id' => $id]);

            foreach ($reservas->fetchAll() as $reserva) {
                if ($reserva['estado'] !== 'Activa') {
                    continue;
                }

                $restaurarStock = $this->db->prepare(
                    'UPDATE menu_diario
                     SET stock = stock + :cantidad,
                         cantidad_solicitada = GREATEST(0, cantidad_solicitada - :cantidad_solicitada)
                     WHERE id = :menu_id'
                );
                $restaurarStock->execute([
                    'cantidad' => (int) $reserva['cantidad'],
                    'cantidad_solicitada' => (int) $reserva['cantidad'],
                    'menu_id' => (int) $reserva['menu_id'],
                ]);
            }

            // Limpieza de relaciones del usuario (historial y reservas)
            $tablas = [
                'historial_login' => 'usuario_id',
                'reservas' => 'usuario_id',
            ];
            foreach ($tablas as $tabla => $columna) {
                $eliminar = $this->db->prepare("DELETE FROM {$tabla} WHERE {$columna} = :usuario_id");
                $eliminar->execute(['usuario_id' => $id]);
            }

            $eliminarUsuario = $this->db->prepare('DELETE FROM usuarios WHERE id = :id');
            $eliminarUsuario->execute(['id' => $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [false, 'No se pudo eliminar el usuario. La información no fue modificada.'];
        }

        return [true, 'Usuario eliminado correctamente.'];
    }

    /** Devuelve los roles disponibles para formularios administrativos. */
    public function obtenerRoles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    // Nota: Los métodos obtenerCantidadSolicitudesRestablecimientoPendientes, obtenerSolicitudesRestablecimiento
    // y atenderSolicitudRestablecimiento fueron eliminados ya que las contraseñas ahora se restablecen de forma 100% automática.


    // Detalle completo de un usuario: datos + historial de reservas + totales acumulados
    /** Combina datos de usuario, reservas y totales acumulados para su ficha. */
    public function obtenerDetalleCompletoUsuario(int $usuarioId): ?array
    {
        $usuarios = $this->obtenerTodosLosUsuarios();
        $info = null;
        foreach ($usuarios as $u) {
            if ((int) $u['id'] === $usuarioId) {
                $info = $u;
                break;
            }
        }

        if ($info === null) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT r.id AS reserva_id, r.fecha_consumo, m.nombre_plato, m.imagen_url, r.cantidad,
                    m.precio AS precio_unitario, fp.nombre AS forma_pago, r.donde_consume,
                    r.nit_facturacion, r.estado, r.fecha_reserva
             FROM reservas r
             INNER JOIN menu_diario m ON m.id = r.menu_id
             INNER JOIN formas_pago fp ON fp.id = r.forma_pago_id
             WHERE r.usuario_id = :usuario_id
             ORDER BY r.fecha_reserva DESC'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        $reservas = $stmt->fetchAll();

        $totalGastado = 0.0;
        $totalPlatillos = 0;
        $totalCanceladas = 0;

        foreach ($reservas as $r) {
            $totalFila = (float) $r['cantidad'] * (float) $r['precio_unitario'];
            if ($r['estado'] === 'Activa') {
                $totalGastado += $totalFila;
                $totalPlatillos += (int) $r['cantidad'];
            } elseif ($r['estado'] === 'Cancelada') {
                $totalCanceladas++;
            }
        }

        return [
            'info_usuario' => $info,
            'historial_reservas' => $reservas,
            'total_gastado_acumulado' => $totalGastado,
            'total_platillos_reservados' => $totalPlatillos,
            'total_reservas_canceladas' => $totalCanceladas,
        ];
    }

    // Reporte global de reservas para exportaciones Excel y PDF.
    /** Obtiene filas del reporte global para exportaciones y filtros de fecha. */
    public function obtenerReporteGlobal(
        ?string $fechaInicio,
        ?string $fechaFin,
        ?int $usuarioId = null,
        ?int $menuId = null
    ): array {
        $sql = 'SELECT r.id, r.fecha_reserva, r.fecha_consumo, u.nombre AS usuario, u.email AS usuario_email, u.telefono AS usuario_telefono, m.nombre_plato,
                       r.cantidad, m.precio, fp.nombre AS forma_pago, r.nit_facturacion, r.estado
                FROM reservas r
                INNER JOIN usuarios u ON u.id = r.usuario_id
                INNER JOIN menu_diario m ON m.id = r.menu_id
                INNER JOIN formas_pago fp ON fp.id = r.forma_pago_id
                WHERE 1 = 1';
        $params = [];

        if ($fechaInicio) {
            $sql .= ' AND r.fecha_consumo >= :fecha_inicio';
            $params['fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin) {
            $sql .= ' AND r.fecha_consumo <= :fecha_fin';
            $params['fecha_fin'] = $fechaFin;
        }
        if ($usuarioId) {
            $sql .= ' AND r.usuario_id = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        }
        if ($menuId) {
            $sql .= ' AND r.menu_id = :menu_id';
            $params['menu_id'] = $menuId;
        }

        $sql .= ' ORDER BY u.nombre ASC, r.fecha_reserva DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Reporte global de reservas en formato CSV (con filtros avanzados)
    /** Genera el reporte global en CSV para compatibilidad con rutas antiguas. */
    public function generarCsvReporteGlobal(
        ?string $fechaInicio,
        ?string $fechaFin,
        ?int $usuarioId,
        ?string $estado,
        ?int $menuId = null
    ): string {
        $sql = 'SELECT r.id, r.fecha_reserva, r.fecha_consumo, u.nombre AS usuario, u.email AS usuario_email, u.telefono AS usuario_telefono, m.nombre_plato,
                       r.cantidad, m.precio, fp.nombre AS forma_pago, r.nit_facturacion, r.estado
                FROM reservas r
                INNER JOIN usuarios u ON u.id = r.usuario_id
                INNER JOIN menu_diario m ON m.id = r.menu_id
                INNER JOIN formas_pago fp ON fp.id = r.forma_pago_id
                WHERE 1 = 1';
        $params = [];

        if ($fechaInicio) {
            $sql .= ' AND r.fecha_consumo >= :fecha_inicio';
            $params['fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin) {
            $sql .= ' AND r.fecha_consumo <= :fecha_fin';
            $params['fecha_fin'] = $fechaFin;
        }
        if ($usuarioId) {
            $sql .= ' AND r.usuario_id = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        }
        if ($menuId) {
            $sql .= ' AND r.menu_id = :menu_id';
            $params['menu_id'] = $menuId;
        }
        if ($estado && $estado !== 'Todos') {
            $sql .= ' AND r.estado = :estado';
            $params['estado'] = $estado;
        }

        $sql .= ' ORDER BY u.nombre ASC, r.fecha_reserva DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll();

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['#Reserva', 'Fecha Reserva', 'Fecha Consumo', 'Usuario', 'Correo', 'Teléfono', 'Platillo', 'Cantidad', 'Precio Unit.', 'Total', 'Forma Pago', 'NIT', 'Estado']);

        foreach ($filas as $f) {
            fputcsv($handle, [
                $f['id'], $f['fecha_reserva'], $f['fecha_consumo'], $f['usuario'], $f['usuario_email'], ($f['usuario_telefono'] ?? 'Sin registrar'), $f['nombre_plato'],
                $f['cantidad'], $f['precio'], $f['cantidad'] * $f['precio'], $f['forma_pago'], $f['nit_facturacion'], $f['estado'],
            ]);
        }

        rewind($handle);
        $contenido = stream_get_contents($handle);
        fclose($handle);

        return $contenido;
    }

    // Padrón de usuarios en CSV
    /** Genera el padron completo de usuarios en CSV. */
    public function generarCsvUsuarios(): string
    {
        $usuarios = $this->obtenerTodosLosUsuarios();

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['#ID', 'Nombre', 'Correo', 'Teléfono', 'Rol', 'Límite Almuerzos', 'NIT', 'Estado', 'Fecha Creación']);

        foreach ($usuarios as $u) {
            fputcsv($handle, [
                $u['id'], $u['nombre'], $u['email'], ($u['telefono'] ?? 'Sin registrar'), $u['nombre_rol'],
                $u['max_almuerzos'] == 0 ? 'Ilimitado' : $u['max_almuerzos'],
                $u['nit_facturacion'], $u['activo'] ? 'Activo' : 'Inactivo', $u['fecha_creacion'],
            ]);
        }

        rewind($handle);
        $contenido = stream_get_contents($handle);
        fclose($handle);

        return $contenido;
    }
}
