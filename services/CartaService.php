<?php
declare(strict_types=1);
/**
 * Servicio de La Carta del restaurante.
 *
 * Gestiona el catálogo de productos organizado por las 4 categorías:
 * Entrada, Plato fuerte, Bebida y Postre. Centraliza CRUD, activación/desactivación,
 * validación backend y cálculo seguro del total de selecciones.
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
     * Obtiene todos los productos de La Carta agrupados por sus 4 categorías.
     *
     * @param bool $soloActivos Si es true, filtra únicamente productos con estado activo (1).
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function obtenerTodosAgrupadosPorCategoria(bool $soloActivos = false): array
    {
        // Inicializar estructura con las 4 categorías para garantizar que siempre existan
        $agrupados = [
            'Entrada' => [],
            'Plato fuerte' => [],
            'Bebida' => [],
            'Postre' => [],
        ];

        $sql = 'SELECT * FROM carta_productos';
        if ($soloActivos) {
            $sql .= ' WHERE estado = 1';
        }
        $sql .= ' ORDER BY categoria ASC, nombre ASC';

        $stmt = $this->db->query($sql);
        $productos = $stmt->fetchAll();

        foreach ($productos as $producto) {
            $cat = trim((string) $producto['categoria']);
            // Mapeo flexible para normalizar tildes o mayúsculas
            $catNormalizada = $this->normalizarNombreCategoria($cat);
            if (isset($agrupados[$catNormalizada])) {
                $agrupados[$catNormalizada][] = $producto;
            } else {
                $agrupados[$catNormalizada] = [$producto];
            }
        }

        return $agrupados;
    }

    /**
     * Obtiene todos los productos en una lista plana.
     *
     * @param bool $soloActivos
     * @return array
     */
    public function obtenerTodos(bool $soloActivos = false): array
    {
        $sql = 'SELECT * FROM carta_productos';
        if ($soloActivos) {
            $sql .= ' WHERE estado = 1';
        }
        $sql .= ' ORDER BY FIELD(categoria, "Entrada", "Plato fuerte", "Bebida", "Postre"), nombre ASC';

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Busca un producto por su identificador único.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerProductoPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM carta_productos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Crea o actualiza un producto de La Carta.
     *
     * @param array{
     *     id?: int,
     *     categoria: string,
     *     nombre: string,
     *     descripcion?: string|null,
     *     precio: float|int|string,
     *     imagen?: string|null,
     *     estado?: bool|int
     * } $datos
     * @return array{0: bool, 1: string, 2: ?int} [éxito, mensaje, id]
     */
    public function guardarProducto(array $datos): array
    {
        $id = (int) ($datos['id'] ?? 0);
        $categoria = trim((string) ($datos['categoria'] ?? ''));
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $descripcion = !empty($datos['descripcion']) ? trim((string) $datos['descripcion']) : null;
        $precio = (float) ($datos['precio'] ?? 0);
        $estado = !empty($datos['estado']) ? 1 : 0;
        $imagen = !empty($datos['imagen']) ? trim((string) $datos['imagen']) : null;

        // Validaciones en backend
        $categoriaNormalizada = $this->normalizarNombreCategoria($categoria);
        if (!in_array($categoriaNormalizada, self::CATEGORIAS, true)) {
            return [false, 'La categoría seleccionada no es válida. Debe ser: Entrada, Plato fuerte, Bebida o Postre.', null];
        }

        if ($nombre === '') {
            return [false, 'El nombre del producto es obligatorio.', null];
        }

        if (mb_strlen($nombre) > 100) {
            return [false, 'El nombre del producto no debe exceder los 100 caracteres.', null];
        }

        if ($precio <= 0) {
            return [false, 'El precio del producto debe ser mayor a 0.', null];
        }

        if ($id === 0) {
            // Inserción de nuevo producto
            $stmt = $this->db->prepare(
                'INSERT INTO carta_productos (categoria, nombre, descripcion, precio, imagen, estado, creado_en)
                 VALUES (:categoria, :nombre, :descripcion, :precio, :imagen, :estado, NOW())'
            );
            $stmt->execute([
                'categoria' => $categoriaNormalizada,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'imagen' => $imagen,
                'estado' => $estado,
            ]);

            $nuevoId = (int) $this->db->lastInsertId();
            return [true, 'Producto creado exitosamente en La Carta.', $nuevoId];
        }

        // Actualización de producto existente
        $existente = $this->obtenerProductoPorId($id);
        if (!$existente) {
            return [false, 'El producto a actualizar no existe.', null];
        }

        $sql = 'UPDATE carta_productos
                SET categoria = :categoria,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    estado = :estado';
        $params = [
            'categoria' => $categoriaNormalizada,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'estado' => $estado,
            'id' => $id,
        ];

        // Si se cargó una nueva imagen, se actualiza el campo; si no, se conserva la existente
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
     * Cambia el estado (activo/inactivo) de un producto mediante AJAX.
     *
     * @param int $id
     * @param bool $activo
     * @return bool
     */
    public function cambiarEstado(int $id, bool $activo): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE carta_productos SET estado = :estado WHERE id = :id');
        $stmt->execute([
            'estado' => $activo ? 1 : 0,
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un producto de La Carta y borra su archivo de imagen asociado si existe.
     *
     * @param int $id
     * @return array{0: bool, 1: string} [éxito, mensaje]
     */
    public function eliminarProducto(int $id): array
    {
        $producto = $this->obtenerProductoPorId($id);
        if (!$producto) {
            return [false, 'El producto no existe o ya fue eliminado.'];
        }

        // Eliminar imagen del disco si existe
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
     * Valida del lado del servidor las opciones seleccionadas por el usuario
     * y calcula el total de acuerdo con la regla:
     * COMO MÁXIMO 1 producto por categoría (Entrada, Plato fuerte, Bebida, Postre).
     *
     * @param array<string, mixed> $selecciones Arreglo asociativo con identificadores o nombres de categoría.
     * @return array{
     *     valido: bool,
     *     total: float,
     *     detalles: array<string, array<string, mixed>|null>,
     *     mensaje: string
     * }
     */
    public function validarYCalcularTotal(array $selecciones): array
    {
        // Mapa esperado de categorías
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

        foreach ($selecciones as $clave => $idValor) {
            $claveLimpia = strtolower(str_replace([' ', '-', '_'], '', (string) $clave));
            $categoriaNombre = null;

            foreach ($mapaCategorias as $alias => $nombreOficial) {
                if (str_replace([' ', '-', '_'], '', $alias) === $claveLimpia) {
                    $categoriaNombre = $nombreOficial;
                    break;
                }
            }

            if ($categoriaNombre === null) {
                // Clave no reconocida o parámetro extra
                continue;
            }

            // Si el valor está vacío, 0 o nulo, significa que el usuario decidió no seleccionar esta categoría
            if ($idValor === null || $idValor === '' || (int) $idValor === 0) {
                continue;
            }

            $productoId = (int) $idValor;
            if ($productoId <= 0) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El identificador seleccionado para {$categoriaNombre} no es válido.",
                ];
            }

            $producto = $this->obtenerProductoPorId($productoId);
            if (!$producto) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto seleccionado para {$categoriaNombre} no fue encontrado.",
                ];
            }

            // Validar que el producto esté activo
            if (!(bool) $producto['estado']) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto '{$producto['nombre']}' está actualmente desactivado y no puede ser seleccionado.",
                ];
            }

            // Validar que el producto pertenezca realmente a la categoría
            $catProducto = $this->normalizarNombreCategoria((string) $producto['categoria']);
            if ($catProducto !== $categoriaNombre) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "El producto '{$producto['nombre']}' pertenece a '{$catProducto}', no a '{$categoriaNombre}'.",
                ];
            }

            // Validar restricción: Máximo 1 por categoría
            if ($detalles[$categoriaNombre] !== null) {
                return [
                    'valido' => false,
                    'total' => 0.0,
                    'detalles' => $detalles,
                    'mensaje' => "No se permite seleccionar más de un producto para la categoría {$categoriaNombre}.",
                ];
            }

            $precioItem = (float) $producto['precio'];
            $detalles[$categoriaNombre] = [
                'id' => (int) $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $precioItem,
                'imagen' => $producto['imagen'],
            ];
            $total += $precioItem;
        }

        return [
            'valido' => true,
            'total' => round($total, 2),
            'detalles' => $detalles,
            'mensaje' => 'Selección y cálculo del total validados exitosamente.',
        ];
    }

    /**
     * Normaliza cadenas de categoría a los 4 nombres canónicos.
     *
     * @param string $categoria
     * @return string
     */
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
}
