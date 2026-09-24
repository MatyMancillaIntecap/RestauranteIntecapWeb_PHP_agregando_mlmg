<?php
declare(strict_types=1);
/**
 * Front controller publico de la aplicacion.
 *
 * Carga la configuracion, las dependencias y entrega la solicitud al Router.
 * Apache redirige aqui las rutas que no corresponden a archivos estaticos.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/Auth.php';
require_once __DIR__ . '/../controllers/Controller.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/../services/SmtpMailer.php';
require_once __DIR__ . '/../services/ExcelWriter.php';
require_once __DIR__ . '/../services/PdfWriter.php';
require_once __DIR__ . '/../models/PdfReport.php';
require_once __DIR__ . '/../services/IPdfService.php';
require_once __DIR__ . '/../services/PdfService.php';

// Modelos (entidades de datos)
require_once ROOT_PATH . '/models/Entidades.php';

// Servicios (lógica de negocio + acceso a datos con PDO)
require_once ROOT_PATH . '/services/CorreoService.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/services/AdminService.php';
require_once ROOT_PATH . '/services/CocinaService.php';
require_once ROOT_PATH . '/services/EmpleadoService.php';

// Carga anticipada del HomeController (no depende de servicios)
require_once ROOT_PATH . '/controllers/HomeController.php';

(new Router())->dispatch();
