<?php
/**
 * Servicio de reservas del empleado.
 *
 * Aplica reglas de negocio de menu, limites diarios, NIT, stock, transacciones
 * y consulta del historial personal.
 */
declare(strict_types=1);

class EmpleadoService
{
    private PDO $db;

    /** Abre la conexion PDO usada por todas las consultas del servicio. */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Platillos disponibles para una fecha, respetando stock, estado, hora de habilitación
    // y el límite diario del usuario autenticado.
    /** Devuelve menu disponible respetando stock, estado y limite diario. */
    public function obtenerMenuDisponiblePorFecha(string $fecha, ?int $usuarioId = null, ?int $limitePermitido = null): array
    {
        $sql = "SELECT * FROM menu_diario
                WHERE fecha = :fecha AND stock > 0 AND estado = 'Disponible' AND hora_habilitacion <= NOW()";
        $params = ['fecha' => $fecha];

        if ($usuarioId !== null && $limitePermitido !== null && $limitePermitido > 0) {
            $stmtUsado = $this->db->prepare(
                "SELECT COALESCE(SUM(cantidad), 0) AS total
                 FROM reservas
                 WHERE usuario_id = :usuario_id AND fecha_consumo = :fecha_consumo AND estado = 'Activa'"
            );
            $stmtUsado->execute(['usuario_id' => $usuarioId, 'fecha_consumo' => $fecha]);
            $usado = (int) $stmtUsado->fetch()['total'];

            if ($usado >= $limitePermitido) {
                return [];
            }
        }

        $sql .= ' ORDER BY nombre_plato ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Suma almuerzos activos del usuario para una fecha. */
    public function obtenerCantidadReservasActivasUsuarioFecha(int $usuarioId, string $fecha): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(cantidad), 0) AS total
             FROM reservas
             WHERE usuario_id = :usuario_id AND fecha_consumo = :fecha_consumo AND estado = 'Activa'"
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'fecha_consumo' => $fecha]);
        return (int) $stmt->fetch()['total'];
    }

    // Procesa la reserva validando límite diario, stock y NIT
    /** Valida y persiste atomicamente una o varias reservas del carrito. */
    public function procesarReserva(array $solicitud): array
    {
        $usuarioId = (int) ($solicitud['usuario_id'] ?? 0);
        $platillos = $solicitud['platillos'] ?? [];
        $nitFacturacion = trim((string) ($solicitud['nit_facturacion'] ?? '')) ?: 'C/F';

        if (empty($platillos)) {
            return [false, 'Debe seleccionar al menos un platillo para realizar la reserva.'];
        }

        $stmtUsuario = $this->db->prepare(
            'SELECT u.*, r.max_almuerzos FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE u.id = :id'
        );
        $stmtUsuario->execute(['id' => $usuarioId]);
        $usuario = $stmtUsuario->fetch();

        if (!$usuario) {
            return [false, 'El usuario no existe en la base de datos.'];
        }

        $limitePermitido = (int) $usuario['max_almuerzos'];

        $primerMenuId = (int) $platillos[0]['menu_id'];
        $stmtMenu = $this->db->prepare('SELECT * FROM menu_diario WHERE id = :id');
        $stmtMenu->execute(['id' => $primerMenuId]);
        $menuPrincipal = $stmtMenu->fetch();

        if (!$menuPrincipal) {
            return [false, 'El menú seleccionado no es válido.'];
        }

        $fechaConsumo = $menuPrincipal['fecha'];

        $stmtReservado = $this->db->prepare(
            "SELECT COALESCE(SUM(r.cantidad), 0) AS total
             FROM reservas r
             INNER JOIN menu_diario m ON m.id = r.menu_id
             WHERE r.usuario_id = :usuario_id AND r.fecha_consumo = :fecha_consumo
               AND r.estado = 'Activa' AND m.estado = 'Disponible'"
        );
        $stmtReservado->execute(['usuario_id' => $usuarioId, 'fecha_consumo' => $fechaConsumo]);
        $platillosYaReservadosHoy = (int) $stmtReservado->fetch()['total'];

        $totalSolicitadosAhora = array_sum(array_column($platillos, 'cantidad'));
        $sumaTotalIntento = $platillosYaReservadosHoy + $totalSolicitadosAhora;

        if ($limitePermitido > 0 && $platillosYaReservadosHoy >= $limitePermitido) {
            return [false, "Ya alcanzaste tu límite diario de {$limitePermitido} almuerzos para este día."];
        }

        if ($limitePermitido > 0 && $sumaTotalIntento > $limitePermitido) {
            return [false, "Límite excedido. Ya tienes {$platillosYaReservadosHoy} almuerzos reservados para este día y tu límite máximo es {$limitePermitido}."];
        }

        $nitLimpio = strtoupper($nitFacturacion);
        if (!preg_match('/^(?:C\/F|\d{1,13}(?:-\d)?)$/', $nitLimpio)) {
            return [false, "El NIT ingresado no es válido. Debe contener entre 1 y 13 dígitos, con guion y dígito verificador opcional, o 'C/F'."];
        }

        try {
            $this->db->beginTransaction();

            foreach ($platillos as $item) {
                $menuId = (int) $item['menu_id'];
                $cantidad = (int) $item['cantidad'];
                $formaPagoId = (int) $item['forma_pago_id'];
                $dondeConsume = (string) ($item['donde_consume'] ?? 'En restaurante');

                $stmtBloqueo = $this->db->prepare('SELECT * FROM menu_diario WHERE id = :id FOR UPDATE');
                $stmtBloqueo->execute(['id' => $menuId]);
                $menu = $stmtBloqueo->fetch();

                if (!$menu || (int) $menu['stock'] < $cantidad) {
                    $this->db->rollBack();
                    return [false, 'No hay suficiente stock para uno de los platillos seleccionados.'];
                }

                $updMenu = $this->db->prepare(
                    'UPDATE menu_diario SET stock = stock - :cantidad, cantidad_solicitada = cantidad_solicitada + :cantidad2 WHERE id = :id'
                );
                $updMenu->execute(['cantidad' => $cantidad, 'cantidad2' => $cantidad, 'id' => $menuId]);

                $insert = $this->db->prepare(
                    "INSERT INTO reservas (usuario_id, menu_id, forma_pago_id, cantidad, donde_consume, fecha_reserva, fecha_consumo, estado, nit_facturacion)
                     VALUES (:usuario_id, :menu_id, :forma_pago_id, :cantidad, :donde_consume, NOW(), :fecha_consumo, 'Activa', :nit)"
                );
                $insert->execute([
                    'usuario_id' => $usuarioId,
                    'menu_id' => $menuId,
                    'forma_pago_id' => $formaPagoId,
                    'cantidad' => $cantidad,
                    'donde_consume' => $dondeConsume,
                    'fecha_consumo' => $menu['fecha'],
                    'nit' => $nitLimpio,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return [false, 'Ocurrió un error al procesar la reserva.'];
        }

        return [true, '¡Reserva realizada exitosamente!'];
    }

    // Historial de reservas del usuario filtrado por rango de fechas y estado
    /** Consulta historial personal aplicando fechas y estado opcional. */
    public function obtenerHistorialUsuarioFiltrado(int $usuarioId, ?string $fechaInicio, ?string $fechaFin, string $estado = 'Todos'): array
    {
        $sql = 'SELECT r.id AS reserva_id, r.fecha_consumo, m.nombre_plato, m.imagen_url, r.cantidad,
                       m.precio AS precio_unitario, fp.nombre AS forma_pago, r.donde_consume,
                       r.nit_facturacion, r.estado, r.fecha_reserva
                FROM reservas r
                INNER JOIN menu_diario m ON m.id = r.menu_id
                INNER JOIN formas_pago fp ON fp.id = r.forma_pago_id
                WHERE r.usuario_id = :usuario_id';
        $params = ['usuario_id' => $usuarioId];

        if ($fechaInicio) {
            $sql .= ' AND r.fecha_consumo >= :fecha_inicio';
            $params['fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin) {
            $sql .= ' AND r.fecha_consumo <= :fecha_fin';
            $params['fecha_fin'] = $fechaFin;
        }
        if ($estado !== 'Todos' && $estado !== '') {
            $sql .= ' AND r.estado = :estado';
            $params['estado'] = $estado;
        }

        $sql .= ' ORDER BY r.fecha_consumo DESC, m.nombre_plato ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Genera el historial personal en formato CSV
    /** Genera el historial personal en CSV para compatibilidad. */
    public function generarCsvHistorial(int $usuarioId, ?string $fechaInicio, ?string $fechaFin): string
    {
        $historial = $this->obtenerHistorialUsuarioFiltrado($usuarioId, $fechaInicio, $fechaFin, 'Todos');

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['#Reserva', 'Fecha Consumo', 'Platillo', 'Cantidad', 'Precio Unitario', 'Total', 'Forma Pago', 'NIT']);

        foreach ($historial as $item) {
            fputcsv($handle, [
                $item['reserva_id'], $item['fecha_consumo'], $item['nombre_plato'], $item['cantidad'],
                $item['precio_unitario'], $item['cantidad'] * $item['precio_unitario'], $item['forma_pago'], $item['nit_facturacion'],
            ]);
        }

        rewind($handle);
        $contenido = stream_get_contents($handle);
        fclose($handle);

        return $contenido;
    }

    /** Devuelve el NIT guardado en el perfil o C/F como valor por defecto. */
    public function obtenerNitUsuario(int $usuarioId): string
    {
        $stmt = $this->db->prepare('SELECT nit_facturacion FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $usuarioId]);
        $row = $stmt->fetch();

        if ($row && !empty(trim((string) $row['nit_facturacion']))) {
            return $row['nit_facturacion'];
        }

        return 'C/F';
    }

    /** Devuelve el limite de almuerzos definido en el rol del usuario. */
    public function obtenerLimiteAlmuerzosUsuario(int $usuarioId): int
    {
        $stmt = $this->db->prepare(
            'SELECT r.max_almuerzos FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE u.id = :id'
        );
        $stmt->execute(['id' => $usuarioId]);
        $row = $stmt->fetch();

        return $row ? (int) $row['max_almuerzos'] : 2;
    }

    /** Normaliza fechas vacias y devuelve un rango consultable. */
    private function resolverRangoFechas(?string $fechaInicio, ?string $fechaFin): array
    {
        $inicio = $fechaInicio ?: ($fechaFin ?: date('Y-m-d'));
        $fin = $fechaFin ?: ($fechaInicio ?: date('Y-m-d'));

        if (strtotime($inicio) > strtotime($fin)) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        return [$inicio, $fin];
    }

    /** Cuenta platillos de dieta solicitados en el rango. */
    public function obtenerPlatillosDietaSolicitadosHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(r.cantidad), 0) AS total
             FROM reservas r INNER JOIN menu_diario m ON m.id = r.menu_id
             WHERE r.fecha_consumo BETWEEN :inicio AND :fin AND r.estado = 'Activa' AND m.es_dieta = 1"
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cuenta platillos de dieta publicados en el rango. */
    public function obtenerPlatillosDietaInicialesHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(stock + cantidad_solicitada), 0) AS total
             FROM menu_diario WHERE fecha BETWEEN :inicio AND :fin AND es_dieta = 1'
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cuenta platillos normales solicitados en el rango. */
    public function obtenerPlatillosNormalesSolicitadosHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(r.cantidad), 0) AS total
             FROM reservas r INNER JOIN menu_diario m ON m.id = r.menu_id
             WHERE r.fecha_consumo BETWEEN :inicio AND :fin AND m.es_dieta = 0"
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cuenta platillos normales publicados en el rango. */
    public function obtenerPlatillosNormalesInicialesHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(stock + cantidad_solicitada), 0) AS total
             FROM menu_diario WHERE fecha BETWEEN :inicio AND :fin AND es_dieta = 0'
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Suma ventas de reservas activas en el rango. */
    public function obtenerVentasTotalesHoy(?string $fechaInicio = null, ?string $fechaFin = null): float
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(r.cantidad * m.precio), 0) AS total
             FROM reservas r INNER JOIN menu_diario m ON m.id = r.menu_id
             WHERE r.fecha_consumo BETWEEN :inicio AND :fin AND r.estado = 'Activa'"
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (float) $stmt->fetch()['total'];
    }

    /** Cuenta reservas activas en el rango. */
    public function obtenerTotalReservasHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM reservas WHERE fecha_consumo BETWEEN :inicio AND :fin AND estado = 'Activa'"
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cuenta usuarios distintos con reservas activas en el rango. */
    public function obtenerUsuariosConReservasHoy(?string $fechaInicio = null, ?string $fechaFin = null): int
    {
        [$inicio, $fin] = $this->resolverRangoFechas($fechaInicio, $fechaFin);
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT usuario_id) AS total FROM reservas WHERE fecha_consumo BETWEEN :inicio AND :fin AND estado = 'Activa'"
        );
        $stmt->execute(['inicio' => $inicio, 'fin' => $fin]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cuenta todas las cuentas registradas, activas o inactivas. */
    public function obtenerTotalUsuariosRegistrados(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) AS total FROM usuarios')->fetch()['total'];
    }
}
