<?php
declare(strict_types=1);
/**
 * Controlador de La Carta.
 *
 * Expone la visualización, selección y cálculo de totales para todos los usuarios autenticados,
 * y protege las operaciones de gestión (crear, editar, activar/desactivar, eliminar, subir imagen)
 * exclusivamente para el rol Administrador.
 */

require_once ROOT_PATH . '/services/CartaService.php';

class CartaController extends Controller
{
    private CartaService $cartaService;

    public function __construct()
    {
        // Todos los métodos de La Carta requieren que el usuario esté autenticado
        Auth::requireLogin();
        $this->cartaService = new CartaService();
    }

    // GET /carta o /carta/index
    // Vista principal para clientes y usuarios: visualización por categoría, selección (máx 1 por categoría) y total
    public function index(): void
    {
        $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(true);
        $esAdmin = (Auth::role() === 'Administrador');

        $this->render('carta/index', [
            'productosPorCategoria' => $productosPorCategoria,
            'categorias' => CartaService::CATEGORIAS,
            'esAdmin' => $esAdmin,
        ]);
    }

    // GET /carta/admin
    // Vista de administración de La Carta (exclusiva para Administrador)
    public function admin(): void
    {
        Auth::requireRole(['Administrador']);

        $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(false);

        $this->render('carta/admin', [
            'productosPorCategoria' => $productosPorCategoria,
            'categorias' => CartaService::CATEGORIAS,
        ]);
    }

    // POST /carta/guardar
    // Crea o actualiza un producto de La Carta con imagen opcional (exclusivo para Administrador)
    public function guardar(): void
    {
        Auth::requireRole(['Administrador']);

        $id = (int) $this->input('id', 0);
        $categoria = trim((string) $this->input('categoria', ''));
        $nombre = trim((string) $this->input('nombre', ''));
        $descripcion = trim((string) $this->input('descripcion', ''));
        $precio = (float) $this->input('precio', 0);
        $activo = $this->input('estado') !== null;

        // Validaciones del servidor
        if ($categoria === '') {
            $this->flash('error', 'Debe seleccionar una categoría para el producto.');
            $this->redirect('carta/admin');
            return;
        }

        if ($nombre === '') {
            $this->flash('error', 'El nombre del producto es obligatorio.');
            $this->redirect('carta/admin');
            return;
        }

        if ($precio <= 0) {
            $this->flash('error', 'El precio del producto debe ser un número mayor a Q0.00.');
            $this->redirect('carta/admin');
            return;
        }

        $datos = [
            'id' => $id,
            'categoria' => $categoria,
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'precio' => $precio,
            'estado' => $activo ? 1 : 0,
        ];

        // Procesar subida de imagen siguiendo el patrón de Cocina
        if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower((string) pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

            if (!in_array($ext, $permitidas, true)) {
                $this->flash('error', 'Formato de imagen no permitido. Utilice JPG, PNG, GIF, WEBP o AVIF.');
                $this->redirect('carta/admin');
                return;
            }

            $uploadsDir = ROOT_PATH . '/public/images/carta';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $nombreArchivo = uniqid('carta_', true) . '.' . $ext;
            $rutaDestino = $uploadsDir . '/' . $nombreArchivo;

            if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $rutaDestino)) {
                $datos['imagen'] = '/images/carta/' . $nombreArchivo;
            }
        }

        [$exito, $mensaje, $guardadoId] = $this->cartaService->guardarProducto($datos);

        if (!$exito) {
            $this->flash('error', $mensaje);
        } else {
            $this->flash('exito', $mensaje);
        }

        $this->redirect('carta/admin');
    }

    // GET /carta/obtener-producto-por-id/{id}
    // Devuelve los datos de un producto en formato JSON para el modal de edición (solo Administrador)
    public function obtenerProductoPorId($id = null): void
    {
        Auth::requireRole(['Administrador']);

        $producto = $this->cartaService->obtenerProductoPorId((int) $id);
        if ($producto === null) {
            $this->json(['error' => 'Producto no encontrado'], 404);
            return;
        }

        $this->json($producto);
    }

    // POST /carta/cambiar-estado
    // Activa o desactiva un producto mediante AJAX (solo Administrador)
    public function cambiarEstado(): void
    {
        Auth::requireRole(['Administrador']);

        $id = (int) $this->input('id', 0);
        $activo = (bool) $this->input('activo', false);

        if ($id <= 0) {
            $this->json(['error' => 'Identificador no válido.'], 400);
            return;
        }

        $resultado = $this->cartaService->cambiarEstado($id, $activo);
        if (!$resultado) {
            $this->json(['error' => 'No se pudo actualizar el estado del producto.'], 400);
            return;
        }

        $this->json(['ok' => true]);
    }

    // POST /carta/eliminar
    // Elimina un producto de La Carta (solo Administrador)
    public function eliminar(): void
    {
        Auth::requireRole(['Administrador']);

        $id = (int) $this->input('id', 0);
        [$exito, $mensaje] = $this->cartaService->eliminarProducto($id);

        if (!$exito) {
            $this->json(['error' => $mensaje], 400);
            return;
        }

        $this->json(['ok' => true, 'mensaje' => $mensaje]);
    }

    // POST /carta/calcular-total
    // Validación backend de las selecciones del usuario y cálculo exacto del total
    // Accesible para cualquier usuario autenticado
    public function calcularTotal(): void
    {
        $body = $this->jsonBody();
        if (empty($body)) {
            $body = $_POST;
        }

        // Estructurar arreglo de selección
        $selecciones = [
            'Entrada' => $body['entrada_id'] ?? $body['Entrada'] ?? null,
            'Plato fuerte' => $body['plato_fuerte_id'] ?? $body['Plato fuerte'] ?? null,
            'Bebida' => $body['bebida_id'] ?? $body['Bebida'] ?? null,
            'Postre' => $body['postre_id'] ?? $body['Postre'] ?? null,
        ];

        $resultado = $this->cartaService->validarYCalcularTotal($selecciones);

        if (!$resultado['valido']) {
            $this->json($resultado, 400);
            return;
        }

        $this->json($resultado, 200);
    }
}
