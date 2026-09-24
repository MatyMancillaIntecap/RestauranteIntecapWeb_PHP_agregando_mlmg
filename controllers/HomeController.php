<?php
/**
 * Controlador de entrada general.
 *
 * Decide si la raiz debe mostrar login o redirigir al modulo inicial del rol
 * autenticado y ofrece paginas auxiliares de error y privacidad.
 */
declare(strict_types=1);

// Redirige a la ruta correspondiente según el estado de autenticación.
class HomeController extends Controller
{
    // GET /home/index  (o simplemente /)
    /** Redirige al modulo correspondiente o al login. */
    public function index(): void
    {
        if (Auth::check()) {
            // Usuario autenticado: redirigir según su rol
            switch (Auth::role()) {
                case 'Administrador':
                    $this->redirect('admin/index');
                    break;
                case 'Cocina':
                    $this->redirect('cocina/index');
                    break;
                default:
                    $this->redirect('empleado/index');
            }
        } else {
            // Sin sesión: ir al login
            $this->redirect('account/login');
        }
    }

    // GET /home/error  — página de error genérica
    /** Renderiza la pagina generica de error HTTP 500. */
    public function error(): void
    {
        http_response_code(500);
        $this->render('home/error', ['mensaje' => 'Ha ocurrido un error inesperado.']);
    }

    // GET /home/privacidad
    /** Renderiza la pagina informativa de privacidad. */
    public function privacidad(): void
    {
        $this->render('home/privacidad');
    }
}
