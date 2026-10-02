<?php
declare(strict_types=1);

/**
 * Servicio para la gestión de Anuncios y Avisos Informativos del Sistema.
 *
 * Administra el almacenamiento, activación/desactivación, validación de fechas
 * y obtención de anuncios activos para la pantalla de login.
 */
class AnuncioService
{
    private PDO $db;

    public const PRESETS = [
        'servicio_carta' => [
            'nombre'  => 'Esta semana servicio a la carta, reserva martes a las 11:00 A.M.',
            'titulo'  => 'Servicio a La Carta',
            'mensaje' => 'Esta semana servicio a la carta, reserva martes a las 11:00 A.M.',
        ],
        'sin_desayuno' => [
            'nombre'  => 'Hoy no hay servicio de desayuno',
            'titulo'  => 'Aviso de Desayuno',
            'mensaje' => 'Hoy no hay servicio de desayuno',
        ],
        'sin_almuerzo' => [
            'nombre'  => 'Hoy no hay servicio de almuerzo',
            'titulo'  => 'Aviso de Almuerzo',
            'mensaje' => 'Hoy no hay servicio de almuerzo',
        ],
        'sin_cafe' => [
            'nombre'  => 'Hoy no hay servicio de café/escuela',
            'titulo'  => 'Aviso Café / Escuela',
            'mensaje' => 'Hoy no hay servicio de café/escuela',
        ],
        'personalizar' => [
            'nombre'  => 'Personalizar',
            'titulo'  => '',
            'mensaje' => '',
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->asegurarTabla();
    }

    /**
     * Asegura que la tabla de anuncios exista e inserte los datos iniciales predeterminados si está vacía.
     */
    public function asegurarTabla(): void
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS anuncios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(150) NOT NULL,
                tipo VARCHAR(100) NOT NULL DEFAULT 'Personalizar',
                mensaje TEXT NOT NULL,
                fecha_inicio DATETIME NOT NULL,
                fecha_fin DATETIME NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->exec($sql);

            $count = (int)$this->db->query("SELECT COUNT(*) FROM anuncios")->fetchColumn();
            if ($count === 0) {
                $hoy = date('Y-m-d');
                $inicioSemana = $hoy . ' 00:00:00';
                $finSemana = date('Y-m-d 23:59:59', strtotime('+7 days'));
                $finHoy = $hoy . ' 23:59:59';

                $stmt = $this->db->prepare(
                    "INSERT INTO anuncios (titulo, tipo, mensaje, fecha_inicio, fecha_fin, activo)
                     VALUES (:titulo, :tipo, :mensaje, :fecha_inicio, :fecha_fin, :activo)"
                );

                // 1. Esta semana servicio a la carta (Activo inicialmente)
                $stmt->execute([
                    'titulo'       => self::PRESETS['servicio_carta']['titulo'],
                    'tipo'         => self::PRESETS['servicio_carta']['nombre'],
                    'mensaje'      => self::PRESETS['servicio_carta']['mensaje'],
                    'fecha_inicio' => $inicioSemana,
                    'fecha_fin'    => $finSemana,
                    'activo'       => 1,
                ]);

                // 2. Hoy no hay servicio de desayuno (Plantilla disponible inactiva)
                $stmt->execute([
                    'titulo'       => self::PRESETS['sin_desayuno']['titulo'],
                    'tipo'         => self::PRESETS['sin_desayuno']['nombre'],
                    'mensaje'      => self::PRESETS['sin_desayuno']['mensaje'],
                    'fecha_inicio' => $inicioSemana,
                    'fecha_fin'    => $finHoy,
                    'activo'       => 0,
                ]);

                // 3. Hoy no hay servicio de almuerzo (Plantilla disponible inactiva)
                $stmt->execute([
                    'titulo'       => self::PRESETS['sin_almuerzo']['titulo'],
                    'tipo'         => self::PRESETS['sin_almuerzo']['nombre'],
                    'mensaje'      => self::PRESETS['sin_almuerzo']['mensaje'],
                    'fecha_inicio' => $inicioSemana,
                    'fecha_fin'    => $finHoy,
                    'activo'       => 0,
                ]);

                // 4. Hoy no hay servicio de café/escuela (Plantilla disponible inactiva)
                $stmt->execute([
                    'titulo'       => self::PRESETS['sin_cafe']['titulo'],
                    'tipo'         => self::PRESETS['sin_cafe']['nombre'],
                    'mensaje'      => self::PRESETS['sin_cafe']['mensaje'],
                    'fecha_inicio' => $inicioSemana,
                    'fecha_fin'    => $finHoy,
                    'activo'       => 0,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Error asegurando tabla anuncios: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los anuncios que deben mostrarse en el login:
     * - Activo = 1
     * - Fecha actual dentro del rango [fecha_inicio, fecha_fin]
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerAnunciosActivos(): array
    {
        try {
            $ahora = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare(
                'SELECT id, titulo, tipo, mensaje, fecha_inicio, fecha_fin, activo
                 FROM anuncios
                 WHERE activo = 1
                   AND fecha_inicio <= :ahora1
                   AND fecha_fin >= :ahora2
                 ORDER BY id DESC'
            );
            $stmt->execute(['ahora1' => $ahora, 'ahora2' => $ahora]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener anuncios activos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los anuncios registrados con su estado calculado para la gestión del Administrador.
     *
     * Estados calculados:
     * - 'activo': activo=1 y fecha_inicio <= ahora <= fecha_fin
     * - 'programado': activo=1 y fecha_inicio > ahora
     * - 'vencido': fecha_fin < ahora
     * - 'inactivo': activo=0 y fecha_fin >= ahora
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM anuncios ORDER BY id DESC');
            $anuncios = $stmt->fetchAll();
            $ahora = date('Y-m-d H:i:s');

            foreach ($anuncios as &$a) {
                if ((int)$a['activo'] === 0) {
                    $a['estado_calculado'] = 'inactivo';
                    $a['estado_label']     = 'Desactivado';
                    $a['estado_badge']     = 'secondary';
                    $a['icono']            = '⏸️';
                } elseif ($a['fecha_inicio'] > $ahora) {
                    $a['estado_calculado'] = 'programado';
                    $a['estado_label']     = 'Programado';
                    $a['estado_badge']     = 'info';
                    $a['icono']            = '⏰';
                } elseif ($a['fecha_fin'] < $ahora) {
                    $a['estado_calculado'] = 'vencido';
                    $a['estado_label']     = 'Vencido';
                    $a['estado_badge']     = 'danger';
                    $a['icono']            = '⌛';
                } else {
                    $a['estado_calculado'] = 'activo';
                    $a['estado_label']     = 'Activo en Login';
                    $a['estado_badge']     = 'success';
                    $a['icono']            = '📢';
                }
            }
            unset($a);

            return $anuncios;
        } catch (Throwable $e) {
            error_log('Error al obtener todos los anuncios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un anuncio por su ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM anuncios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Guarda o actualiza un anuncio.
     *
     * @param array<string, mixed> $datos
     * @return array{0: bool, 1: string, 2: int}
     */
    public function guardar(array $datos): array
    {
        $id = (int) ($datos['id'] ?? 0);
        $titulo = trim((string) ($datos['titulo'] ?? ''));
        $tipo = trim((string) ($datos['tipo'] ?? 'Personalizar')) ?: 'Personalizar';
        $mensaje = trim((string) ($datos['mensaje'] ?? ''));
        $fechaInicioRaw = trim((string) ($datos['fecha_inicio'] ?? ''));
        $fechaFinRaw = trim((string) ($datos['fecha_fin'] ?? ''));
        $activo = !empty($datos['activo']) ? 1 : 0;

        if ($titulo === '') {
            return [false, 'El título del anuncio es obligatorio.', 0];
        }

        if ($mensaje === '') {
            return [false, 'El mensaje del anuncio es obligatorio.', 0];
        }

        // Formatear fechas a formato Y-m-d H:i:s
        $fechaInicio = $this->normalizarFechaHora($fechaInicioRaw, true);
        $fechaFin = $this->normalizarFechaHora($fechaFinRaw, false);

        if (!$fechaInicio || !$fechaFin) {
            return [false, 'Las fechas de inicio y finalización deben ser válidas.', 0];
        }

        if (strtotime($fechaInicio) > strtotime($fechaFin)) {
            return [false, 'La fecha de inicio no puede ser posterior a la fecha de finalización.', 0];
        }

        try {
            if ($id > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE anuncios
                     SET titulo = :titulo,
                         tipo = :tipo,
                         mensaje = :mensaje,
                         fecha_inicio = :fecha_inicio,
                         fecha_fin = :fecha_fin,
                         activo = :activo,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->execute([
                    'titulo'       => $titulo,
                    'tipo'         => $tipo,
                    'mensaje'      => $mensaje,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin'    => $fechaFin,
                    'activo'       => $activo,
                    'id'           => $id,
                ]);
                return [true, 'Anuncio actualizado exitosamente.', $id];
            }

            $stmt = $this->db->prepare(
                'INSERT INTO anuncios (titulo, tipo, mensaje, fecha_inicio, fecha_fin, activo)
                 VALUES (:titulo, :tipo, :mensaje, :fecha_inicio, :fecha_fin, :activo)'
            );
            $stmt->execute([
                'titulo'       => $titulo,
                'tipo'         => $tipo,
                'mensaje'      => $mensaje,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'activo'       => $activo,
            ]);
            $nuevoId = (int) $this->db->lastInsertId();
            return [true, 'Anuncio publicado exitosamente.', $nuevoId];
        } catch (Throwable $e) {
            error_log('Error al guardar anuncio: ' . $e->getMessage());
            return [false, 'Error en la base de datos al guardar el anuncio: ' . $e->getMessage(), 0];
        }
    }

    /**
     * Cambia el estado activo/inactivo de un anuncio.
     */
    public function cambiarEstado(int $id, bool $activo): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('UPDATE anuncios SET activo = :activo, updated_at = NOW() WHERE id = :id');
            return $stmt->execute([
                'activo' => $activo ? 1 : 0,
                'id'     => $id,
            ]);
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de anuncio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un anuncio por su ID.
     *
     * @return array{0: bool, 1: string}
     */
    public function eliminar(int $id): array
    {
        if ($id <= 0) {
            return [false, 'ID de anuncio no válido.'];
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM anuncios WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return [true, 'Anuncio eliminado correctamente.'];
        } catch (Throwable $e) {
            error_log('Error al eliminar anuncio: ' . $e->getMessage());
            return [false, 'Error al eliminar el anuncio de la base de datos.'];
        }
    }

    /**
     * Normaliza un valor de fecha/hora recibido de un input HTML5.
     */
    private function normalizarFechaHora(string $val, bool $esInicio): ?string
    {
        $val = trim($val);
        if ($val === '') {
            return null;
        }

        // Si viene con 'T' de datetime-local (e.g. 2026-10-02T11:00)
        $val = str_replace('T', ' ', $val);

        if (strlen($val) === 10) { // YYYY-MM-DD
            $val .= $esInicio ? ' 00:00:00' : ' 23:59:59';
        } elseif (strlen($val) === 16) { // YYYY-MM-DD HH:MM
            $val .= ':00';
        }

        $time = strtotime($val);
        if ($time === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $time);
    }
}

