<?php
declare(strict_types=1);
/**
 * Front controller publico de la aplicacion.
 *
 * Carga la configuracion, las dependencias y entrega la solicitud al Router.
 * Apache redirige aqui las rutas que no corresponden a archivos estaticos.
 */

// La configuración debe cargarse primero: define ROOT_PATH, BASE_URL, sesión y DB_*.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Componentes transversales usados por controladores, vistas y servicios.
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../controllers/Controller.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/../services/SmtpMailer.php';
require_once __DIR__ . '/../services/ExcelWriter.php';
require_once __DIR__ . '/../services/PdfWriter.php';
// Contratos y escritores de reportes; se cargan antes de los controladores.
require_once __DIR__ . '/../models/PdfReport.php';
require_once __DIR__ . '/../services/IPdfService.php';
require_once __DIR__ . '/../services/PdfService.php';

// Modelos de dominio y DTOs.
require_once ROOT_PATH . '/models/Entidades.php';

// Servicios de aplicación: contienen las reglas de negocio y acceso PDO.
require_once ROOT_PATH . '/services/CorreoService.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/services/AdminService.php';
require_once ROOT_PATH . '/services/CocinaService.php';
require_once ROOT_PATH . '/services/EmpleadoService.php';

// El router carga los demás controladores bajo demanda; Home se deja disponible
// para resolver la ruta raíz sin depender del orden de las URL.
require_once ROOT_PATH . '/controllers/HomeController.php';

(new Router())->dispatch();
