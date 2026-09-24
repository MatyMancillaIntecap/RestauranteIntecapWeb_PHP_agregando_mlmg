<?php
/**
 * Controlador del flujo de reservas del empleado.
 *
 * Expone el menu, procesa reservas JSON, consulta el historial y permite
 * consultar su historial. Cocina y Administrador tambien pueden reservar.
 */
declare(strict_types=1);

class EmpleadoController extends Controller
{
    private EmpleadoService $empleadoService;

    /** Exige un rol con capacidad de realizar reservas. */
    public function __construct()
    {
        Auth::requireRole(['Empleado', 'Cocina', 'Administrador']);
        $this->empleadoService = new EmpleadoService();
    }

    // GET /empleado/index
    /** Carga el menu disponible y los limites del usuario actual. */
    public function index(): void
    {
        $usuarioId = Auth::id();
        $limiteMaximo = $this->empleadoService->obtenerLimiteAlmuerzosUsuario($usuarioId);
        $reservasHoy = $this->empleadoService->obtenerCantidadReservasActivasUsuarioFecha($usuarioId, date('Y-m-d'));

        $this->render('empleado/index', [
            'menus' => $this->empleadoService->obtenerMenuDisponiblePorFecha(date('Y-m-d'), $usuarioId, $limiteMaximo),
            'usuario_id' => $usuarioId,
            'limite_maximo' => $limiteMaximo,
            'reservas_hoy' => $reservasHoy,
            'nit_usuario' => $this->empleadoService->obtenerNitUsuario($usuarioId),
        ]);
    }

    // POST /empleado/realizar-reserva (cuerpo JSON)
    /** Convierte el carrito JSON en una solicitud de reserva validable. */
    public function realizarReserva(): void
    {
        $body = $this->jsonBody();

        if (empty($body)) {
            $this->json(['error' => 'Solicitud no válida.'], 400);
            return;
        }

        $solicitud = [
            'usuario_id' => Auth::id(),
            'nit_facturacion' => $body['nitFacturacion'] ?? 'C/F',
            'platillos' => array_map(static function ($item) {
                return [
                    'menu_id' => (int) $item['menuId'],
                    'cantidad' => (int) $item['cantidad'],
                    'forma_pago_id' => (int) $item['formaPagoId'],
                    'donde_consume' => $item['dondeConsume'] ?? 'En restaurante',
                ];
            }, $body['platillos'] ?? []),
        ];

        [$exito, $mensaje] = $this->empleadoService->procesarReserva($solicitud);

        if (!$exito) {
            $this->json(['error' => $mensaje], 400);
            return;
        }

        $this->json(['mensaje' => $mensaje]);
    }

    // GET /empleado/historial
    /** Muestra el historial filtrado por fecha del usuario actual. */
    public function historial(): void
    {
        $usuarioId = Auth::id();
        $fInicio = (string) $this->input('fechaInicio', date('Y-m-d'));
        $fFin = (string) $this->input('fechaFin', $fInicio);

        $this->render('empleado/historial', [
            'fecha_inicio' => $fInicio,
            'fecha_fin' => $fFin,
            'historial' => $this->empleadoService->obtenerHistorialUsuarioFiltrado($usuarioId, $fInicio, $fFin, 'Todos'),
        ]);
    }

    // GET /empleado/descargar-excel-historial
    /** Genera el historial personal en formato XLSX. */
    public function descargarExcelHistorial(): void
    {
        $usuarioId = Auth::id();
        $fechaInicio = $this->input('fechaInicio');
        $fechaFin = $this->input('fechaFin');

        $historial = $this->empleadoService->obtenerHistorialUsuarioFiltrado($usuarioId, $fechaInicio, $fechaFin, 'Todos');

        $writer = new ExcelWriter();
        $writer->setSheetName('MiHistorial');
        $writer->setTitle('MI HISTORIAL DE RESERVAS');
        $writer->setSubtitle('Restaurante Escuela INTECAP · Periodo: ' . ($fechaInicio ?: 'Todos') . ' al ' . ($fechaFin ?: 'Todos') . ' · Generado: ' . date('d/m/Y H:i'));
        $writer->setHeaderColor('0F766E');
        $writer->setColumnWidths([10, 14, 26, 10, 14, 12, 16, 16, 12, 12]);
        $writer->setHeaders(['#Reserva', 'Fecha Consumo', 'Platillo', 'Cantidad', 'Precio Unitario', 'Total', 'Forma Pago', 'Dónde Consume', 'NIT', 'Estado']);

        $sumaCantidad = 0;
        $sumaTotal = 0.0;

        foreach ($historial as $item) {
            $totalFila = (float) $item['cantidad'] * (float) $item['precio_unitario'];
            $sumaCantidad += (int) $item['cantidad'];
            $sumaTotal += $totalFila;

            $writer->addRow([
                (int) $item['reserva_id'],
                (string) $item['fecha_consumo'],
                (string) $item['nombre_plato'],
                (int) $item['cantidad'],
                (float) $item['precio_unitario'],
                (float) $totalFila,
                (string) $item['forma_pago'],
                (string) $item['donde_consume'],
                (string) $item['nit_facturacion'],
                (string) $item['estado'],
            ]);
        }

        $writer->setTotalRow(['TOTAL', '', count($historial) . ' reservas', $sumaCantidad, '', $sumaTotal, '', '', '', '']);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="MiHistorial_' . date('Ymd') . '.xlsx"');
        echo $writer->generate();
        exit;
    }

    // GET /empleado/descargar-pdf-historial
    /** Genera el historial personal en formato PDF. */
    public function descargarPdfHistorial(): void
    {
        $usuarioId = Auth::id();
        $fechaInicio = $this->input('fechaInicio');
        $fechaFin = $this->input('fechaFin');

        $historial = $this->empleadoService->obtenerHistorialUsuarioFiltrado($usuarioId, $fechaInicio, $fechaFin, 'Todos');

        $sumaCantidad = 0;
        $sumaTotal = 0.0;
        $filasTabla = [];

        foreach ($historial as $item) {
            $totalFila = (float) $item['cantidad'] * (float) $item['precio_unitario'];
            $sumaCantidad += (int) $item['cantidad'];
            $sumaTotal += $totalFila;

            $filasTabla[] = [
                $item['reserva_id'],
                $item['fecha_consumo'],
                $item['nombre_plato'],
                $item['cantidad'],
                'Q ' . number_format($totalFila, 2),
                $item['forma_pago'],
                $item['estado'],
            ];
        }

        $pdf = new PdfWriter('Mi Historial de Reservas');
        $pdf->addLine('Periodo: ' . ($fechaInicio ?: 'Todos') . ' al ' . ($fechaFin ?: 'Todos') . ' · Generado: ' . date('d/m/Y H:i'));
        $pdf->setSummary([
            'Reservas' => (string) count($historial),
            'Platillos' => (string) $sumaCantidad,
            'Total pagado' => 'Q ' . number_format($sumaTotal, 2),
        ]);
        $pdf->setTable(
            ['Reserva', 'Fecha', 'Platillo', 'Cant.', 'Total', 'Pago', 'Estado'],
            $filasTabla,
            [42, 58, 130, 35, 60, 65, 133]
        );

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="MiHistorial_' . date('Ymd') . '.pdf"');
        echo $pdf->generate();
        exit;
    }

    // Compatibilidad con rutas antiguas.
    /** Mantiene compatibilidad con la ruta CSV anterior. */
    public function descargarCsvHistorial(): void { $this->descargarExcelHistorial(); }

}
