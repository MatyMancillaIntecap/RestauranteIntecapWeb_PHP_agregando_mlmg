<?php
/**
 * Controlador del modulo de cocina.
 *
 * Gestiona menu, imagenes, estados, consolidado y reportes de reservas del
 * dia, protegiendo cada accion para Cocina y Administrador.
 */
declare(strict_types=1);

class CocinaController extends Controller
{
    private CocinaService $cocinaService;

    /** Comprueba permisos y prepara el servicio de cocina. */
    public function __construct()
    {
        Auth::requireRole(['Cocina', 'Administrador']);
        $this->cocinaService = new CocinaService();
    }

    // GET /cocina/index
    /** Carga menu, consolidado y detalle para la fecha solicitada. */
    public function index(): void
    {
        $fecha = $this->normalizarFechaConsulta((string) $this->input('fecha', date('Y-m-d')));

        $this->render('cocina/index', [
            'fecha_consulta' => $fecha,
            'menus' => $this->cocinaService->obtenerMenusPorFecha($fecha),
            'consolidado' => $this->cocinaService->obtenerConsolidadoPorFecha($fecha),
            'reservas_detalle' => $this->cocinaService->obtenerReservasDetalladasPorFecha($fecha),
        ]);
    }

    /**
     * Asegura que el filtro de cocina siempre sea una fecha ISO válida.
     *
     * Las fechas inválidas o valores con hora se reemplazan por el día actual
     * para impedir que una entrada ambigua consulte registros históricos.
     */
    private function normalizarFechaConsulta(string $fecha): string
    {
        $fechaNormalizada = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        $errores = DateTimeImmutable::getLastErrors();

        if ($fechaNormalizada === false || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
            return date('Y-m-d');
        }

        return $fechaNormalizada->format('Y-m-d');
    }

    // POST /cocina/guardar-menu (multipart/form-data con imagen opcional)
    /** Valida y crea o actualiza un platillo, incluida su imagen opcional. */
    public function guardarMenu(): void
    {
        $nombrePlato = trim((string) $this->input('nombre_plato', ''));
        $precio      = (float) $this->input('precio', 0);
        $stock       = (int) $this->input('stock', 0);
        $fecha       = (string) ($this->input('fecha') ?: date('Y-m-d'));
        $horaHab     = (string) ($this->input('hora_habilitacion') ?: '');

        // Validaciones del lado servidor
        if ($nombrePlato === '') {
            $this->flash('error', 'El nombre del platillo es obligatorio.');
            $this->redirect('cocina/index?fecha=' . urlencode($fecha));
            return;
        }

        if ($precio <= 0) {
            $this->flash('error', 'El precio debe ser mayor a 0.');
            $this->redirect('cocina/index?fecha=' . urlencode($fecha));
            return;
        }

        if ($stock < 0) {
            $this->flash('error', 'El stock no puede ser negativo.');
            $this->redirect('cocina/index?fecha=' . urlencode($fecha));
            return;
        }

        // Normalizar hora_habilitacion a formato MySQL DATETIME
        if ($horaHab !== '' && strlen($horaHab) === 16) {
            // datetime-local envía "YYYY-MM-DDTHH:MM" → convertir a "YYYY-MM-DD HH:MM:00"
            $horaHab = str_replace('T', ' ', $horaHab) . ':00';
        } elseif ($horaHab === '') {
            $horaHab = date('Y-m-d H:i:s');
        }

        $menu = [
            'id'               => (int) $this->input('id', 0),
            'nombre_plato'     => $nombrePlato,
            'descripcion'      => trim((string) $this->input('descripcion', '')),
            'precio'           => $precio,
            'stock'            => $stock,
            'es_dieta'         => $this->input('es_dieta') !== null,
            'estado'           => (string) ($this->input('estado') ?: 'Disponible'),
            'fecha'            => $fecha,
            'hora_habilitacion'=> $horaHab,
        ];

        // Procesar imagen si se envía
        if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower((string) pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

            if (!in_array($ext, $allowed, true)) {
                $this->flash('error', 'Solo se permiten imágenes en formato JPG, PNG, GIF o WEBP.');
                $this->redirect('cocina/index?fecha=' . urlencode($fecha));
                return;
            }

            $uploadsDir = ROOT_PATH . '/public/images/menus';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $nombreArchivo = uniqid('menu_', true) . '.' . $ext;
            $rutaDestino   = $uploadsDir . '/' . $nombreArchivo;

            if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $rutaDestino)) {
                $menu['imagen_url'] = '/images/menus/' . $nombreArchivo;
            }
        }

        $this->cocinaService->actualizarMenu($menu);
        $this->flash('exito', 'Platillo guardado correctamente.');
        $this->redirect('cocina/index?fecha=' . urlencode($fecha));
    }

    // GET /cocina/obtener-menu-por-id/{id}
    /** Devuelve en JSON un platillo para el modal de edicion. */
    public function obtenerMenuPorId($id = null): void
    {
        $menu = $this->cocinaService->obtenerMenuPorId((int) $id);
        if ($menu === null) {
            $this->json(['error' => 'No encontrado'], 404);
            return;
        }
        $this->json($menu);
    }

    // POST /cocina/cambiar-estado
    /** Cambia el estado del platillo usando una lista permitida. */
    public function cambiarEstado(): void
    {
        $id         = (int) $this->input('id', 0);
        $nuevoEstado = (string) $this->input('nuevoEstado', '');

        $estadosPermitidos = ['Disponible', 'Agotado', 'Inactivo'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            $this->json(['error' => 'Estado no válido.'], 400);
            return;
        }

        $resultado = $this->cocinaService->cambiarEstadoMenu($id, $nuevoEstado);
        if (!$resultado) {
            $this->json(['error' => 'No se pudo actualizar el estado.'], 400);
            return;
        }
        $this->json(['ok' => true]);
    }

    // POST /cocina/eliminar-menu
    /** Elimina un platillo solamente si no tiene reservas asociadas. */
    public function eliminarMenu(): void
    {
        $id = (int) $this->input('id', 0);
        [$exito, $mensaje] = $this->cocinaService->eliminarMenuSinReservas($id);

        if (!$exito) {
            $this->json(['error' => $mensaje], 400);
            return;
        }
        $this->json(['ok' => true, 'mensaje' => $mensaje]);
    }

    // GET /cocina/descargar-excel
    /** Genera el detalle diario de reservas en formato XLSX. */
    public function descargarExcel(): void
    {
        $fecha = (string) $this->input('fecha', date('Y-m-d'));
        $filas = $this->cocinaService->obtenerReservasDetalladasPorFecha($fecha);

        $writer = new ExcelWriter();
        $writer->setSheetName('ReservasCocina');
        $writer->setTitle('RESERVAS DE COCINA');
        $writer->setSubtitle('Restaurante Escuela INTECAP · Fecha: ' . $fecha . ' · Generado: ' . date('d/m/Y H:i'));
        $writer->setHeaderColor('1F4E78');
        $writer->setColumnWidths([6, 24, 28, 10, 14, 14, 14, 14, 22, 10, 10]);
        $writer->setPageLayout('landscape', 1, 1, 0.25, 0.25, 0.35, 0.35);
        $writer->setIntegerColumns([0, 3]);
        $writer->setHeaders(['#', 'Empleado', 'Platillo', 'Cantidad', 'Precio Unitario', 'Total', 'Pago en Efectivo', 'Pago con Carnet', 'Consumo en Restaurante / Para Llevar', 'Dieta', 'Normal']);

        foreach ($filas as $item) {
            $totalFila = (float) $item['cantidad'] * (float) $item['precio_unitario'];

            $writer->addRow([
                (int) $item['reserva_id'],
                (string) $item['nombre_empleado'],
                (string) $item['nombre_plato'],
                (int) $item['cantidad'],
                (float) $item['precio_unitario'],
                (float) $totalFila,
                strcasecmp(trim((string) $item['forma_pago']), 'Efectivo') === 0 ? '✔' : '',
                strcasecmp(trim((string) $item['forma_pago']), 'Carnet') === 0 ? '✔' : '',
                (string) $item['donde_consume'],
                !empty($item['es_dieta']) ? '✔' : '',
                empty($item['es_dieta']) ? '✔' : '',
            ]);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Reservas_Cocina_' . str_replace('-', '', $fecha) . '.xlsx"');
        echo $writer->generate();
        exit;
    }

    // GET /cocina/descargar-pdf
    /** Genera el detalle diario de reservas en formato PDF. */
    public function descargarPdf(): void
    {
        $fecha = (string) $this->input('fecha', date('Y-m-d'));
        $filas = $this->cocinaService->obtenerReservasDetalladasPorFecha($fecha);

        $sumaCantidad = 0;
        $sumaTotal = 0.0;
        $filasTabla = [];

        foreach ($filas as $item) {
            $totalFila = (float) $item['cantidad'] * (float) $item['precio_unitario'];
            $sumaCantidad += (int) $item['cantidad'];
            $sumaTotal += $totalFila;

            $filasTabla[] = [
                $item['reserva_id'],
                $item['nombre_empleado'],
                $item['nombre_plato'],
                $item['cantidad'],
                'Q ' . number_format((float) $item['precio_unitario'], 2),
                'Q ' . number_format($totalFila, 2),
                strcasecmp(trim((string) $item['forma_pago']), 'Efectivo') === 0 ? 'X' : '',
                strcasecmp(trim((string) $item['forma_pago']), 'Carnet') === 0 ? 'X' : '',
                $item['donde_consume'],
                !empty($item['es_dieta']) ? 'X' : '',
                empty($item['es_dieta']) ? 'X' : '',
            ];
        }

        $pdf = new PdfWriter('Reservas de Cocina');
        $pdf->addLine('Fecha: ' . $fecha . ' · Generado: ' . date('d/m/Y H:i'));
        $pdf->setSummary([
            'Reservas' => (string) count($filas),
            'Platillos solicitados' => (string) $sumaCantidad,
            'Total recaudado' => 'Q ' . number_format($sumaTotal, 2),
        ]);
        $pdf->setTable(
            ['#', 'Empleado', 'Platillo', 'Cantidad', 'Precio Unitario', 'Total', 'Efectivo', 'Carnet', 'Consumo', 'Dieta', 'Normal'],
            $filasTabla,
            [25, 65, 80, 28, 43, 45, 38, 38, 75, 38, 40]
        );
        $pdf->setColumnAlignments(['right', 'left', 'left', 'right', 'right', 'right', 'center', 'center', 'left', 'center', 'center']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Reservas_Cocina_' . str_replace('-', '', $fecha) . '.pdf"');
        echo $pdf->generate();
        exit;
    }

    // Compatibilidad: la ruta vieja queda apuntando a Excel.
    /** Mantiene compatibilidad con la ruta CSV anterior. */
    public function descargarCsv(): void { $this->descargarExcel(); }
}
