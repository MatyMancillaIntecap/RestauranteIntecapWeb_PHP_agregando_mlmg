<?php
/**
 * Entidades de dominio del restaurante.
 *
 * Son objetos simples que representan filas de la base de datos y permiten
 * transportar datos tipados entre servicios, controladores y vistas.
 */
declare(strict_types=1);

// Se usan como contenedores de datos (arrays asociativos convertidos a objetos) entre los Services y las Vistas.

/** Representa un rol y su límite diario de almuerzos. */
class Rol
{
    public int $id = 0;
    public string $nombre = '';
    public ?string $descripcion = null;
    public int $max_almuerzos = 2;

    /** Convierte una fila SQL en una entidad Rol tipada. */
    public static function fromRow(array $row): self
    {
        $rol = new self();
        $rol->id = (int) $row['id'];
        $rol->nombre = $row['nombre'];
        $rol->descripcion = $row['descripcion'] ?? null;
        $rol->max_almuerzos = (int) $row['max_almuerzos'];
        return $rol;
    }
}

/** Representa una cuenta autenticable y sus datos administrativos. */
class Usuario
{
    public int $id = 0;
    public string $nombre = '';
    public string $email = '';
    public ?string $telefono = null; // Número de teléfono del usuario
    public string $password = '';
    public int $rol_id = 0;
    public bool $activo = true;
    public string $fecha_creacion = '';
    public string $nit_facturacion = 'C/F';
    public ?string $rol_nombre = null; // Reemplaza al Include(u => u.Rol) de EF Core

    /** Convierte una fila SQL en una entidad Usuario. */
    public static function fromRow(array $row): self
    {
        $u = new self();
        $u->id = (int) $row['id'];
        $u->nombre = $row['nombre'];
        $u->email = $row['email'];
        $u->telefono = $row['telefono'] ?? null;
        $u->password = $row['password'];
        $u->rol_id = (int) $row['rol_id'];
        $u->activo = (bool) $row['activo'];
        $u->fecha_creacion = $row['fecha_creacion'];
        $u->nit_facturacion = $row['nit_facturacion'] ?? 'C/F';
        $u->rol_nombre = $row['rol_nombre'] ?? null;
        return $u;
    }
}

/** Representa una forma de pago disponible para una reserva. */
class FormaPago
{
    public int $id = 0;
    public string $nombre = '';

    /** Convierte una fila SQL en una entidad FormaPago. */
    public static function fromRow(array $row): self
    {
        $f = new self();
        $f->id = (int) $row['id'];
        $f->nombre = $row['nombre'];
        return $f;
    }
}

/** Representa un platillo publicado para una fecha concreta. */
class MenuDiario
{
    public int $id = 0;
    public string $nombre_plato = '';
    public ?string $descripcion = null;
    public float $precio = 0.0;
    public int $stock = 0;
    public int $cantidad_solicitada = 0;
    public ?string $imagen_url = null;
    public string $fecha = '';
    public string $hora_habilitacion = '';
    public bool $es_dieta = false;
    public string $estado = 'Disponible';

    /** Convierte una fila SQL en una entidad MenuDiario. */
    public static function fromRow(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->nombre_plato = $row['nombre_plato'];
        $m->descripcion = $row['descripcion'] ?? null;
        $m->precio = (float) $row['precio'];
        $m->stock = (int) $row['stock'];
        $m->cantidad_solicitada = (int) $row['cantidad_solicitada'];
        $m->imagen_url = $row['imagen_url'] ?? null;
        $m->fecha = $row['fecha'];
        $m->hora_habilitacion = $row['hora_habilitacion'];
        $m->es_dieta = (bool) $row['es_dieta'];
        $m->estado = $row['estado'];
        return $m;
    }
}

/** Representa una reserva de uno o varios almuerzos. */
class Reserva
{
    public int $id = 0;
    public int $usuario_id = 0;
    public int $menu_id = 0;
    public int $forma_pago_id = 0;
    public int $cantidad = 0;
    public string $donde_consume = 'En restaurante';
    public string $fecha_reserva = '';
    public string $fecha_consumo = '';
    public string $estado = 'Activa';
    public string $nit_facturacion = 'C/F';

    /** Convierte una fila SQL en una entidad Reserva. */
    public static function fromRow(array $row): self
    {
        $r = new self();
        $r->id = (int) $row['id'];
        $r->usuario_id = (int) $row['usuario_id'];
        $r->menu_id = (int) $row['menu_id'];
        $r->forma_pago_id = (int) $row['forma_pago_id'];
        $r->cantidad = (int) $row['cantidad'];
        $r->donde_consume = $row['donde_consume'];
        $r->fecha_reserva = $row['fecha_reserva'];
        $r->fecha_consumo = $row['fecha_consumo'];
        $r->estado = $row['estado'];
        $r->nit_facturacion = $row['nit_facturacion'] ?? 'C/F';
        return $r;
    }
}

/** Representa un registro de auditoría de inicio de sesión. */
class HistorialLogin
{
    public int $id = 0;
    public int $usuario_id = 0;
    public string $fecha_login = '';
}

// Representa un producto publicado en La Carta
class CartaProducto
{
    public int $id = 0;
    public string $categoria = '';
    public string $nombre = '';
    public ?string $descripcion = null;
    public float $precio = 0.0;
    public int $stock = 10;
    public int $cantidad_solicitada = 0;
    public ?string $imagen = null;
    public string $dias_habilitados = 'Todos';
    public ?string $fecha_habilitacion = null;
    public string $hora_inicio = '00:00:00';
    public string $hora_fin = '23:59:59';
    public bool $estado = true;
    public string $creado_en = '';

    /** Devuelve las unidades disponibles actualmente. */
    public function stockDisponible(): int
    {
        return max(0, $this->stock - $this->cantidad_solicitada);
    }

    /** Convierte una fila SQL en una entidad CartaProducto. */
    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->id = (int) $row['id'];
        $p->categoria = (string) $row['categoria'];
        $p->nombre = (string) $row['nombre'];
        $p->descripcion = $row['descripcion'] ?? null;
        $p->precio = (float) $row['precio'];
        $p->stock = (int) ($row['stock'] ?? 10);
        $p->cantidad_solicitada = (int) ($row['cantidad_solicitada'] ?? 0);
        $p->imagen = $row['imagen'] ?? null;
        $p->dias_habilitados = (string) ($row['dias_habilitados'] ?? 'Todos');
        $p->fecha_habilitacion = $row['fecha_habilitacion'] ?? null;
        $p->hora_inicio = (string) ($row['hora_inicio'] ?? '00:00:00');
        $p->hora_fin = (string) ($row['hora_fin'] ?? '23:59:59');
        $p->estado = (bool) $row['estado'];
        $p->creado_en = (string) ($row['creado_en'] ?? '');
        return $p;
    }
}

// Representa una reserva efectuada sobre La Carta
class CartaReserva
{
    public int $id = 0;
    public int $usuario_id = 0;
    public ?int $entrada_id = null;
    public ?int $plato_fuerte_id = null;
    public ?int $bebida_id = null;
    public ?int $postre_id = null;
    public float $total = 0.0;
    public string $fecha_reserva = '';
    public string $fecha_consumo = '';
    public string $donde_consume = 'En restaurante';
    public string $estado = 'Activa';
    public string $nit_facturacion = 'C/F';
    public ?string $usuario_nombre = null;
    public ?string $usuario_email = null;
    public ?string $usuario_telefono = null;

    /** Convierte una fila SQL en una entidad CartaReserva. */
    public static function fromRow(array $row): self
    {
        $r = new self();
        $r->id = (int) $row['id'];
        $r->usuario_id = (int) $row['usuario_id'];
        $r->entrada_id = isset($row['entrada_id']) && $row['entrada_id'] !== null ? (int) $row['entrada_id'] : null;
        $r->plato_fuerte_id = isset($row['plato_fuerte_id']) && $row['plato_fuerte_id'] !== null ? (int) $row['plato_fuerte_id'] : null;
        $r->bebida_id = isset($row['bebida_id']) && $row['bebida_id'] !== null ? (int) $row['bebida_id'] : null;
        $r->postre_id = isset($row['postre_id']) && $row['postre_id'] !== null ? (int) $row['postre_id'] : null;
        $r->total = (float) $row['total'];
        $r->fecha_reserva = (string) $row['fecha_reserva'];
        $r->fecha_consumo = (string) $row['fecha_consumo'];
        $r->donde_consume = (string) ($row['donde_consume'] ?? 'En restaurante');
        $r->estado = (string) ($row['estado'] ?? 'Activa');
        $r->nit_facturacion = (string) ($row['nit_facturacion'] ?? 'C/F');
        $r->usuario_nombre = $row['usuario_nombre'] ?? null;
        $r->usuario_email = $row['usuario_email'] ?? null;
        $r->usuario_telefono = $row['usuario_telefono'] ?? null;
        return $r;
    }
}
