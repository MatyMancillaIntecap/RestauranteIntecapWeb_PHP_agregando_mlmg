<?php
declare(strict_types=1);
/**
 * Servicio de La Carta del restaurante.
 *
 * Gestiona el catálogo de productos organizado por las 4 categorías:
 * Entrada, Plato fuerte, Bebida y Postre. Centraliza CRUD, stock disponible,
 * control de fechas y horarios de habilitación, recuento consolidado y reservas.
 */

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/models/Entidades.php';

class CartaService
{
    private PDO $db;

    // Categorías oficiales de La Carta según requerimientos
    public const CATEGORIAS = [
        'Entrada',
        'Plato fuerte',
        'Bebida',
        'Postre',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Comprueba si un producto está habilitado para los usuarios en este instante:
     * 1. Debe estar activo (estado = 1).
     * 2. Debe tener stock disponible (stock - cantidad_solicitada > 0).
     * 3. Debe cumplir con el día de la semana o fecha específica configurada.
     * 4. Debe encontrarse dentro del rango de hora permitido (hora_inicio a hora_fin).
     *
     * @param array<string, mixed> $producto
     * @param DateTimeImmutable|null $ahora
     * @return bool
     */
    public function isHabilitado(array $producto, ?DateTimeImmutable $ahora = null): bool
    {
        // 1. Estado activo
        if (!(bool) ($producto['estado'] ?? 1)) {
            return false;
        }

        // 2. Stock disponible
        $stock = (int) ($producto['stock'] ?? 10);
        $solicitados = (int) ($producto['cantidad_solicitada'] ?? 0);
        if (($stock - $solicitados) <= 0) {
            return false;
        }

        if ($ahora === null) {
            $ahora = new DateTimeImmutable('now', new DateTimeZone('America/Guatemala'));
        }

        // 3. Verificación de fecha específica (si se configuró una)
        if (!empty($producto['fecha_habilitacion'])) {
            $fechaProducto = substr((string) $producto['fecha_habilitacion'], 0, 10);
            if ($ahora->format('Y-m-d') !== $fechaProducto) {
                return false;
            }
        }

        // 4. Verificación de día de la semana (ej. Martes)
        $diasConfigurados = trim((string) ($producto['dias_habilitados'] ?? 'Todos'));
        if ($diasConfigurados !== '' && strcasecmp($diasConfigurados, 'Todos') !== 0) {
            $diasEspanol = [
                1 => 'lunes',
                2 => 'martes',
                3 => 'miercoles',
                4 => 'jueves',
                5 => 'viernes',
                6 => 'sabado',
                7 => 'domingo',
            ];
            $numDia = (int) $ahora->format('N');
            $diaActual = $diasEspanol[$numDia] ?? '';

            // Limpiar acentos y comparar
            $diasConfiguradosLimpios = strtolower($this->quitarAcentos($diasConfigurados));
            if (!str_contains($diasConfiguradosLimpios, $diaActual)) {
                return false;
            }
        }

        // 5. Verificación de rango horario (ej. 08:00:00 a 10:00:00)
        $horaInicio = !empty($producto['hora_inicio']) ? (string) $producto['hora_inicio'] : '00:00:00';
        $horaFin = !empty($producto['hora_fin']) ? (string) $producto['hora_fin'] : '23:59:59';
        $horaActual = $ahora->format('H:i:s');

        if ($horaActual < $horaInicio || $horaActual > $horaFin) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene los productos agrupados por categoría.
     * Si $soloDisponiblesParaUsuario es true, filtra por horario y stock disponible.
     *
     * @param bool $soloDisponiblesParaUsuario
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function obtenerTodosAgrupadosPorCategoria(bool $soloDisponiblesParaUsuario = false): array
    {
        $agrupados = [
            'Entrada' => [],
            'Plato fuerte' => [],
            'Bebida' => [],
            'Postre' => [],
        ];

        $sql = 'SELECT * FROM carta_productos ORDER BY categoria ASC, nombre ASC';
        $stmt = $this->db->query($sql);
        $productos = $stmt->fetchAll();

        $ahora = new DateTimeImmutable('now', new DateTimeZone('America/Guatemala'));

        foreach ($productos as $producto) {
            $cat = trim((string) $producto['categoria']);
            $catNormalizada = $this->normalizarNombreCategoria($cat);

            // Calcular stock disponible actual
            $stock = (int) ($producto['stock'] ?? 10);
            $solicitados = (int) ($producto['cantidad_solicitada'] ?? 0);
            $producto['stock_disponible'] = max(0, $stock - $solicitados);
            $producto['habilitado_ahora'] = $this->isHabilitado($producto, $ahora);

            // Si es vista de cliente/usuario, únicamente mostrar productos con horario vigente y stock > 0
            if ($soloDisponiblesParaUsuario) {
                if (!$producto['habilitado_ahora']) {
                    continue;
                }
            }

            if (isset($agrupados[$catNormalizada])) {
                $agrupados[$catNormalizada][] = $producto;
            } else {
                $agrupados[$catNormalizada] = [$producto];
            }
        }

        return $agrupados;
    }

    /**
     * Busca un producto por su ID.
     */
    public function obtenerProductoPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM carta_productos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $stock = (int) ($row['stock'] ?? 10);
            $solicitados = (int) ($row['cantidad_solicitada'] ?? 0);
            $row['stock_disponible'] = max(0, $stock - $solicitados);
            $row['habilitado_ahora'] = $this->isHabilitado($row);
        }

        return $row ?: null;
    }

    /**
     * Guarda o actualiza un producto de La Carta con su stock y horarios configurables.
     *
     * @param array<string, mixed> $datos
     * @return array{0: bool, 1: string, 2: ?int}
     */
    public function guardarProducto(array $datos): array
    {
        $id = (int) ($datos['id'] ?? 0);
        $categoria = trim((string) ($datos['categoria'] ?? ''));
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $descripcion = !empty($datos['descripcion']) ? trim((string) $datos['descripcion']) : null;
        $precio = (float) ($datos['precio'] ?? 0);
        $stock = max(0, (int) ($datos['stock'] ?? 10));
        $estado = !empty($datos['estado']) ? 1 : 0;
        $imagen = !empty($datos['imagen']) ? trim((string) $datos['imagen']) : null;

        // Horarios y días configurables
        $diasHabilitados = trim((string) ($datos['dias_habilitados'] ?? 'Todos')) ?: 'Todos';
        $fechaHabilitacion = !empty($datos['fecha_habilitacion']) ? trim((string) $datos['fecha_habilitacion']) : null;
        $horaInicio = !empty($datos['hora_inicio']) ? trim((string) $datos['hora_inicio']) : '00:00:00';
        $horaFin = !empty($datos['hora_fin']) ? trim((string) $datos['hora_fin']) : '23:59:59';

        // Normalizar formato de hora a HH:MM:00
        if (strlen($horaInicio) === 5) {
            $horaInicio .= ':00';
        }
        if (strlen($horaFin) === 5) {
            $horaFin .= ':00';
        }

        $categoriaNormalizada = $this->normalizarNombreCategoria($categoria);
        if (!in_array($categoriaNormalizada, self::CATEGORIAS, true)) {
            return [false, 'La categoría seleccionada no es válida. Debe ser: Entrada, Plato fuerte, Bebida o Postre.', null];
        }

        if ($nombre === '') {
            return [false, 'El nombre del producto es obligatorio.', null];
        }

        if ($precio <= 0) {
            return [false, 'El precio del producto debe ser mayor a 0.', null];
        }

        if ($id === 0) {
            $stmt = $this->db->prepare(
                'INSERT INTO carta_productos (
                    categoria, nombre, descripcion, precio, stock, cantidad_solicitada,
                    imagen, dias_habilitados, fecha_habilitacion, hora_inicio, hora_fin, estado, creado_en
                 ) VALUES (
                    :categoria, :nombre, :descripcion, :precio, :stock, 0,
                    :imagen, :dias_habilitados, :fecha_habilitacion, :hora_inicio, :hora_fin, :estado, NOW()
                 )'
            );
            $stmt->execute([
                'categoria' => $categoriaNormalizada,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'stock' => $stock,
                'imagen' => $imagen,
                'dias_habilitados' => $diasHabilitados,
                'fecha_habilitacion' => $fechaHabilitacion,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'estado' => $estado,
            ]);

            return [true, 'Producto guardado exitosamente en La Carta.', (int) $this->db->lastInsertId()];
        }

        $sql = 'UPDATE carta_productos
                SET categoria = :categoria,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    stock = :stock,
                    dias_habilitados = :dias_habilitados,
                    fecha_habilitacion = :fecha_habilitacion,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    estado = :estado';
        $params = [
            'categoria' => $categoriaNormalizada,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'stock' => $stock,
            'dias_habilitados' => $diasHabilitados,
            'fecha_habilitacion' => $fechaHabilitacion,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado' => $estado,
            'id' => $id,
        ];

        if ($imagen !== null) {
            $sql .= ', imagen = :imagen';
            $params['imagen'] = $imagen;
        }

        $sql .= ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [true, 'Producto actualizado correctamente en La Carta.', $id];
    }

    /**
     * Cambia el estado activo/inactivo vía AJAX.
     */
    public function cambiarEstado(int $id, bool $activo): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE carta_productos SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $activo ? 1 : 0, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un producto de La Carta y su imagen asociada.
     */
    public function eliminarProducto(int $id): array
    {
        $producto = $this->obtenerProductoPorId($id);
        if (!$producto) {
            return [false, 'El producto no existe o ya fue eliminado.'];
        }

        if (!empty($producto['imagen'])) {
            $rutaArchivo = ROOT_PATH . '/public' . $producto['imagen'];
            if (file_exists($rutaArchivo) && is_file($rutaArchivo)) {
                @unlink($rutaArchivo);
            }
        }

        $stmt = $this->db->prepare('DELETE FROM carta_productos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return [true, 'Producto eliminado exitosamente de La Carta.'];
    }

    /**
     * Valida del lado del servidor las opciones seleccionadas (respuestas Sí/No)
     * y calcula el total de acuerdo con la regla:
     * Máximo 1 producto seleccionado por categoría.
     *
     * @param array<string, mixed> $selecciones
     * @return array{
     *     valido: bool,
     *     total: float,
     *     detalles: array<string, array<string, mixed>|null>,
     *     mensaje: string
     * }
     */
    public function validarYCalcularTotal(array $selecciones): array
    {
        $mapaCategorias = [
            'entrada' => 'Entrada',
            'plato_fuerte' => 'Plato fuerte',
            'platofuerte' => 'Plato fuerte',
            'bebida' => 'Bebida',
            'postre' => 'Postre',
        ];

        $detalles = [
            'Entrada' => null,
            'Plato fuerte' => null,
            'Bebida' => null,
            'Postre' => null,
        ];
        $total = 0.0;
        $ahora = new DateTimeImmutable('now', new DateTimeZone('America/Guatemala'));

        foreach ($selecciones as $clave => $idValor) {
            $claveLimpia = strtolower(str_replace([' ', '-', '_'], '', (string) $clave));
            $categoriaNombre = null;

            foreach ($mapaCategorias as $alias => $nombreOficial) {
                if (str_replace([' ', '-', '_'], '', $alias) === $claveLimpia) {
                    $categoriaNombre = $nombreOficial;
                    break;
                }
            }

            if ($categoriaNombre === null || $idValor === null || $idValor === '' || (int) $idValor === 0) {
                continue;
            }

            $productoId = (int) $idValor;
            $producto = $this->obtenerProductoPorId($productoId);

            if (!$producto) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto seleccionado para {$categoriaNombre} no existe.",
                ];
            }

            // Validar si está habilitado por horario y stock
            if (!$this->isHabilitado($producto, $ahora)) {
                $stockDisp = max(0, ((int)$producto['stock']) - ((int)$producto['cantidad_solicitada']));
                if ($stockDisp <= 0) {
                    return [
                        'valido' => false,
                        'total' => 0.0,
                        'detalles' => $detalles,
                        'mensaje' => "El producto '{$producto['nombre']}' se ha agotado (0 unidades disponibles).",
                    ];
                }

                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto '{$producto['nombre']}' está fuera de su horario de atención configurado.",
                ];
            }

            // Validar pertenencia a categoría
            $catProd = $this->normalizarNombreCategoria((string) $producto['categoria']);
            if ($catProd !== $categoriaNombre) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto '{$producto['nombre']}' no pertenece a la categoría {$categoriaNombre}.",
                ];
            }

            if ($detalles[$categoriaNombre] !== null) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "Solo puede seleccionar un producto para la categoría {$categoriaNombre}.",
                ];
            }

            $precioItem = (float) $producto['precio'];
            $detalles[$categoriaNombre] = [
                'id' => (int) $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $precioItem,
                'imagen' => $producto['imagen'],
                'stock_disponible' => max(0, (int)$producto['stock'] - (int)$producto['cantidad_solicitada']),
            ];
            $total += $precioItem;
        }

        return [
            'valido' => true,
            'total' => round($total, 2),
            'detalles' => $detalles,
            'mensaje' => 'Selección y total validados correctamente.',
        ];
    }

    /**
     * Procesa y registra una reserva real sobre La Carta:
     * decrementa el stock disponible y crea el registro en carta_reservas.
     *
     * @param array<string, mixed> $dto
     * @return array{0: bool, 1: string, 2: ?int} [éxito, mensaje, reserva_id]
     */
    public function procesarReserva(array $dto): array
    {
        $usuarioId = (int) ($dto['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return [false, 'Usuario no autenticado para realizar la reserva.', null];
        }

        $selecciones = [
            'entrada' => $dto['entrada_id'] ?? null,
            'plato_fuerte' => $dto['plato_fuerte_id'] ?? null,
            'bebida' => $dto['bebida_id'] ?? null,
            'postre' => $dto['postre_id'] ?? null,
        ];

        // Validar selecciones y calcular total oficial
        $val = $this->validarYCalcularTotal($selecciones);
        if (!$val['valido']) {
            return [false, $val['mensaje'], null];
        }

        // Al menos una opción debe haber sido seleccionada (Sí)
        $detalles = $val['detalles'];
        $haySeleccion = !empty($detalles['Entrada']) || !empty($detalles['Plato fuerte']) || !empty($detalles['Bebida']) || !empty($detalles['Postre']);
        if (!$haySeleccion) {
            return [false, 'Debe seleccionar al menos una opción (marcar Sí) para realizar la reserva.', null];
        }

        $total = (float) $val['total'];
        $dondeConsume = trim((string) ($dto['donde_consume'] ?? 'En restaurante')) ?: 'En restaurante';
        $nitFacturacion = trim((string) ($dto['nit_facturacion'] ?? 'C/F')) ?: 'C/F';
        $fechaConsumo = !empty($dto['fecha_consumo']) ? (string) $dto['fecha_consumo'] : date('Y-m-d');

        $this->db->beginTransaction();
        try {
            // Verificar stock en tiempo real y actualizar cantidad_solicitada
            $idsParaActualizar = [];
            foreach (['Entrada', 'Plato fuerte', 'Bebida', 'Postre'] as $cat) {
                if (!empty($detalles[$cat]['id'])) {
                    $idsParaActualizar[$cat] = (int) $detalles[$cat]['id'];
                }
            }

            foreach ($idsParaActualizar as $cat => $prodId) {
                $check = $this->db->prepare('SELECT id, stock, cantidad_solicitada, nombre FROM carta_productos WHERE id = :id FOR UPDATE');
                $check->execute(['id' => $prodId]);
                $pRow = $check->fetch();

                if (!$pRow) {
                    $this->db->rollBack();
                    return [false, "El producto de {$cat} ya no está disponible.", null];
                }

                $disponible = ((int) $pRow['stock']) - ((int) $pRow['cantidad_solicitada']);
                if ($disponible <= 0) {
                    $this->db->rollBack();
                    return [false, "Lo sentimos, el producto '{$pRow['nombre']}' se ha agotado en este momento.", null];
                }

                // Incrementar cantidad solicitada
                $updStock = $this->db->prepare('UPDATE carta_productos SET cantidad_solicitada = cantidad_solicitada + 1 WHERE id = :id');
                $updStock->execute(['id' => $prodId]);
            }

            // Insertar reserva en carta_reservas
            $ins = $this->db->prepare(
                'INSERT INTO carta_reservas (
                    usuario_id, entrada_id, plato_fuerte_id, bebida_id, postre_id,
                    total, fecha_reserva, fecha_consumo, donde_consume, estado, nit_facturacion
                 ) VALUES (
                    :usuario_id, :entrada_id, :plato_fuerte_id, :bebida_id, :postre_id,
                    :total, NOW(), :fecha_consumo, :donde_consume, "Activa", :nit_facturacion
                 )'
            );
            $ins->execute([
                'usuario_id' => $usuarioId,
                'entrada_id' => $idsParaActualizar['Entrada'] ?? null,
                'plato_fuerte_id' => $idsParaActualizar['Plato fuerte'] ?? null,
                'bebida_id' => $idsParaActualizar['Bebida'] ?? null,
                'postre_id' => $idsParaActualizar['Postre'] ?? null,
                'total' => $total,
                'fecha_consumo' => $fechaConsumo,
                'donde_consume' => $dondeConsume,
                'nit_facturacion' => $nitFacturacion,
            ]);

            $reservaId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return [true, "¡Reserva realizada exitosamente! Código de reserva #{$reservaId}. Total: Q" . number_format($total, 2), $reservaId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error al procesar reserva de La Carta: ' . $e->getMessage());
            return [false, 'Ocurrió un error al registrar la reserva en la base de datos.', null];
        }
    }

    /**
     * Recuento consolidado de productos y reservas para el panel de administración.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total_platillos_reservados: int,
     *     total_recaudado: float
    /**
     * Recuento consolidado de productos y reservas para el panel de administración.
     * Soporta filtrado opcional por fecha de consumo.
     *
     * @param string|null $fecha
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total_platillos_reservados: int,
     *     total_recaudado: float
     * }
     */
    public function obtenerConsolidado(?string $fecha = null): array
    {
        if (!empty($fecha)) {
            $sql = 'SELECT 
                        p.id, p.categoria, p.nombre, p.precio, p.stock,
                        p.hora_inicio, p.hora_fin, p.dias_habilitados, p.fecha_habilitacion, p.estado,
                        p.cantidad_solicitada AS total_historico,
                        (p.stock - p.cantidad_solicitada) AS stock_disponible,
                        COALESCE(COUNT(r.id), 0) AS cantidad_solicitada,
                        (COALESCE(COUNT(r.id), 0) * p.precio) AS total_recaudado
                    FROM carta_productos p
                    LEFT JOIN carta_reservas r ON (
                        r.fecha_consumo = :fecha AND (
                            r.entrada_id = p.id OR
                            r.plato_fuerte_id = p.id OR
                            r.bebida_id = p.id OR
                            r.postre_id = p.id
                        )
                    )
                    GROUP BY p.id
                    ORDER BY FIELD(p.categoria, "Entrada", "Plato fuerte", "Bebida", "Postre"), p.nombre ASC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['fecha' => $fecha]);
            $items = $stmt->fetchAll();
        } else {
            $sql = 'SELECT id, categoria, nombre, precio, stock, cantidad_solicitada,
                           (stock - cantidad_solicitada) AS stock_disponible,
                           (cantidad_solicitada * precio) AS total_recaudado,
                           hora_inicio, hora_fin, dias_habilitados, fecha_habilitacion, estado
                    FROM carta_productos
                    ORDER BY FIELD(categoria, "Entrada", "Plato fuerte", "Bebida", "Postre"), nombre ASC';
            $items = $this->db->query($sql)->fetchAll();
        }

        $totalPlatillos = 0;
        $totalRecaudado = 0.0;

        foreach ($items as $item) {
            $totalPlatillos += (int) $item['cantidad_solicitada'];
            $totalRecaudado += (float) $item['total_recaudado'];
        }

        return [
            'items' => $items,
            'total_platillos_reservados' => $totalPlatillos,
            'total_recaudado' => round($totalRecaudado, 2),
        ];
    }

    /**
     * Devuelve el detalle individual de reservas realizadas desde La Carta.
     *
     * @param string|null $fecha
     * @return array<int, array<string, mixed>>
     */
    public function obtenerReservasDetalladas(?string $fecha = null): array
    {
        $sql = 'SELECT cr.*, u.nombre AS usuario_nombre, u.email AS usuario_email, u.telefono AS usuario_telefono,
                       pe.nombre AS entrada_nombre, pe.precio AS entrada_precio,
                       pf.nombre AS plato_fuerte_nombre, pf.precio AS plato_fuerte_precio,
                       pb.nombre AS bebida_nombre, pb.precio AS bebida_precio,
                       pp.nombre AS postre_nombre, pp.precio AS postre_precio
                FROM carta_reservas cr
                INNER JOIN usuarios u ON u.id = cr.usuario_id
                LEFT JOIN carta_productos pe ON pe.id = cr.entrada_id
                LEFT JOIN carta_productos pf ON pf.id = cr.plato_fuerte_id
                LEFT JOIN carta_productos pb ON pb.id = cr.bebida_id
                LEFT JOIN carta_productos pp ON pp.id = cr.postre_id';

        $params = [];
        if (!empty($fecha)) {
            $sql .= ' WHERE cr.fecha_consumo = :fecha';
            $params['fecha'] = $fecha;
        }

        $sql .= ' ORDER BY cr.fecha_reserva DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function normalizarNombreCategoria(string $categoria): string
    {
        $cat = mb_strtolower(trim($categoria), 'UTF-8');
        if (str_contains($cat, 'entrada')) {
            return 'Entrada';
        }
        if (str_contains($cat, 'fuerte') || str_contains($cat, 'plato')) {
            return 'Plato fuerte';
        }
        if (str_contains($cat, 'bebida')) {
            return 'Bebida';
        }
        if (str_contains($cat, 'postre')) {
            return 'Postre';
        }
        return ucfirst($categoria);
    }

    private function quitarAcentos(string $cadena): string
    {
        $buscar = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
        $reemplazar = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'];
        return str_replace($buscar, $reemplazar, $cadena);
    }
}
