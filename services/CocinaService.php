<?php
/**
 * Servicio de cocina y menu.
 *
 * Centraliza operaciones CRUD de platillos, estados, stock, consolidado y
 * consultas del detalle diario de reservas.
 */
declare(strict_types=1);

class CocinaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Devuelve los platillos publicados para una fecha. */
    public function obtenerMenusPorFecha(string $fecha): array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_diario WHERE fecha = :fecha');
        $stmt->execute(['fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    /** Busca un platillo por su identificador. */
    public function obtenerMenuPorId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_diario WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Crea un nuevo platillo o actualiza uno existente
    /** Inserta un platillo nuevo o actualiza uno existente. */
    public function actualizarMenu(array $menu): bool
    {
        $id = (int) ($menu['id'] ?? 0);

        if ($id === 0) {
            $insert = $this->db->prepare(
                'INSERT INTO menu_diario (nombre_plato, descripcion, precio, stock, cantidad_solicitada, imagen_url, fecha, hora_habilitacion, es_dieta, estado)
                 VALUES (:nombre_plato, :descripcion, :precio, :stock, 0, :imagen_url, :fecha, :hora_habilitacion, :es_dieta, :estado)'
            );
            $insert->execute([
                'nombre_plato' => $menu['nombre_plato'],
                'descripcion' => $menu['descripcion'] ?? null,
                'precio' => $menu['precio'],
                'stock' => $menu['stock'],
                'imagen_url' => $menu['imagen_url'] ?? null,
                'fecha' => $menu['fecha'],
                'hora_habilitacion' => $menu['hora_habilitacion'],
                'es_dieta' => !empty($menu['es_dieta']) ? 1 : 0,
                'estado' => $menu['estado'] ?? 'Disponible',
            ]);
            return true;
        }

        $existente = $this->obtenerMenuPorId($id);
        if (!$existente) {
            return false;
        }

        $sql = 'UPDATE menu_diario SET nombre_plato = :nombre_plato, descripcion = :descripcion, precio = :precio,
                stock = :stock, es_dieta = :es_dieta, estado = :estado';
        $params = [
            'nombre_plato' => $menu['nombre_plato'],
            'descripcion' => $menu['descripcion'] ?? null,
            'precio' => $menu['precio'],
            'stock' => $menu['stock'],
            'es_dieta' => !empty($menu['es_dieta']) ? 1 : 0,
            'estado' => $menu['estado'] ?? $existente['estado'],
            'id' => $id,
        ];

        if (!empty($menu['imagen_url'])) {
            $sql .= ', imagen_url = :imagen_url';
            $params['imagen_url'] = $menu['imagen_url'];
        }

        $sql .= ' WHERE id = :id';

        $update = $this->db->prepare($sql);
        $update->execute($params);
        return true;
    }

    /** Actualiza el estado de un platillo y reporta si cambio alguna fila. */
    public function cambiarEstadoMenu(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->prepare('UPDATE menu_diario SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $nuevoEstado, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Elimina un platillo únicamente si no tiene reservas asociadas
    /** Elimina solo menus sin reservas para preservar integridad historica. */
    public function eliminarMenuSinReservas(int $id): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM reservas WHERE menu_id = :id');
        $stmt->execute(['id' => $id]);

        if ((int) $stmt->fetch()['total'] > 0) {
            return [false, 'No se puede eliminar el platillo porque ya cuenta con reservas registradas. Cambie su estado a Inactivo.'];
        }

        $existe = $this->obtenerMenuPorId($id);
        if (!$existe) {
            return [false, 'El platillo especificado no existe.'];
        }

        $delete = $this->db->prepare('DELETE FROM menu_diario WHERE id = :id');
        $delete->execute(['id' => $id]);

        return [true, 'Platillo eliminado exitosamente.'];
    }

    // Detalle de reservas activas de un día, ordenado alfabéticamente por empleado
    /** Devuelve las reservas activas de una fecha ordenadas por empleado. */
    public function obtenerReservasDetalladasPorFecha(string $fecha): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id AS reserva_id, u.nombre AS nombre_empleado, u.email AS email_empleado,
                    m.nombre_plato, m.precio AS precio_unitario, m.es_dieta, r.cantidad, r.donde_consume, fp.nombre AS forma_pago,
                    r.fecha_reserva, r.estado, m.estado AS estado_platillo
             FROM reservas r
             INNER JOIN usuarios u ON u.id = r.usuario_id
             INNER JOIN menu_diario m ON m.id = r.menu_id
             INNER JOIN formas_pago fp ON fp.id = r.forma_pago_id
             WHERE r.fecha_consumo = :fecha AND r.estado = 'Activa'
             ORDER BY u.nombre ASC"
        );
        $stmt->execute(['fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    // Consolidado: todos los platillos del día (con o sin reservas), más sus totales
    /** Agrupa cantidades y recaudacion por platillo, incluso sin reservas. */
    public function obtenerConsolidadoPorFecha(string $fecha): array
    {
        // LEFT JOIN para incluir platillos sin reservas
        $stmt = $this->db->prepare(
            "SELECT m.id AS menu_id, m.nombre_plato, m.precio, m.es_dieta,
                    m.estado AS estado_platillo, m.stock, m.cantidad_solicitada,
                    m.imagen_url,
                    COALESCE(SUM(r.cantidad), 0) AS total_solicitado,
                    COALESCE(SUM(r.cantidad * m.precio), 0) AS total_recaudado
             FROM menu_diario m
             LEFT JOIN reservas r
                 ON r.menu_id = m.id
                 AND r.fecha_consumo = :fecha
                 AND r.estado = 'Activa'
             WHERE m.fecha = :fecha2
             GROUP BY m.id, m.nombre_plato, m.precio, m.es_dieta, m.estado, m.stock, m.cantidad_solicitada, m.imagen_url
             ORDER BY total_solicitado DESC, m.nombre_plato ASC"
        );
        $stmt->execute(['fecha' => $fecha, 'fecha2' => $fecha]);
        return $stmt->fetchAll();
    }

    // Genera el reporte de reservas del día en formato CSV
    /** Genera el reporte CSV legado de reservas de cocina. */
    public function generarCsvReservas(string $fecha): string
    {
        $detalles = $this->obtenerReservasDetalladasPorFecha($fecha);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['#', 'Empleado', 'Plato', 'Cantidad', 'Dónde consume', 'Forma de Pago', 'Hora Reserva', 'Estado Platillo']);

        foreach ($detalles as $item) {
            fputcsv($handle, [
                $item['reserva_id'], $item['nombre_empleado'], $item['nombre_plato'], $item['cantidad'],
                $item['donde_consume'], $item['forma_pago'], $item['fecha_reserva'], $item['estado_platillo'],
            ]);
        }

        rewind($handle);
        $contenido = stream_get_contents($handle);
        fclose($handle);

        return $contenido;
    }
}
