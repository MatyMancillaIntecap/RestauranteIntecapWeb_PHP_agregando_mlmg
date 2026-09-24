<?php
/**
 * Controlador del panel administrativo.
 *
 * Coordina dashboard, usuarios, solicitudes de contrasena y exportaciones,
 * delegando el acceso a datos en AdminService y servicios especializados.
 */
declare(strict_types=1);

class AdminController extends Controller
{
    private AdminService $adminService;
    private CocinaService $cocinaService;
    private EmpleadoService $empleadoService;

    /** Verifica el rol administrador e inicializa los servicios requeridos. */
    public function __construct()
    {
        Auth::requireRole(['Administrador']);
        $this->adminService = new AdminService();
        $this->cocinaService = new CocinaService();
        $this->empleadoService = new EmpleadoService();
    }

    // GET /admin/index
    /** Carga los indicadores del panel para un rango de fechas. */
    public function index(): void
    {
        $inicio = (string) $this->input('fechaInicio', date('Y-m-d'));
        $fin = (string) $this->input('fechaFin', $inicio);

        if (strtotime($inicio) > strtotime($fin)) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        $data = [
            'dieta_solicitados' => $this->empleadoService->obtenerPlatillosDietaSolicitadosHoy($inicio, $fin),
            'dieta_iniciales' => $this->empleadoService->obtenerPlatillosDietaInicialesHoy($inicio, $fin),
            'normales_solicitados' => $this->empleadoService->obtenerPlatillosNormalesSolicitadosHoy($inicio, $fin),
            'normales_iniciales' => $this->empleadoService->obtenerPlatillosNormalesInicialesHoy($inicio, $fin),
            'ventas_hoy' => $this->empleadoService->obtenerVentasTotalesHoy($inicio, $fin),
            'reservas_hoy' => $this->empleadoService->obtenerTotalReservasHoy($inicio, $fin),
            'usuarios_con_reserva' => $this->empleadoService->obtenerUsuariosConReservasHoy($inicio, $fin),
            'total_usuarios' => $this->empleadoService->obtenerTotalUsuariosRegistrados(),
            'solicitudes_pendientes' => $this->adminService->obtenerCantidadSolicitudesRestablecimientoPendientes(),
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
        ];

        $this->render('admin/index', $data);
    }

    // GET /admin/usuarios
    /** Lista usuarios o devuelve el resultado de una busqueda por correo. */
    public function usuarios(): void
    {
        $correo = trim((string) $this->input('correo', ''));

        if ($correo !== '') {
            $usuario = $this->adminService->obtenerUsuarioPorCorreo($correo);
            $usuarios = $usuario ? [$usuario] : [];
        } else {
            $usuarios = $this->adminService->obtenerTodosLosUsuarios();
        }

        $this->render('admin/usuarios', [
            'usuarios' => $usuarios,
            'email_busqueda' => $correo,
            'roles' => $this->adminService->obtenerRoles(),
            'solicitudes_pendientes' => $this->adminService->obtenerCantidadSolicitudesRestablecimientoPendientes(),
        ]);
    }

    // GET /admin/detalle-usuario/{id}
    /** Muestra el detalle y el historial de un usuario. */
    public function detalleUsuario($id = null): void
    {
        $detalle = $this->adminService->obtenerDetalleCompletoUsuario((int) $id);
        if ($detalle === null) {
            http_response_code(404);
            echo 'Usuario no encontrado.';
            return;
        }

        $this->render('admin/detalle_usuario', ['detalle' => $detalle]);
    }

    // GET /admin/obtener-usuario-por-id/{id} (JSON para el modal)
    /** Devuelve datos de usuario para el formulario de edicion AJAX. */
    public function obtenerUsuarioPorId($id = null): void
    {
        $usuario = $this->adminService->obtenerUsuarioPorId((int) $id);
        if ($usuario === null) {
            $this->json(['error' => 'No encontrado'], 404);
            return;
        }
        $this->json($usuario);
    }

    // POST /admin/guardar-usuario
    /** Valida y crea o actualiza un usuario desde el formulario administrativo. */
    public function guardarUsuario(): void
    {
        $dto = [
            'id'             => (int) $this->input('id', 0),
            'nombre'         => trim((string) $this->input('nombre', '')),
            'email'          => trim((string) $this->input('email', '')),
            'password'       => (string) $this->input('password', ''),
            'rol_id'         => (int) $this->input('rol_id', 0),
            'activo'         => $this->input('activo') !== null,
            'nit_facturacion'=> trim((string) $this->input('nit_facturacion', 'C/F')) ?: 'C/F',
            'max_almuerzos'  => (int) $this->input('max_almuerzos', 2),
        ];

        // Validaciones del lado servidor
        if ($dto['nombre'] === '') {
            $this->flash('error', 'El nombre del usuario es obligatorio.');
            $this->redirect('admin/usuarios');
            return;
        }

        if ($dto['email'] === '' || !filter_var($dto['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Debe ingresar un correo electrónico válido.');
            $this->redirect('admin/usuarios');
            return;
        }

        if ($dto['rol_id'] <= 0) {
            $this->flash('error', 'Debe seleccionar un rol para el usuario.');
            $this->redirect('admin/usuarios');
            return;
        }

        // Contraseña en edición: si se proporciona, debe tener mínimo 8 caracteres
        if ($dto['id'] > 0 && $dto['password'] !== '' && strlen($dto['password']) < 8) {
            $this->flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            $this->redirect('admin/usuarios');
            return;
        }

        [$exito, $mensaje] = $this->adminService->guardarUsuario($dto);

        if (!$exito) {
            $this->flash('error', $mensaje);
        } elseif (str_contains($mensaje, 'CONTRASENA_INICIAL:')) {
            $partes = explode('|', $mensaje);
            $contrasenaInfo = trim(str_replace('CONTRASENA_INICIAL:', '', $partes[0]));
            $this->flash('exito', 'Usuario creado exitosamente. Contraseña inicial: ' . $contrasenaInfo);
        } else {
            $this->flash('exito', $mensaje);
        }

        $this->redirect('admin/usuarios');
    }

    // POST /admin/cambiar-estado-usuario
    /** Actualiza via AJAX el estado activo de una cuenta. */
    public function cambiarEstadoUsuario(): void
    {
        $id = (int) $this->input('id', 0);
        $activo = (bool) $this->input('activo', false);

        $resultado = $this->adminService->cambiarEstadoUsuario($id, $activo);
        if (!$resultado) {
            $this->json(['error' => 'No se pudo actualizar'], 400);
            return;
        }
        $this->json(['ok' => true]);
    }

    // POST /admin/eliminar-usuario
    /** Elimina una cuenta y devuelve un resultado JSON para la tabla administrativa. */
    public function eliminarUsuario(): void
    {
        $id = (int) $this->input('id', 0);
        [$exito, $mensaje] = $this->adminService->eliminarUsuario($id, Auth::id());

        if (!$exito) {
            $this->json(['error' => $mensaje], 400);
            return;
        }

        $this->json(['ok' => true, 'mensaje' => $mensaje]);
    }

    // GET /admin/solicitudes-restablecimiento
    /** Muestra solicitudes pendientes y ya atendidas. */
    public function solicitudesRestablecimiento(): void
    {
        $this->render('admin/solicitudes_restablecimiento', [
            'solicitudes' => $this->adminService->obtenerSolicitudesRestablecimiento(),
            'solicitudes_pendientes' => $this->adminService->obtenerCantidadSolicitudesRestablecimientoPendientes(),
        ]);
    }

    // POST /admin/atender-solicitud-restablecimiento
    /** Atiende una solicitud y asigna la contrasena temporal definida. */
    public function atenderSolicitudRestablecimiento(): void
    {
        $solicitudId = (int) $this->input('solicitud_id', 0);
        // El comportamiento original del sistema C# usa una contraseña temporal fija y no requiere
        // que el administrador escriba una nueva contraseña manualmente.
        $nuevaPassword = '87654321';

        [$exito, $mensaje] = $this->adminService->atenderSolicitudRestablecimiento($solicitudId, $nuevaPassword, Auth::id());
        $mensajeLimpio = $this->limpiarPrefijoMensaje($mensaje);

        if (!$exito) {
            $this->flash('error', $mensajeLimpio);
        } elseif (str_starts_with($mensaje, 'WARN:')) {
            $this->flash('advertencia', $mensajeLimpio);
        } else {
            $this->flash('exito', $mensajeLimpio);
        }

        $this->redirect('admin/solicitudes-restablecimiento');
    }

    /** Elimina prefijos internos de resultado antes de mostrar un mensaje. */
    private function limpiarPrefijoMensaje(string $mensaje): string
    {
        foreach (['OK:', 'WARN:', 'ERR:'] as $prefijo) {
            if (str_starts_with($mensaje, $prefijo)) {
                return trim(substr($mensaje, strlen($prefijo)));
            }
        }
        return $mensaje;
    }

    // GET /admin/descargar-reporte-excel
    public function descargarReporteExcel(): void
    {
        $fechaInicio = $this->input('fechaInicio') ?: null;
        $fechaFin    = $this->input('fechaFin')    ?: null;
        $usuarioId   = $this->input('usuarioId')   ? (int) $this->input('usuarioId') : null;
        $menuId      = $this->input('menuId')      ? (int) $this->input('menuId')    : null;

        if ($fechaInicio && $fechaFin && strtotime($fechaInicio) > strtotime($fechaFin)) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $filas = $this->adminService->obtenerReporteGlobal($fechaInicio, $fechaFin, $usuarioId, $menuId);
        $writer = new ExcelWriter();
        $writer->setSheetName('ReporteGlobal');
        $writer->setHeaders(['#Reserva', 'Fecha Reserva', 'Fecha Consumo', 'Usuario', 'Platillo', 'Cantidad', 'Precio Unit.', 'Total', 'Forma Pago', 'NIT', 'Estado']);

        foreach ($filas as $f) {
            $writer->addRow([
                (int) $f['id'],
                (string) $f['fecha_reserva'],
                (string) $f['fecha_consumo'],
                (string) $f['usuario'],
                (string) $f['nombre_plato'],
                (int) $f['cantidad'],
                (float) $f['precio'],
                (float) ($f['cantidad'] * $f['precio']),
                (string) $f['forma_pago'],
                (string) $f['nit_facturacion'],
                (string) $f['estado'],
            ]);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Reporte_Admin_Global_' . date('Ymd_Hi') . '.xlsx"');
        echo $writer->generate();
        exit;
    }

    // GET /admin/descargar-reporte-pdf
    public function descargarReportePdf(): void
    {
        $fechaInicio = $this->input('fechaInicio') ?: null;
        $fechaFin    = $this->input('fechaFin')    ?: null;
        $usuarioId   = $this->input('usuarioId')   ? (int) $this->input('usuarioId') : null;
        $menuId      = $this->input('menuId')      ? (int) $this->input('menuId')    : null;

        if ($fechaInicio && $fechaFin && strtotime($fechaInicio) > strtotime($fechaFin)) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $filas = $this->adminService->obtenerReporteGlobal($fechaInicio, $fechaFin, $usuarioId, $menuId);
        $sumaCantidad = 0;
        $sumaTotal = 0.0;
        $filasTabla = [];

        foreach ($filas as $f) {
            $totalFila = (int) $f['cantidad'] * (float) $f['precio'];
            $sumaCantidad += (int) $f['cantidad'];
            $sumaTotal += $totalFila;

            $filasTabla[] = [
                $f['id'],
                date('d/m/Y H:i', strtotime((string) $f['fecha_reserva'])),
                date('d/m/Y', strtotime((string) $f['fecha_consumo'])),
                $f['usuario'],
                $f['nombre_plato'],
                $f['cantidad'],
                'Q ' . number_format((float) $f['precio'], 2),
                'Q ' . number_format($totalFila, 2),
                $f['forma_pago'],
                $f['nit_facturacion'],
                $f['estado'],
            ];
        }

        $pdf = new PdfWriter('Reporte Global de Reservas');
        $pdf->addLine('Generado: ' . date('d/m/Y H:i'));
        $pdf->addLine('Periodo: ' . ($fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : 'Todos')
            . ' al ' . ($fechaFin ? date('d/m/Y', strtotime($fechaFin)) : 'Todos'));
        $pdf->setSummary([
            'Reservas' => (string) count($filas),
            'Platillos solicitados' => (string) $sumaCantidad,
            'Total recaudado' => 'Q ' . number_format($sumaTotal, 2),
        ]);
        $pdf->setTable(
            ['#', 'Reserva', 'Consumo', 'Usuario', 'Platillo', 'Cant.', 'P. Unit.', 'Total', 'Pago', 'NIT', 'Estado'],
            $filasTabla,
            [22, 55, 50, 75, 72, 28, 45, 48, 45, 40, 35]
        );

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Reporte_Admin_Global_' . date('Ymd_Hi') . '.pdf"');
        echo $pdf->generate();
        exit;
    }

    // GET /admin/descargar-usuarios-excel
    public function descargarUsuariosExcel(): void
    {
        $usuarios = $this->adminService->obtenerTodosLosUsuarios();

        $writer = new ExcelWriter();
        $writer->setSheetName('Usuarios');
        $writer->setTitle('GESTIÓN GENERAL DE USUARIOS');
        $writer->setSubtitle('Administra accesos, roles, estados, límites de platillos y NITs del personal.');
        $writer->setHeaderColor('0D6EFD');
        $writer->setColumnWidths([30, 34, 18, 18, 14, 14]);
        $writer->setHeaders(['Nombre Completo', 'Correo Electrónico', 'Rol', 'Límite Almuerzos', 'NIT Facturación', 'Estado']);
        $writer->setIntegerColumns([]);

        foreach ($usuarios as $u) {
            $writer->addRow([
                (string) $u['nombre'],
                (string) $u['email'],
                (string) $u['nombre_rol'],
                (int) $u['max_almuerzos'] === 0 ? 'Ilimitado' : (int) $u['max_almuerzos'] . ' / día',
                (string) $u['nit_facturacion'],
                $u['activo'] ? 'Activo' : 'Inactivo',
            ]);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Usuarios_Sistema_' . date('Ymd') . '.xlsx"');
        echo $writer->generate();
        exit;
    }

    // GET /admin/descargar-usuarios-pdf
    public function descargarUsuariosPdf(): void
    {
        $usuarios = $this->adminService->obtenerTodosLosUsuarios();
        $activos = count(array_filter($usuarios, static fn ($u) => (bool) $u['activo']));

        $pdf = new PdfWriter('Padrón de Usuarios del Sistema');
        $pdf->addLine('Generado: ' . date('d/m/Y H:i'));
        $pdf->setSummary([
            'Total de usuarios' => (string) count($usuarios),
            'Activos' => (string) $activos,
            'Inactivos' => (string) (count($usuarios) - $activos),
        ]);

        $filas = [];
        foreach ($usuarios as $u) {
            $filas[] = [
                $u['id'],
                $u['nombre'],
                $u['email'],
                $u['nombre_rol'],
                (int) $u['max_almuerzos'] === 0 ? 'Ilimitado' : $u['max_almuerzos'],
                $u['activo'] ? 'Activo' : 'Inactivo',
            ];
        }
        $pdf->setTable(['ID', 'Nombre', 'Correo', 'Rol', 'Límite', 'Estado'], $filas, [30, 115, 155, 85, 55, 83]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Usuarios_Sistema_' . date('Ymd') . '.pdf"');
        echo $pdf->generate();
        exit;
    }

    // Compatibilidad: las rutas antiguas quedan reencaminadas a Excel/PDF.
    public function descargarReporteCsv(): void { $this->descargarReporteExcel(); }
    public function descargarUsuariosCsv(): void { $this->descargarUsuariosExcel(); }
}
