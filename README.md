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
| `menu_diario` | Platillos con stock, precio, imagen y hora de habilitación |
| `reservas` | Reservas activas y canceladas con NIT de facturación |
| `historial_login` | Auditoría de cada inicio de sesión |
| `solicitudes_restablecimiento_password` | Solicitudes de cambio de contraseña |

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
| **Administrador** | Control total del sistema | Dashboard, usuarios, cocina, reservas, reportes, solicitudes |
| **Cocina** | Gestión del menú del día | Publicar platillos, ver reservas, reportes de cocina, hacer reserva propia |
| **Empleado** | Usuario estándar | Reservar almuerzos, ver historial propio |

Correo: admin@intecap.edu.gt
Contraseña: 12345678

Cocina:
Correo: cocina@intecap.edu.gt
Contraseña: 12345678

Empleado:
Correo: empleado@intecap.edu.gt
Contraseña: 12345678



### Contraseñas del sistema

| Situación | Contraseña |
|-----------|-----------|
| Nuevo usuario creado por admin | `12345678` |
| Contraseña restablecida por admin | `87654321` |
| Mínimo para cambio manual | 8 caracteres |

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
| `GET/POST /account/recuperar-password` | Envía solicitud de restablecimiento al administrador |
| `GET/POST /account/cambiar-password` | Cambia la contraseña del usuario autenticado |
| `GET /account/acceso-denegado` | Pantalla de acceso denegado por rol insuficiente |

### 👨‍💼 Administración (`/admin/`)

| Ruta | Descripción |
|------|-------------|
| `GET /admin/index` | Dashboard con KPIs: ventas, reservas, platillos dieta/normal, usuarios. Filtrable por rango de fechas |
| `GET /admin/usuarios` | Lista completa de usuarios. Busqueda por correo. Toggle de activar/desactivar via AJAX |
| `GET /admin/detalle-usuario/{id}` | Ficha completa: datos del usuario + historial de reservas + totales acumulados |
| `GET /admin/obtener-usuario-por-id/{id}` | JSON para cargar datos en el modal de edición |
| `POST /admin/guardar-usuario` | Crear o editar usuario con validaciones del lado servidor |
| `POST /admin/cambiar-estado-usuario` | Activar o desactivar usuario via AJAX (sin recarga de página) |
| `GET /admin/solicitudes-restablecimiento` | Lista con secciones Pendientes / Realizadas |
| `POST /admin/atender-solicitud-restablecimiento` | Confirmar restablecimiento via modal. Asigna `87654321` como temporal |
| `GET /admin/descargar-reporte-csv` | CSV global de reservas. Filtros: fechas, estado (Activa/Cancelada/Todos) |
| `GET /admin/descargar-usuarios-csv` | Padrón completo de usuarios en CSV |

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

### Reservas

- Solo se muestran platillos con estado `Disponible`, `stock > 0` y `hora_habilitacion <= NOW()`
- El límite diario se calcula contando reservas **Activas** de platillos **Disponibles**
- Si un platillo pasa a `Inactivo`, sus reservas se conservan como historial pero el cupo queda libre para que el empleado pueda reservar otro platillo
- El carrito muestra el contador en tiempo real `(seleccionados / límite máximo)`
- Validación de NIT: `C/F` o entre 1 y 13 dígitos numéricos

### Contraseñas

- Hash bcrypt con `PASSWORD_BCRYPT` (equivalente a ASP.NET Identity PasswordHasher)
- Compatibilidad con contraseñas en texto plano heredadas: se validan y migran automáticamente a bcrypt al primer login
- Cambio de contraseña requiere contraseña actual correcta y mínimo 8 caracteres
- Las contraseñas nuevas deben coincidir en el campo de confirmación

### Estado de usuarios (Soft Delete)

- Los usuarios `inactivos` no pueden iniciar sesión
- Sus registros históricos de reservas se conservan
- El administrador puede reactivarlos en cualquier momento

### Stock de platillos

- Al reservar: `stock -= cantidad` y `cantidad_solicitada += cantidad`
- Al cancelar: `stock += cantidad` y `cantidad_solicitada -= cantidad`
- Al eliminar un platillo: solo si no tiene reservas asociadas

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
│   ├── AccountController.php   ← Login, logout, recuperar/cambiar contraseña
│   ├── AdminController.php     ← Dashboard, usuarios, solicitudes, reportes CSV
│   ├── CocinaController.php    ← Menú, platillos, cambio de estado, exportación
│   ├── EmpleadoController.php  ← Reservas (carrito JSON), historial, cancelación
│   └── HomeController.php      ← Redirige a módulo según rol
│
├── models/
│   └── Entidades.php       ← Clases de datos: Rol, Usuario, MenuDiario, Reserva…
│
├── services/
│   ├── Auth.php                ← Autenticación por sesión: login/logout/check/requireRole
│   ├── AuthService.php         ← Validar credenciales, solicitudes, cambio de contraseña
│   ├── AdminService.php        ← CRUD usuarios, roles, solicitudes, reportes CSV
│   ├── CocinaService.php       ← CRUD menú, consolidado, reservas del día, CSV
│   ├── EmpleadoService.php     ← Menú disponible, reservas, historial, estadísticas KPI
│   ├── CorreoService.php       ← Notificaciones SMTP
│   ├── SmtpMailer.php           ← Cliente SMTP nativo sin dependencias externas
│   ├── ExcelWriter.php          ← Generador de reportes XLSX sin dependencias externas
│   ├── PdfWriter.php            ← Generador de reportes PDF sin dependencias externas
│
├── views/
│   ├── layout.php              ← HTML base: navbar dinámico por rol, flash messages
│   ├── account/
│   │   ├── login.php               ← Pantalla de login con imagen de fondo INTECAP
│   │   ├── recuperar_password.php  ← Formulario de solicitud de restablecimiento
│   │   ├── cambiar_password.php    ← Cambiar contraseña propia (autenticado)
│   │   └── acceso_denegado.php
│   ├── admin/
│   │   ├── index.php               ← Dashboard KPIs + filtros avanzados + exportación CSV
│   │   ├── usuarios.php            ← Lista + toggle AJAX + modal crear/editar
│   │   ├── detalle_usuario.php     ← Ficha completa + historial del usuario
│   │   └── solicitudes_restablecimiento.php  ← Secciones Pendientes/Realizadas + modal
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

### Modificar la base de datos

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
| Hash contraseñas | `password_hash()` / `password_verify()` con `PASSWORD_BCRYPT` |
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
| Dashboard Admin con KPIs por rango fechas | ✅ | ✅ | **COMPLETO** |
| KPIs: ventas, reservas, platillos dieta/normal | ✅ | ✅ | **COMPLETO** |
| Contador de solicitudes pendientes en navbar | ✅ | ✅ | **COMPLETO** |
| Lista de usuarios con búsqueda por correo | ✅ | ✅ | **COMPLETO** |
| Crear usuario (contraseña inicial 12345678) | ✅ | ✅ | **COMPLETO** |
| Editar usuario (modal, sin cambiar contraseña) | ✅ | ✅ | **COMPLETO** |
| Toggle activar/desactivar usuario via AJAX | ✅ | ✅ | **COMPLETO** |
| Ficha detallada de usuario + historial completo | ✅ | ✅ | **COMPLETO** |
| Solicitudes restablecimiento: sección Pendientes | ✅ | ✅ | **COMPLETO** |
| Solicitudes restablecimiento: sección Realizadas | ✅ | ✅ | **COMPLETO** |
| Modal de confirmación para restablecer contraseña | ✅ | ✅ | **COMPLETO** |
| Contraseña temporal fija al restablecer (87654321) | ✅ | ✅ | **COMPLETO** |
| Exportar padrón de usuarios (CSV / Excel) | ✅ CSV | ✅ CSV | **COMPLETO** |
| Reporte global de reservas con filtros | ✅ Excel+PDF | ✅ CSV | **COMPLETO** |
| Filtros de reporte: fechas, estado, usuario, platillo | ✅ | ✅ | **COMPLETO** |
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

**Diferencias intencionales (no faltantes):**

| Aspecto | C# | PHP | Motivo |
|---------|-----|-----|--------|
| Exportación Excel/PDF | ClosedXML + QuestPDF | CSV nativo PHP | Sin dependencias externas en PHP |
| Motor de base de datos | SQL Server | MySQL | Requerimiento del proyecto PHP |
| Autenticación | ASP.NET Identity Cookies | Sesiones PHP nativas | Equivalente funcional |
| Hash de contraseñas | PasswordHasher (PBKDF2) | password_hash() (bcrypt) | Igualmente seguro |

---

*Documentación generada — Proyecto Restaurante Escuela INTECAP · PHP 8.2 MVC · Completamente independiente*
