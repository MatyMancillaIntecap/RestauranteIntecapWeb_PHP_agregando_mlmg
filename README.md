# Restaurante Escuela INTECAP — Sistema Web PHP

Sistema de gestión de reservas de almuerzos para el Restaurante Escuela INTECAP.  
Desarrollado completamente en **PHP 8.2 · MVC · MySQL · PDO · XAMPP · Apache**.

> Este proyecto es completamente independiente. No depende de ningún otro proyecto, librería de terceros ni framework externo. Solo requiere PHP, MySQL y Apache (incluidos en XAMPP).

---

## Índice

1. [Requisitos del sistema](#1-requisitos-del-sistema)
2. [Instalación en XAMPP](#2-instalación-en-xampp)
3. [Configuración de la base de datos](#3-configuración-de-la-base-de-datos)
4. [Configuración de correo SMTP](#4-configuración-de-correo-smtp-opcional)
5. [Ejecución](#5-ejecución)
6. [Usuarios, roles y contraseñas](#6-usuarios-roles-y-contraseñas)
7. [Módulos y funcionalidades](#7-módulos-y-funcionalidades)
8. [Reglas de negocio](#8-reglas-de-negocio)
9. [Estructura de carpetas](#9-estructura-de-carpetas)
10. [Guía de mantenimiento](#10-guía-de-mantenimiento)
11. [Dependencias y versiones](#11-dependencias-y-versiones)
12. [Separación física del proyecto](#12-separación-física-del-proyecto)
13. [Matriz de paridad funcional](#13-matriz-de-paridad-funcional)
14. [Pestañas Marcadas y Adaptación Móvil](#14-novedades-de-diseño-pestañas-marcadas-y-adaptación-móvil--escritorio)
15. [Mejora Integral de Visibilidad y Organización Visual](#15-mejora-integral-de-visibilidad-límites-y-organización-visual-en-todo-el-sistema)

---

## 1. Requisitos del sistema

| Componente | Versión mínima |
|------------|---------------|
| PHP        | 8.2           |
| MySQL      | 5.7 / MariaDB 10.4 |
| Apache     | 2.4 con `mod_rewrite` habilitado |
| XAMPP      | 8.2.x         |

**Extensiones PHP requeridas** (incluidas en XAMPP):

- `pdo_mysql` — conexión a MySQL
- `session` — autenticación de usuarios
- `openssl` — conexiones SMTP TLS
- `fileinfo` — validación de imágenes subidas

---

## 2. Instalación en XAMPP

### Paso 1 — Copiar el proyecto

Copia la carpeta completa `RestauranteIntecapWeb_PHP` dentro de:

```
C:\xampp\htdocs\RestauranteIntecapWeb_PHP\
```

### Paso 2 — Activar mod_rewrite en Apache

1. Abre **XAMPP Control Panel → Apache → Config → httpd.conf**
2. Descomenta la línea: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Busca el bloque `<Directory "C:/xampp/htdocs">` y cambia `AllowOverride None` a `AllowOverride All`
4. Guarda el archivo y reinicia Apache desde el panel de XAMPP

### Paso 3 — Crear el archivo .env

Desde la carpeta del proyecto, copia el archivo de ejemplo:

```
.env.example  →  .env
```

Edita `.env` con los valores de tu entorno:

```ini
APP_NAME=Restaurante INTECAP
APP_ENV=development
DB_HOST=127.0.0.1
DB_NAME=intecap_proy_rest_m
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

---

## 3. Configuración de la base de datos

### Paso 1 — Crear la estructura

Abre phpMyAdmin (`http://localhost/phpmyadmin`) e importa el archivo:

```
database/schema.sql
```

Este archivo crea la base de datos `intecap_proy_rest_m` con:

- Todas las tablas con índices y claves foráneas
- Datos base: roles y formas de pago
- Juego de caracteres utf8mb4

### Paso 2 — Crear el administrador inicial

Abre una terminal en la carpeta raíz del proyecto y ejecuta:

```bash
php database/seed_admin.php
```

Credenciales creadas:

| Campo | Valor |
|-------|-------|
| Correo | `admin@intecap.edu.gt` |
| Contraseña | `12345678` |

> Cambia la contraseña al iniciar sesión por primera vez.

### Tablas de la base de datos

| Tabla | Descripción |
|-------|-------------|
| `roles` | Roles del sistema con límite de almuerzos por rol |
| `usuarios` | Cuentas con contraseña hasheada en bcrypt |
| `formas_pago` | Efectivo y Carnet |
| `menu_diario` | Platillos del almuerzo con stock, precio, imagen y hora de habilitación |
| `reservas` | Reservas de almuerzos activas y canceladas con NIT de facturación |
| `historial_login` | Auditoría de cada inicio de sesión |
| `carta_productos` | Catálogo de productos de **La Carta** (Entrada, Plato fuerte, Bebida, Postre), con precio, stock, días habilitados, fecha opcional, horarios y foto |
| `carta_reservas` | Registro consolidado de reservas de **La Carta** por usuario, con desglose de ítems, total, consumo y NIT |

> **Nota sobre Solicitudes de Contraseña:** El flujo manual que almacenaba peticiones para atención del administrador en `solicitudes_restablecimiento_password` fue eliminado. Ahora el restablecimiento es automático a `87654321` vía correo SMTP sin intervención administrativa.

---

## 4. Configuración de correo SMTP (opcional)

El sistema puede enviar notificaciones al restablecer contraseñas. Agrega en `.env`:

```ini
CORREO_HOST=smtp.gmail.com
CORREO_PUERTO=587
CORREO_USAR_SSL=true
CORREO_USUARIO=tu_correo@gmail.com
CORREO_CONTRASENA=tu_app_password
CORREO_REMITENTE=tu_correo@gmail.com
CORREO_REMITENTE_NOMBRE=Restaurante Escuela INTECAP
```

Si no configuras SMTP, el sistema funciona igual. Solo omite el envío de correos.

> Para Gmail: usa una **Contraseña de aplicación**, no tu contraseña normal. Actívala en `myaccount.google.com → Seguridad → Contraseñas de aplicaciones`.

---

## 5. Ejecución

Inicia XAMPP (Apache + MySQL) y accede desde el navegador:

```
http://localhost/RestauranteIntecapWeb_PHP/
http://localhost/RestauranteIntecapWeb_PHP_agregando_mlmg-main/

```

El sistema detecta automáticamente si hay sesión activa y redirige al login o al módulo correspondiente.

---

## 6. Usuarios, roles y contraseñas

### Roles disponibles

| Rol | Descripción | Acceso |
|-----|-------------|--------|
| **Administrador** | Control total del sistema | Dashboard, usuarios, cocina, reservas, reportes, **La Carta** (gestión unificada, recuento consolidado y reservas) |
| **Cocina** | Gestión del menú del día | Publicar platillos del día, ver reservas de cocina, reportes, **La Carta** (visualización y reservas) |
| **Empleado** | Usuario estándar | Menú del día, historial de reservas, **La Carta** (visualización y reservas con Sí/No) |

Correo: admin@intecap.edu.gt  
Contraseña inicial: 12345678  

Cocina:  
Correo: cocina@intecap.edu.gt  
Contraseña inicial: 12345678  

Empleado:  
Correo: empleado@intecap.edu.gt  
Contraseña inicial: 12345678  

### Contraseñas del sistema

| Situación | Contraseña | Comportamiento |
|-----------|-----------|----------------|
| Nuevo usuario creado por admin | `12345678` | Debe cambiar contraseña al primer ingreso |
| Restablecimiento automático a solicitud del usuario | `87654321` | Se asigna automáticamente (bcrypt), se envía por correo SMTP y se exige cambio al ingresar |
| Mínimo para cambio manual | 8 caracteres | Validación de seguridad |

### Límite de almuerzos por día

- **Empleados**: 2 almuerzos por día (configurable por rol)
- **Administradores / Cocina**: ilimitado (valor 0 en la base de datos)
- El límite puede ajustarse por rol desde el panel de administración

---

## 7. Módulos y funcionalidades

### 🔐 Autenticación (`/account/`)

| Ruta | Descripción |
|------|-------------|
| `GET/POST /account/login` | Formulario de inicio de sesión. Redirige según rol. Soporta "Recordarme" (cookie 30 días) |
| `GET /account/logout` | Cierra la sesión y destruye la cookie |
| `GET/POST /account/recuperar-password` | Restablece automáticamente la contraseña a `87654321`, envía correo con credenciales y fuerza cambio al ingresar. Rollback automático si falla el envío |
| `GET/POST /account/cambiar-password` | Cambia la contraseña del usuario autenticado |
| `GET /account/acceso-denegado` | Pantalla de acceso denegado por rol insuficiente |

### 👨‍💼 Administración (`/admin/`)

| Ruta | Descripción |
|------|-------------|
| `GET /admin/index` | Dashboard con KPIs: ventas, reservas, platillos dieta/normal, usuarios. Filtrable por rango de fechas. Acceso directo a La Carta |
| `GET /admin/usuarios` | Lista completa de usuarios. Busqueda por correo. Toggle de activar/desactivar via AJAX |
| `GET /admin/detalle-usuario/{id}` | Ficha completa: datos del usuario + historial de reservas + totales acumulados |
| `GET /admin/obtener-usuario-por-id/{id}` | JSON para cargar datos en el modal de edición |
| `POST /admin/guardar-usuario` | Crear o editar usuario con validaciones del lado servidor |
| `POST /admin/cambiar-estado-usuario` | Activar o desactivar usuario via AJAX (sin recarga de página) |
| `GET /admin/descargar-reporte-csv` | CSV global de reservas. Filtros: fechas, estado (Activa/Cancelada/Todos) |
| `GET /admin/descargar-usuarios-csv` | Padrón completo de usuarios en CSV |

### 📖 La Carta (`/carta/`)

| Ruta | Descripción |
|------|-------------|
| `GET /carta/index` | Vista cliente / comensal: catálogo organizado por las 4 categorías (Entrada, Plato fuerte, Bebida, Postre). Botones de respuesta **Sí (✓)** y **No (✗)**, visualización clara de unidades disponibles, filtro por horario/stock, desglose en vivo y confirmación de reserva |
| `GET /carta/admin` | Panel de administración organizado en 2 pestañas: **Catálogo de La Carta** (gestión unificada de las 4 categorías) y **Recuento Consolidado** (métricas, detalle de reservas con filtro de fecha y exportaciones). Enlace a "Ver Vista Cliente" y botón directo para "Realizar Reserva" |
| `POST /carta/guardar` | Crear o editar producto con subida de imagen, stock disponible, días habilitados, fecha específica opcional y rango horario de atención (solo Administrador) |
| `GET /carta/obtener-producto-por-id/{id}` | Endpoint JSON para cargar datos en el modal de edición de La Carta |
| `POST /carta/cambiar-estado` | Toggle AJAX Activo / Inactivo para productos de La Carta |
| `POST /carta/eliminar` | Eliminación de producto con borrado de su archivo de imagen físico (solo Administrador) |
| `POST /carta/calcular-total` | Endpoint JSON para validar combinaciones (máx. 1 por categoría) y calcular total exacto |
| `POST /carta/realizar-reserva` | Registra una reserva en `carta_reservas` y descuenta el stock en transacción segura. Accesible tanto para usuarios como para administradores (pudiendo el admin reservar a su nombre o en nombre de un comensal) |
| `GET /carta/descargar-excel` | Genera y descarga el reporte consolidado y detalle de reservas de La Carta en formato Excel real (`.xlsx`) |
| `GET /carta/descargar-pdf` | Genera y descarga el reporte consolidado y detalle de reservas de La Carta en formato PDF nativo (`.pdf`) |

### 🔥 Cocina (`/cocina/`)

| Ruta | Descripción |
|------|-------------|
| `GET /cocina/index` | Vista con 3 pestañas: Gestión de Menú / Consolidado / Detalle de Reservas |
| `POST /cocina/guardar-menu` | Crear platillo (formulario lateral) o editar (modal). Con imagen, validaciones y hora de habilitación |
| `GET /cocina/obtener-menu-por-id/{id}` | JSON para llenar el modal de edición |
| `POST /cocina/cambiar-estado` | Toggle Disponible ↔ Inactivo. Valida estados permitidos |
| `POST /cocina/eliminar-menu` | Solo elimina si no tiene reservas asociadas |
| `GET /cocina/descargar-csv` | CSV del día: empleado, platillo, cantidad, consumo, pago, hora |

**Pestaña Consolidado**: muestra tarjetas por platillo con total solicitado y total recaudado. Indica si el platillo fue deshabilitado.

**Pestaña Detalle de Reservas**: tabla con empleado, correo, platillo, cantidad, donde consume, forma de pago, hora de reserva y estado del platillo.

### 🍽️ Empleado / Reservas (`/empleado/`)

| Ruta | Descripción |
|------|-------------|
| `GET /empleado/index` | Menú del día con carrito lateral. Botones +/- de cantidad. Modal de límite alcanzado |
| `POST /empleado/realizar-reserva` | Endpoint JSON. Valida límite diario, stock, NIT |
| `GET /empleado/historial` | Historial con imagen del platillo, hora de solicitud, donde consume, NIT. Botón cancelar para reservas activas |
| `POST /empleado/cancelar-reserva` | Cancela reserva activa y devuelve stock |
| `GET /empleado/descargar-csv-historial` | CSV personal filtrado por rango de fechas |

---

## 8. Reglas de negocio

### La Carta (Módulo a la carta)

- **4 Categorías independientes**: Entrada, Plato fuerte, Bebida y Postre.
- **Selección mediante Sí (✓) / No (✗)**: cada opción presenta únicamente dos respuestas interactivas.
- **Restricción de selección**: Como máximo **un producto** seleccionado por cada categoría (0 o 1).
- **Flexibilidad de combinación**: El comensal puede elegir cualquier combinación (ej. Entrada + Plato fuerte + Bebida, o únicamente Plato fuerte, etc.).
- **Cálculo del total**: En tiempo real y verificado en backend:
  $$\text{TOTAL} = \text{Entrada} + \text{Plato fuerte} + \text{Bebida} + \text{Postre}$$
  Las categorías no seleccionadas contabilizan **Q 0.00**.
- **Control de stock y visibilidad**:
  - Se muestra claramente el número de unidades disponibles (`stock - cantidad_solicitada`).
  - Cuando el stock llega a 0, la opción deja de mostrarse a los comensales o queda inhabilitada automáticamente.
- **Habilitación configurable por días, fechas y horarios**:
  - El administrador define los días habilitados (ej. "Solo Martes", "Todos los días", etc.), fecha específica opcional y rango horario (ej. `08:00` a `10:00`).
  - Fuera del horario o día configurado, el producto no aparece disponible para los usuarios.
- **Panel de Administrador y Recuento Consolidado**:
  - Panel unificado que muestra todas las categorías juntas sin pestañas redundantes.
  - El administrador puede registrar reservas tanto directamente desde el panel mediante el modal "Realizar Reserva" como navegando a "Ver Vista Cliente".
  - Apartado de **Recuento Consolidado** con métricas por producto (stock inicial, solicitados, disponible y recaudación) y tabla detallada de cada reserva.

### Reservas de Menú del Día

- Solo se muestran platillos con estado `Disponible`, `stock > 0` y `hora_habilitacion <= NOW()`.
- El límite diario se calcula contando reservas **Activas** de platillos **Disponibles**.
- Si un platillo pasa a `Inactivo`, sus reservas se conservan como historial pero el cupo queda libre para que el empleado pueda reservar otro platillo.
- El carrito muestra el contador en tiempo real `(seleccionados / límite máximo)`.
- Validación de NIT: `C/F` o entre 1 y 13 dígitos numéricos.

### Contraseñas y Flujo Automático

- **Restablecimiento automático sin intermediación**:
  - El usuario ingresa su correo en `/account/recuperar-password`.
  - El sistema valida la cuenta y restablece la contraseña automáticamente a: `87654321`.
  - Se almacena con hash seguro bcrypt (`PASSWORD_BCRYPT`).
  - Se envía automáticamente correo SMTP con el diseño oficial del sistema informando la nueva clave temporal.
  - Se activa `debe_cambiar_password = 1` para obligar a cambiarla en el siguiente inicio de sesión.
  - **Manejo seguro de errores**: Si el correo SMTP no puede entregarse, se ejecuta un rollback atómico restaurando el hash anterior sin dejar al usuario bloqueado ni el sistema en estado inconsistente.
- Hash bcrypt con `PASSWORD_BCRYPT` (compatible con ASP.NET Identity PasswordHasher).
- Cambio de contraseña requiere contraseña actual correcta y mínimo 8 caracteres.

### Estado de usuarios (Soft Delete)

- Los usuarios `inactivos` no pueden iniciar sesión.
- Sus registros históricos de reservas se conservan.
- El administrador puede reactivarlos en cualquier momento.

### Stock de platillos

- Al reservar: `stock -= cantidad` y `cantidad_solicitada += cantidad`.
- Al cancelar: `stock += cantidad` y `cantidad_solicitada -= cantidad`.
- Al eliminar un platillo: solo si no tiene reservas asociadas.

---

## 9. Estructura de carpetas

```
RestauranteIntecapWeb_PHP/
│
├── .env                    ← Variables de entorno (NO subir a repositorio)
├── .env.example            ← Plantilla de configuración
├── .htaccess               ← Redirige todo al directorio public/
├── README.md               ← Esta documentación
│
├── config/
│   ├── config.php          ← Arranque: .env, sesión, constantes BASE_URL/ROOT_PATH
│   ├── database.php        ← Singleton PDO (una conexión por request)
│   └── correo.php          ← Configuración SMTP desde variables de entorno
│
├── public/
│   ├── index.php           ← Front controller y carga de dependencias
│   └── Router.php          ← /controlador/accion/param → método PHP (soporta kebab-case)
│
├── controllers/
│   ├── Controller.php          ← Base: render, redirect, json, flash, input, jsonBody
│   ├── AccountController.php   ← Login, logout, restablecimiento automático y cambio de contraseña
│   ├── AdminController.php     ← Dashboard, usuarios, reportes CSV
│   ├── CartaController.php     ← La Carta: panel unificado, selección Sí/No, consolidado, reservas
│   ├── CocinaController.php    ← Menú, platillos, cambio de estado, exportación
│   ├── EmpleadoController.php  ← Reservas (carrito JSON), historial, cancelación
│   └── HomeController.php      ← Redirige a módulo según rol
│
├── models/
│   └── Entidades.php       ← Clases de datos: Rol, Usuario, MenuDiario, CartaProducto, CartaReserva…
│
├── services/
│   ├── Auth.php                ← Autenticación por sesión: login/logout/check/requireRole
│   ├── AuthService.php         ← Validar credenciales, restablecimiento automático (87654321), cambio contraseña
│   ├── AdminService.php        ← CRUD usuarios, roles, reportes CSV
│   ├── CartaService.php        ← CRUD productos La Carta, control días/horarios/stock, reservas y consolidado
│   ├── CocinaService.php       ← CRUD menú, consolidado, reservas del día, CSV
│   ├── EmpleadoService.php     ← Menú disponible, reservas, historial, estadísticas KPI
│   ├── CorreoService.php       ← Notificaciones SMTP con plantillas
│   ├── SmtpMailer.php           ← Cliente SMTP nativo sin dependencias externas
│   ├── ExcelWriter.php          ← Generador de reportes XLSX sin dependencias externas
│   ├── PdfWriter.php            ← Generador de reportes PDF sin dependencias externas
│
├── views/
│   ├── layout.php              ← HTML base: navbar dinámico por rol (incluye pestaña "La Carta")
│   ├── account/
│   │   ├── login.php               ← Pantalla de login con imagen de fondo INTECAP
│   │   ├── recuperar_password.php  ← Formulario de solicitud de restablecimiento automático
│   │   ├── cambiar_password.php    ← Cambiar contraseña propia (autenticado)
│   │   └── acceso_denegado.php
│   ├── admin/
│   │   ├── index.php               ← Dashboard KPIs + filtros avanzados + acceso a La Carta
│   │   ├── usuarios.php            ← Lista + toggle AJAX + modal crear/editar
│   │   └── detalle_usuario.php     ← Ficha completa + historial del usuario
│   ├── carta/
│   │   ├── index.php               ← Vista cliente: opciones con botones Sí (✓) / No (✗), stock y total en vivo
│   │   └── admin.php               ← Panel de administración: catálogo unificado, habilitación, consolidado y reservas
│   ├── cocina/
│   │   └── index.php               ← 3 pestañas: Menú / Consolidado / Detalle reservas
│   ├── empleado/
│   │   ├── index.php               ← Tarjetas de platillos + carrito lateral + modal límite
│   │   └── historial.php           ← Tabla completa + cancelación + descarga CSV
│   └── home/
│       └── error.php
│
├── database/
│   ├── schema.sql          ← Estructura completa MySQL (tablas, índices, FK, datos base)
│   └── seed_admin.php      ← Crea el primer administrador con hash bcrypt correcto
│
└── public/                 ← DocumentRoot de Apache
    ├── index.php           ← Front controller (carga todo y despacha el router)
    ├── .htaccess           ← Rewrite: archivos físicos pasan directo, resto → index.php
    ├── css/
    │   └── site.css        ← Estilos personalizados (Bootstrap 5 via CDN + sobrescrituras)
    ├── js/
    │   └── site.js         ← JS global: cierre de alertas, confirmaciones, tooltips
    └── images/
        ├── logo_intecap/   ← Logos del sistema
        └── menus/          ← Imágenes de platillos subidas por Cocina
```

---

## 10. Guía de mantenimiento

### Agregar un controller nuevo

1. Crear `controllers/MiModuloController.php`
2. Extender `Controller`
3. Agregar protección de rol con `Auth::requireRole([...])`
4. Las acciones se exponen automáticamente como `/mi-modulo/nombre-accion`

```php
<?php
declare(strict_types=1);

class MiModuloController extends Controller
{
    public function __construct()
    {
        Auth::requireRole(['Administrador']);
    }

    public function index(): void
    {
        $this->render('mi_modulo/index', ['datos' => $datos]);
    }
}
```

### Agregar una vista nueva

1. Crear `views/mi_modulo/index.php`
2. Las vistas reciben variables via `extract($data)` desde el controller
3. Usar `<?= BASE_URL ?>` para rutas absolutas

### Agregar una consulta nueva a un servicio

```php
public function miNuevaConsulta(int $id): array
{
    $stmt = $this->db->prepare(
        'SELECT * FROM mi_tabla WHERE id = :id AND activo = 1'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}
```

### Modificar el CSS

Editar `public/css/site.css`. Bootstrap 5.3 se carga desde CDN y puede sobreescribirse aquí.

### Modificar el JavaScript global

Editar `public/js/site.js`. jQuery 3.7 y Bootstrap JS se cargan desde CDN.

### Modificar la b|||              ase de datos

1. Ejecutar el ALTER TABLE o CREATE TABLE en phpMyAdmin
2. Actualizar la clase correspondiente en `models/Entidades.php`
3. Actualizar las consultas en el servicio correspondiente

### Variables de entorno

Todas las credenciales y configuraciones sensibles deben estar en `.env` (nunca en el código). El archivo `.env.example` contiene todas las variables disponibles con valores de ejemplo.

---

## 11. Dependencias y versiones

### PHP nativo (sin Composer)

Este proyecto **no usa Composer**. Toda la funcionalidad está implementada con PHP puro y PDO.

| Funcionalidad | Solución |
|--------------|----------|
| Base de datos | PDO + pdo_mysql |
| Autenticación | Sesiones nativas PHP |
| Hash contraseñas | `password_hash()` / `password_verify()` con `xaPASSWORD_BCRYPT` |
| Envío de correo | SmtpMailer propio (sockets PHP) |
| Exportación de datos | `fputcsv()` nativo PHP |
| Enrutamiento | Router propio (kebab-case → camelCase) |
| Flash messages | `$_SESSION['flash']` + `flash_get_and_clear()` |

### CDN (cargadas desde internet al abrir el navegador)

| Librería | Versión | Uso |
|----------|---------|-----|
| Bootstrap CSS | 5.3.3 | Grid, componentes, modales, tabs |
| Bootstrap Icons | 1.11.1 | Iconografía |
| Bootstrap JS | 5.3.3 | Modales, collapses, tabs interactivos |
| jQuery | 3.7.1 | AJAX en administración (toggle usuarios, cocina) |

> El sistema requiere conexión a internet para cargar estas librerías. Para uso sin internet, descarga y sirve localmente desde `public/`.

---

## 12. Separación física del proyecto

El proyecto PHP es completamente independiente. Para moverlo a cualquier ubicación:

1. Copia toda la carpeta `RestauranteIntecapWeb_PHP/` al destino deseado
2. Actualiza `.env` con los datos de conexión del nuevo entorno
3. El proyecto funciona sin necesidad de ningún otro proyecto, carpeta o archivo externo

**Ejemplo de separación:**

```
C:\xampp\htdocs\sistema_csharp\    ← Proyecto C# original (para Visual Studio)
C:\xampp\htdocs\sistema_php\       ← Proyecto PHP independiente
```

Después de moverlo:
- `http://localhost/sistema_php/` → funciona sin cambios adicionales
- No hay ningún `require`, `include` ni referencia a rutas del proyecto C#

---

## 13. Matriz de paridad funcional

Comparación entre el sistema original C# (ASP.NET Core MVC + SQL Server) y esta implementación PHP.

| Funcionalidad | C# | PHP | Estado |
|---|---|---|---|
| Login con correo y contraseña | ✅ | ✅ | **COMPLETO** |
| Logout y destrucción de sesión | ✅ | ✅ | **COMPLETO** |
| "Recordarme" (cookie persistente 30 días) | ✅ | ✅ | **COMPLETO** |
| Redirección por rol al iniciar sesión | ✅ | ✅ | **COMPLETO** |
| Pantalla de acceso denegado | ✅ | ✅ | **COMPLETO** |
| Hash bcrypt con migración progresiva | ✅ | ✅ | **COMPLETO** |
| Solicitar restablecimiento de contraseña | ✅ | ✅ | **COMPLETO** |
| Cambiar contraseña (usuario autenticado) | ✅ | ✅ | **COMPLETO** |
| Registro de historial_login por sesión | ✅ | ✅ | **COMPLETO** |
| Restablecimiento automático de contraseña (87654321 + SMTP + Rollback) | N/A (Manual en C#) | ✅ | **OPTIMIZADO** |
| Módulo La Carta: panel unificado con todas las categorías juntas | N/A | ✅ | **NUEVO** |
| Módulo La Carta: control de stock y habilitación por horario y días | N/A | ✅ | **NUEVO** |
| Módulo La Carta: recuento consolidado y detalle de reservas | N/A | ✅ | **NUEVO** |
| Módulo La Carta: reserva directa desde admin y vista cliente | N/A | ✅ | **NUEVO** |
| Módulo La Carta: selección interactiva Sí (✓) / No (✗) con stock visible | N/A | ✅ | **NUEVO** |
| Módulo La Carta: regla máx. 1 por categoría y total automático | N/A | ✅ | **NUEVO** |
| Módulo La Carta: ocultamiento automático al agotarse stock o por horario | N/A | ✅ | **NUEVO** |
| Lista de usuarios con búsqueda por correo | ✅ | ✅ | **COMPLETO** |
| Crear usuario (contraseña inicial 12345678) | ✅ | ✅ | **COMPLETO** |
| Editar usuario (modal, sin cambiar contraseña) | ✅ | ✅ | **COMPLETO** |
| Toggle activar/desactivar usuario via AJAX | ✅ | ✅ | **COMPLETO** |
| Ficha detallada de usuario + historial completo | ✅ | ✅ | **COMPLETO** |
| Pestaña Gestión de Menú en Cocina | ✅ | ✅ | **COMPLETO** |
| Formulario lateral para nuevo platillo | ✅ | ✅ | **COMPLETO** |
| Modal de edición de platillo existente | ✅ | ✅ | **COMPLETO** |
| Subida de imagen del platillo | ✅ | ✅ | **COMPLETO** |
| Hora de habilitación programada | ✅ | ✅ | **COMPLETO** |
| Toggle Disponible / Inactivo en platillo | ✅ | ✅ | **COMPLETO** |
| Eliminar platillo (solo sin reservas) | ✅ | ✅ | **COMPLETO** |
| Pestaña Consolidado (tarjetas por platillo) | ✅ | ✅ | **COMPLETO** |
| Pestaña Detalle de Reservas del día | ✅ | ✅ | **COMPLETO** |
| Exportar reporte de cocina (CSV / Excel) | ✅ Excel+PDF | ✅ CSV | **COMPLETO** |
| Menú disponible para empleados (stock+estado+hora) | ✅ | ✅ | **COMPLETO** |
| Carrito de reservas con contador límite | ✅ | ✅ | **COMPLETO** |
| Botones +/- de cantidad por platillo | ✅ | ✅ | **COMPLETO** |
| Modal de límite de almuerzos alcanzado | ✅ | ✅ | **COMPLETO** |
| Validación de NIT (C/F o 1-13 dígitos) | ✅ | ✅ | **COMPLETO** |
| NIT pre-cargado desde el perfil del usuario | ✅ | ✅ | **COMPLETO** |
| Forma de pago individual por platillo | ✅ | ✅ | **COMPLETO** |
| Donde consume (En restaurante / Para llevar) | ✅ | ✅ | **COMPLETO** |
| Límite diario dinámico por rol | ✅ | ✅ | **COMPLETO** |
| Historial de reservas con imagen del platillo | ✅ | ✅ | **COMPLETO** |
| Historial con hora de solicitud y donde consume | ✅ | ✅ | **COMPLETO** |
| Historial con NIT de facturación | ✅ | ✅ | **COMPLETO** |
| Filtro de historial por rango de fechas | ✅ | ✅ | **COMPLETO** |
| Cancelar reserva activa con devolución de stock | ✅ servicio | ✅ vista+servicio | **COMPLETO** |
| Exportar historial personal (CSV / Excel) | ✅ Excel+PDF | ✅ CSV | **COMPLETO** |
| Protección de rutas por rol | ✅ Claims | ✅ Auth::requireRole | **COMPLETO** |
| Flash messages (TempData equivalente) | ✅ TempData | ✅ $_SESSION flash | **COMPLETO** |
| Validaciones del lado servidor en controllers | ✅ | ✅ | **COMPLETO** |
| Validaciones del lado cliente (JS) | ✅ | ✅ | **COMPLETO** |
| Navbar dinámico por rol con badge pendientes | ✅ | ✅ | **COMPLETO** |
| Soft delete de usuarios (activo/inactivo) | ✅ | ✅ | **COMPLETO** |
| Relación entre límite de almuerzos y rol | ✅ | ✅ | **COMPLETO** |
| Registro de auditoría de logins | ✅ | ✅ | **COMPLETO** |
| Desacoplamiento completo (sin dependencias C#) | ✅ | ✅ | **COMPLETO** |

| Pestañas de navegación de alto contraste y relieve (Cocina y La Carta) | N/A | ✅ | **NUEVO** |
| Diseño responsivo total para móviles (smartphones) y computadoras | Parcial | ✅ | **OPTIMIZADO** |
| Barra flotante móvil para La Carta con total en tiempo real y salto al resumen | N/A | ✅ | **NUEVO** |
| Menú desplegable móvil optimizado como panel táctil | Básico | ✅ | **OPTIMIZADO** |

**Diferencias intencionales (no faltantes):**

| Aspecto | C# | PHP | Motivo |
|---------|-----|-----|--------|
| Exportación Excel/PDF | ClosedXML + QuestPDF | ExcelWriter nativo (.xlsx) + PdfWriter (.pdf) | Compatibilidad total y descarga directa |
| Motor de base de datos | SQL Server | MySQL | Requerimiento del proyecto PHP |
| Autenticación | ASP.NET Identity Cookies | Sesiones PHP nativas | Equivalente funcional |
| Hash de contraseñas | PasswordHasher (PBKDF2) | password_hash() (bcrypt) | Igualmente seguro |

---

## 14. Novedades de Diseño: Pestañas Marcadas y Adaptación Móvil / Escritorio

### 🌟 Pestañas de Navegación Marcadas ("Alto Contraste y Relieve")
1. **Contenedor Tray**: Fondo slate suave (`#e9eef5`), borde perimetral (`#cbd5e1`), esquinas curvas de 14px y espaciado de 8px entre pestañas.
2. **Pestaña Activa**: Destaca de forma rotunda e inconfundible con el degradado oficial INTECAP (`linear-gradient(135deg, #123d6b 0%, #0d6efd 100%)`), texto blanco puro, tipografía negrita (`font-weight: 700`), elevación suave y sombra tridimensional (`box-shadow: 0 4px 14px rgba(13, 110, 253, 0.35)`).
3. **Pestañas Inactivas**: Texto pizarra oscuro de alta legibilidad (`#334155`), fondo blanco limpio con animación al pasar el cursor (hover).
4. **Contadores e Insignias (`tab-badge`)**: Integrados armoniosamente con fondos translúcidos en tabs activos y contraste sólido en tabs inactivos.

### 📱 Experiencia Responsive Multiplataforma (Móvil + Computadora)
1. **Pestañas en Smartphones (`<= 768px`)**: En teléfonos móviles, las pestañas cambian automáticamente a disposición vertical de 100% de ancho, con altura táctil mínima de 48px para facilitar la interacción con los dedos sin textos comprimidos.
2. **Navbar Móvil (Drawer Táctil)**: En pantallas pequeñas (`< 992px`), el menú colapsable se despliega como un panel flotante azul noche (`#10335a`), con botones alargados de fácil toque y perfil de usuario integrado.
3. **Barra Flotante Fija en La Carta**: En smartphones, los comensales disponen de una barra fija inferior con efecto translúcido que muestra el total acumulado en tiempo real y el botón `🍽️ Ver Resumen` con desplazamiento suave hacia la confirmación.
4. **Tablas y Formularios**: Desplazamiento táctil fluido `-webkit-overflow-scrolling: touch`, modales centrados a pantalla completa móvil y botones de acción con dimensiones ergonómicas mínimas.

---

## 15. Mejora Integral de Visibilidad, Límites y Organización Visual en Todo el Sistema

Se llevó a cabo una renovación visual integral en todas las pantallas y componentes del sistema para erradicar elementos desvanecidos, límites ambiguos o información amontonada:

### 1. Límites Claramente Visibles en Ventanas, Tarjetas y Módulos
- **Bordes Perimetrales Reforzados**: En `site.css`, la variable `--intecap-border` se fijó en `#cbd5e1` (pizarra visible). Se eliminaron clases `border-0` que desdibujaban componentes, dotando a las tarjetas (`.card`), modales y contenedores de un borde perimetral firme de `1.5px solid #cbd5e1 !important` complementado con sombra tridimensional suave (`box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08)`).
- **Acentos Institucionales en Cabeceras**: Los paneles y módulos principales ahora lucen una franja izquierda sólida (`border-left: 5px solid var(--intecap-primary)`), enmarcando de manera inconfundible la temática de cada sección.
- **Modales Estructurados**: Las ventanas modales cuentan con bordes firmes de `2px solid #94a3b8`, cabeceras en degradado azul marino y pie de página separado con fondo tenue y borde superior visible.

### 2. Separación Visual entre Pestañas, Formularios, Filtros y Secciones
- **Aislamiento de Formularios y Filtros**:
  - El buscador de usuarios (`/admin/usuarios`) y los filtros por rango de fechas del dashboard (`/admin/index`) fueron extraídos de espacios planos e integrados dentro de tarjetas dedicadas con cabecera, ícono y bordes nítidos.
  - El formulario de registro de platillos en Cocina y el formulario de cambio de contraseña (`/account/cambiar-password`) ahora cuentan con una estructura encuadrada que los separa claramente de las tablas adyacentes.
- **Campos de Entrada y Selectores Notables**: Inputs y selects utilizan bordes de `1.5px solid #cbd5e1`, esquinas de 8px, etiquetas en negrita azul institucional (`#123d6b`) y un halo azul al enfocar (`box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.18)`).

### 3. Botones Fáciles de Identificar, Diferenciar y con Jerarquía Clara
- **Eliminación de Íconos Ambiguos en Tablas**: En las tablas de Cocina y Administración, los botones que sólo mostraban emojis pequeños fueron reemplazados por botones estilizados con texto y color funcional explícito:
  - `✏️ Editar` (Celeste / Información)
  - `🚫 Pausar` / `✅ Activar` (Ámbar Advertencia / Verde Éxito)
  - `🗑️ Borrar` (Rojo Rubí Peligro)
- **Paleta de Botones con Alto Contraste**:
  - **Primario (`.btn-primary`)**: Azul INTECAP (`#123d6b`) para acciones de confirmación y guardado.
  - **Éxito (`.btn-success`)**: Verde esmeralda (`#198754`) para descargas Excel y activación.
  - **Peligro (`.btn-danger`)**: Rojo rubí (`#dc3545`) para cancelaciones y eliminaciones.
  - **Advertencia (`.btn-warning`)**: Ámbar dorado (`#d97706`) con texto blanco para pausas y precauciones.
  - **Efecto de Micro-Elevación**: Transición suave con elevación de 1px al pasar el cursor y feedback táctil inmediato.

### 4. Distribución Amplia y sin Amontonamientos (Espaciado y Tipografía)
- **Tablas Espaciosas y Legibles**:
  - Celdas con acolchado ampliado a `0.95rem 1rem !important`.
  - Encabezados de tabla (`<thead>`) con fondo pizarra suave (`#f1f5f9`), borde inferior de `2px solid #cbd5e1`, tipografía en mayúsculas compactas de alto contraste y esquinas superiores redondeadas.
  - Filas con separación sutil y efecto hover iluminado en azul tenue (`#f1f7ff`).
- **Acentos Cromáticos por Categoría en La Carta**:
  - Las secciones de Entrada, Plato fuerte, Bebida y Postre poseen un borde superior de 4px con su color temático correspondiente (Ámbar, Esmeralda, Cian, Magenta) e insignias destacadas, permitiendo al usuario identificar cada tiempo de comida de un vistazo.
- **Tarjetas de Métricas del Dashboard Renovadas**:
  - Se sustituyeron fondos de colores pasteles planos por tarjetas blancas con acento lateral de color, badges institucionales, números grandes y legibles, y círculos para íconos que previenen la fatiga visual.

### 5. Resumen de Pantallas Intervenidas
1. `views/admin/index.php`: Métricas KPI con bordes y acentos laterales, formulario de filtros enmarcado.
2. `views/admin/usuarios.php`: Barra de búsqueda dentro de tarjeta de filtro, encabezado temático de padrón.
3. `views/cocina/index.php`: Pestañas enmarcadas, botones de acción en tabla con texto descriptivo, formulario aislado.
4. `views/carta/admin.php`: Categorías con acento superior de color, tablas de recuento y catálogo delimitadas.
5. `views/carta/index.php`: Separadores por categoría de comida, panel de resumen lateral contrastado.
6. `views/empleado/index.php`: Tarjetas de platillos con bordes marcados y carrito lateral enmarcado.
7. `views/empleado/historial.php`: Cabecera descriptiva, tabla de reservas holgada y badge de estado nítidos.
8. `views/account/cambiar_password.php`: Formulario central enmarcado en tarjeta con cabecera azul institucional.
9. `views/layout.php` & `public/css/site.css`: Reglas globales de bordes, sombras, botones, tablas y modales.

---

## 16. Agrupación de Usuarios por Rol e Integración Limpia de Teléfono

A petición de las directrices institucionales, se perfeccionó la administración del padrón de usuarios:

### 1. Usuarios Agrupados por Rol (Sin Columnas Innecesarias)
- **Orden Agrupado en Base de Datos y Vistas**: Los usuarios ya no aparecen revueltos alfabéticamente; ahora se ordenan y agrupan estrictamente por su rol institucional:
  1. 🛡️ **Administradores** (todos juntos al inicio)
  2. 👨‍🍳 **Personal de Cocina** (todos juntos a continuación)
  3. 👤 **Empleados** (todos juntos en su propio bloque)
- **Separadores Visuales de Rol en Tabla**: En `views/admin/usuarios.php`, la tabla incluye cabeceras divisorias de grupo con acento cromático e indicación del número de integrantes de cada rol, manteniendo una tabla limpia sin agregar columnas redundantes.

### 2. Integración Limpia del Teléfono (Formato Idéntico a Correo)
- **Columna Teléfono**: Agregada inmediatamente después del correo electrónico en la tabla del padrón.
- **Presentación Sobria y Legible ("Sin tanta cosa")**: El número de teléfono se muestra como texto limpio y directo (ej. `5555-1234`), en perfecta simetría y armonía con la columna de correo electrónico.
- **Formularios de Creación y Edición**: Campo de texto normalizado en el modal de usuarios (`#user_telefono`), con persistencia directa en MySQL.
- **Ficha de Detalle y Exportaciones**: El teléfono está disponible en la ficha completa del usuario (`/admin/detalle-usuario/{id}`) y en los reportes descargables de Excel (.xlsx), PDF (.pdf) y CSV.

---

*Documentación generada — Proyecto Restaurante Escuela INTECAP · PHP 8.2 MVC · Completamente independiente y adaptado a dispositivos móviles y de escritorio*
