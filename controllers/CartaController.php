<?php
declare(strict_types=1);
/**
 * Controlador de La Carta.
 *
 * Coordina la visualización, selección interactiva (Sí/No) y reservas con control de stock y horarios.
 * Para el administrador, ofrece el panel unificado de todas las categorías,
 * gestión completa de productos y recuento consolidado de reservas.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/CartaService.php';
require_once __DIR__ . '/../services/ExcelWriter.php';
require_once __DIR__ . '/../services/PdfWriter.php';

class CartaController extends Controller
{
    private CartaService $cartaService;

    public function __construct()
    {
        // Se requiere sesión activa para cualquier interacción con La Carta
        Auth::requireLogin();
        $this->cartaService = new CartaService();
    }

    // GET /carta o /carta/index
    // Vista de cliente/usuario: muestra productos vigentes en su horario y con stock > 0
    public function index(): void
    {
        // Para comensales se muestran solo los productos activos, con horario vigente y stock disponible
        $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(true);
        $esAdmin = (Auth::role() === 'Administrador');

        // Obtener NIT predeterminado del usuario actual si existe
        $nitUsuario = 'C/F';
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('SELECT nit_facturacion FROM usuarios WHERE id = :id');
            $stmt->execute(['id' => Auth::id()]);
            $nitUsuario = $stmt->fetchColumn() ?: 'C/F';
        } catch (Throwable $e) {
            $nitUsuario = 'C/F';
        }

        $this->render('carta/index', [
            'productosPorCategoria' => $productosPorCategoria,
            'categorias' => CartaService::CATEGORIAS,
            'esAdmin' => $esAdmin,
            'nitUsuario' => $nitUsuario,
        ]);
    }

    // GET /carta/admin
    // Panel de administración de La Carta: catálogo general y recuento consolidado en pestañas con exportaciones Excel/PDF
    public function admin(): void
    {
        Auth::requireRole(['Administrador']);

        $tabActiva = (string) $this->input('tab', 'catalogo');
        $fechaParam = $this->input('fecha');
        $fechaFiltro = ($fechaParam !== null) ? trim((string) $fechaParam) : date('Y-m-d');

        $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(false);
        // Productos vigentes con stock para reservas directas desde el panel de administración
        $productosDisponibles = $this->cartaService->obtenerTodosAgrupadosPorCategoria(true);
        $consolidado = $this->cartaService->obtenerConsolidado($fechaFiltro !== '' ? $fechaFiltro : null);
        $reservasDetalladas = $this->cartaService->obtenerReservasDetalladas($fechaFiltro !== '' ? $fechaFiltro : null);

        // Lista de usuarios activos para permitir que el administrador reserve por sí mismo o para un comensal
        $db = Database::getConnection();
        $stmtUsuarios = $db->query('SELECT id, nombre, email, telefono FROM usuarios WHERE activo = 1 ORDER BY nombre ASC');
        $usuarios = $stmtUsuarios->fetchAll();

        $this->render('carta/admin', [
            'productosPorCategoria' => $productosPorCategoria,
            'productosDisponibles' => $productosDisponibles,
            'categorias' => CartaService::CATEGORIAS,
            'consolidado' => $consolidado,
            'reservasDetalladas' => $reservasDetalladas,
            'fechaFiltro' => $fechaFiltro,
            'tabActiva' => $tabActiva,
            'usuarios' => $usuarios,
        ]);
    }

    // POST /carta/guardar
    // Guarda o actualiza un producto con stock y horarios configurables (solo Administrador)
    public function guardar(): void
    {
        Auth::requireRole(['Administrador']);

        $id = (int) $this->input('id', 0);
        $categoria = trim((string) $this->input('categoria', ''));
        $nombre = trim((string) $this->input('nombre', ''));
        $descripcion = trim((string) $this->input('descripcion', ''));
        $precio = (float) $this->input('precio', 0);
        $stock = max(0, (int) $this->input('stock', 10));
        $activo = $this->input('estado') !== null;

        // Días y horario configurables
        $diasHabilitados = trim((string) $this->input('dias_habilitados', 'Todos')) ?: 'Todos';
        $fechaHabilitacion = trim((string) $this->input('fecha_habilitacion', '')) ?: null;
        $horaInicio = trim((string) $this->input('hora_inicio', '00:00')) ?: '00:00';
        $horaFin = trim((string) $this->input('hora_fin', '23:59')) ?: '23:59';

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
            'stock' => $stock,
            'dias_habilitados' => $diasHabilitados,
            'fecha_habilitacion' => $fechaHabilitacion,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado' => $activo ? 1 : 0,
        ];

        // Procesar imagen si se adjuntó
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
    // Calcula el total en backend a partir de las selecciones Sí/No
    public function calcularTotal(): void
    {
        $body = $this->jsonBody();
        if (empty($body)) {
            $body = $_POST;
        }

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

    // POST /carta/realizar-reserva
    // Registra una reserva en firme sobre La Carta y descuenta stock
    // Accesible para cualquier usuario autenticado (incluido el Administrador)
    public function realizarReserva(): void
    {
        $body = $this->jsonBody();
        if (empty($body)) {
            $body = $_POST;
        }

        $usuarioId = Auth::id();
        // Si el usuario es administrador y especificó un usuario_id válido, se permite reservar para dicho comensal
        if (Auth::role() === 'Administrador' && !empty($body['usuario_id'])) {
            $usuarioId = (int) $body['usuario_id'];
        }

        $dto = [
            'usuario_id' => $usuarioId,
            'entrada_id' => !empty($body['entrada_id']) ? (int) $body['entrada_id'] : null,
            'plato_fuerte_id' => !empty($body['plato_fuerte_id']) ? (int) $body['plato_fuerte_id'] : null,
            'bebida_id' => !empty($body['bebida_id']) ? (int) $body['bebida_id'] : null,
            'postre_id' => !empty($body['postre_id']) ? (int) $body['postre_id'] : null,
            'donde_consume' => $body['donde_consume'] ?? 'En restaurante',
            'nit_facturacion' => $body['nit_facturacion'] ?? 'C/F',
            'fecha_consumo' => !empty($body['fecha_consumo']) ? (string) $body['fecha_consumo'] : date('Y-m-d'),
        ];

        [$exito, $mensaje, $reservaId] = $this->cartaService->procesarReserva($dto);

        if (!$exito) {
            $this->json(['ok' => false, 'error' => $mensaje], 400);
            return;
        }

        $this->json(['ok' => true, 'mensaje' => $mensaje, 'reserva_id' => $reservaId], 200);
    }

    // GET /carta/descargar-excel
    // Genera el reporte en formato XLSX (Excel nativo), ya sea del Catálogo o del Recuento Consolidado
    public function descargarExcel(): void
    {
        Auth::requireRole(['Administrador']);

        $tab = (string) $this->input('tab', '');
        $tipo = (string) $this->input('tipo', '');

        // Reporte del Catálogo de La Carta
        if ($tab === 'catalogo' || $tipo === 'catalogo') {
            $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(false);
            $writer = new ExcelWriter();
            $writer->setSheetName('CatalogoLaCarta');
            $writer->setTitle('CATÁLOGO GENERAL DE PRODUCTOS - LA CARTA');
            $writer->setSubtitle('Restaurante Escuela INTECAP · Generado: ' . date('d/m/Y H:i'));
            $writer->setHeaderColor('1F4E78');
            $writer->setColumnWidths([8, 16, 26, 32, 14, 12, 12, 18, 18, 12]);
            $writer->setPageLayout('landscape', 1, 1, 0.25, 0.25, 0.35, 0.35);
            $writer->setIntegerColumns([0, 5, 6]);
            $writer->setHeaders([
                '# ID',
                'Categoría',
                'Nombre del Producto',
                'Descripción',
                'Precio (Q)',
                'Stock Inicial',
                'Stock Disp.',
                'Días Habilitados',
                'Horario de Habilitación',
                'Estado'
            ]);

            $totalProductos = 0;
            foreach ($productosPorCategoria as $categoria => $prods) {
                foreach ($prods as $p) {
                    $totalProductos++;
                    $horario = substr((string)$p['hora_inicio'], 0, 5) . ' a ' . substr((string)$p['hora_fin'], 0, 5);
                    $writer->addRow([
                        (int) $p['id'],
                        (string) $categoria,
                        (string) $p['nombre'],
                        (string) ($p['descripcion'] ?? 'Sin descripción'),
                        (float) $p['precio'],
                        (int) ($p['stock'] ?? 0),
                        (int) ($p['stock_disponible'] ?? 0),
                        (string) ($p['dias_habilitados'] ?: 'Todos los días'),
                        $horario,
                        ((int)($p['estado'] ?? 1) === 1) ? 'Activo' : 'Inactivo',
                    ]);
                }
            }

            $writer->setTotalRow([
                'TOTAL',
                $totalProductos . ' productos',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ]);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Catalogo_La_Carta_' . date('Ymd_His') . '.xlsx"');
            echo $writer->generate();
            exit;
        }

        // Reporte de Recuento Consolidado y Reservas
        $fechaParam = $this->input('fecha');
        $fecha = ($fechaParam !== null) ? trim((string) $fechaParam) : date('Y-m-d');
        $filas = $this->cartaService->obtenerReservasDetalladas($fecha !== '' ? $fecha : null);

        $writer = new ExcelWriter();
        $writer->setSheetName('ReservasLaCarta');
        $writer->setTitle('RECUENTO CONSOLIDADO Y RESERVAS DE LA CARTA');
        $subtitulo = 'Restaurante Escuela INTECAP · ';
        $subtitulo .= ($fecha !== '' ? 'Fecha: ' . $fecha : 'Histórico General');
        $subtitulo .= ' · Generado: ' . date('d/m/Y H:i');
        $writer->setSubtitle($subtitulo);
        $writer->setHeaderColor('1F4E78');
        $writer->setColumnWidths([10, 24, 28, 16, 20, 24, 18, 18, 14, 18, 14, 20]);
        $writer->setPageLayout('landscape', 1, 1, 0.25, 0.25, 0.35, 0.35);
        $writer->setIntegerColumns([0]);
        $writer->setHeaders([
            '# Reserva',
            'Comensal / Usuario',
            'Correo Electrónico',
            'Teléfono',
            'Entrada',
            'Plato Fuerte',
            'Bebida',
            'Postre',
            'Total (Q)',
            'Modalidad',
            'NIT Facturación',
            'Fecha y Hora'
        ]);

        $totalRecaudado = 0.0;
        foreach ($filas as $item) {
            $totalFila = (float) $item['total'];
            $totalRecaudado += $totalFila;

            $writer->addRow([
                (int) $item['id'],
                (string) ($item['usuario_nombre'] ?? 'Usuario'),
                (string) ($item['usuario_email'] ?? ''),
                (string) ($item['usuario_telefono'] ?? 'Sin registrar'),
                (string) ($item['entrada_nombre'] ?? '—'),
                (string) ($item['plato_fuerte_nombre'] ?? '—'),
                (string) ($item['bebida_nombre'] ?? '—'),
                (string) ($item['postre_nombre'] ?? '—'),
                (float) $totalFila,
                (string) ($item['donde_consume'] ?? 'En restaurante'),
                (string) ($item['nit_facturacion'] ?? 'C/F'),
                (string) $item['fecha_reserva'],
            ]);
        }

        // Fila final con totales destacados
        $writer->setTotalRow([
            'TOTAL',
            count($filas) . ' reservas',
            '',
            '',
            '',
            '',
            '',
            '',
            (float) $totalRecaudado,
            '',
            '',
            ''
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = 'Recuento_Consolidado_Carta_' . ($fecha !== '' ? str_replace('-', '', $fecha) : 'General') . '.xlsx';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $writer->generate();
        exit;
    }

    // GET /carta/descargar-pdf
    // Genera el reporte de La Carta en formato PDF nativo
    public function descargarPdf(): void
    {
        Auth::requireRole(['Administrador']);

        $tab = (string) $this->input('tab', '');
        $tipo = (string) $this->input('tipo', '');

        // Reporte del Catálogo de La Carta
        if ($tab === 'catalogo' || $tipo === 'catalogo') {
            $productosPorCategoria = $this->cartaService->obtenerTodosAgrupadosPorCategoria(false);
            $filasTabla = [];
            $totalProductos = 0;
            $totalActivos = 0;

            foreach ($productosPorCategoria as $categoria => $prods) {
                foreach ($prods as $p) {
                    $totalProductos++;
                    if ((int)($p['estado'] ?? 1) === 1) $totalActivos++;
                    $horario = substr((string)$p['hora_inicio'], 0, 5) . '-' . substr((string)$p['hora_fin'], 0, 5);
                    $stockTexto = (int)($p['stock_disponible'] ?? 0) . ' / ' . (int)($p['stock'] ?? 0);
                    $filasTabla[] = [
                        (string) $categoria,
                        (string) $p['nombre'],
                        'Q ' . number_format((float) $p['precio'], 2),
                        $stockTexto,
                        (string) ($p['dias_habilitados'] ?: 'Todos') . ' (' . $horario . ')',
                        ((int)($p['estado'] ?? 1) === 1) ? 'Activo' : 'Inactivo',
                    ];
                }
            }

            $pdf = new PdfWriter('Catálogo General de La Carta');
            $pdf->addLine('Restaurante Escuela INTECAP · Generado: ' . date('d/m/Y H:i'));
            $pdf->setSummary([
                'Categorías' => (string) count(CartaService::CATEGORIAS),
                'Total Productos' => (string) $totalProductos,
                'Productos Activos' => (string) $totalActivos,
            ]);
            $pdf->setTable(
                ['Categoría', 'Producto', 'Precio', 'Stock (Disp/Tot)', 'Días y Horarios', 'Estado'],
                $filasTabla,
                [75, 125, 55, 65, 120, 55]
            );
            $pdf->setColumnAlignments(['left', 'left', 'right', 'center', 'left', 'center']);

            header('Content-Type: application/pdf');
            $filename = 'Catalogo_La_Carta_' . date('Ymd_His') . '.pdf';
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $pdf->generate();
            exit;
        }

        // Reporte de Recuento Consolidado y Reservas
        $fechaParam = $this->input('fecha');
        $fecha = ($fechaParam !== null) ? trim((string) $fechaParam) : date('Y-m-d');
        $filas = $this->cartaService->obtenerReservasDetalladas($fecha !== '' ? $fecha : null);

        $sumaTotal = 0.0;
        $totalPlatillos = 0;
        $filasTabla = [];

        foreach ($filas as $item) {
            $totalFila = (float) $item['total'];
            $sumaTotal += $totalFila;

            $conteoPlatos = 0;
            if (!empty($item['entrada_id'])) $conteoPlatos++;
            if (!empty($item['plato_fuerte_id'])) $conteoPlatos++;
            if (!empty($item['bebida_id'])) $conteoPlatos++;
            if (!empty($item['postre_id'])) $conteoPlatos++;
            $totalPlatillos += $conteoPlatos;

            $comensalInfo = (string) ($item['usuario_nombre'] ?? 'Usuario');
            if (!empty($item['usuario_telefono'])) {
                $comensalInfo .= "\n" . $item['usuario_telefono'];
            }

            $filasTabla[] = [
                '#' . $item['id'],
                $comensalInfo,
                $item['entrada_nombre'] ?? '—',
                $item['plato_fuerte_nombre'] ?? '—',
                $item['bebida_nombre'] ?? '—',
                $item['postre_nombre'] ?? '—',
                'Q ' . number_format($totalFila, 2),
                $item['donde_consume'] ?? 'En restaurante',
                $item['nit_facturacion'] ?? 'C/F',
            ];
        }

        $pdf = new PdfWriter('Recuento Consolidado - La Carta');
        $lineaFecha = 'Fecha Consulta: ' . ($fecha !== '' ? $fecha : 'Histórico General') . ' · Generado: ' . date('d/m/Y H:i');
        $pdf->addLine($lineaFecha);
        $pdf->setSummary([
            'Reservas' => (string) count($filas),
            'Platillos solicitados' => (string) $totalPlatillos,
            'Total recaudado' => 'Q ' . number_format($sumaTotal, 2),
        ]);
        $pdf->setTable(
            ['#', 'Comensal', 'Entrada', 'Plato Fuerte', 'Bebida', 'Postre', 'Total', 'Modalidad', 'NIT'],
            $filasTabla,
            [25, 75, 70, 80, 55, 55, 45, 65, 45]
        );
        $pdf->setColumnAlignments(['center', 'left', 'left', 'left', 'left', 'left', 'right', 'center', 'center']);

        header('Content-Type: application/pdf');
        $filename = 'Recuento_Consolidado_Carta_' . ($fecha !== '' ? str_replace('-', '', $fecha) : 'General') . '.pdf';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf->generate();
        exit;
    }

    // Compatibilidad: la ruta CSV delega en Excel
    public function descargarCsv(): void
    {
        $this->descargarExcel();
    }
}
