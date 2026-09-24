<?php
/**
 * Controlador de autenticacion y cuenta.
 *
 * Atiende login, logout, recuperacion y cambio de contrasena, delegando la
 * validacion y persistencia sensible en AuthService.
 */
declare(strict_types=1);

class AccountController extends Controller
{
    private AuthService $authService;

    /** Construye el servicio que contiene la logica de autenticacion. */
    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // GET /account/login
    /** Muestra el login y procesa sus credenciales cuando llegan por POST. */
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirigirSegunRol(Auth::role());
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) $this->input('email', ''));
            $password = (string) $this->input('password', '');
            $recordarme = (bool) $this->input('recordarme', false);

            if ($email === '' || $password === '') {
                $this->render('account/login', ['error' => 'Debe ingresar correo y contraseña.', 'email' => $email], false);
                return;
            }

            [$exito, $mensaje, $usuario] = $this->authService->validarCredenciales($email, $password);

            if (!$exito || $usuario === null) {
                $this->render('account/login', ['error' => $mensaje, 'email' => $email], false);
                return;
            }

            Auth::login([
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol_nombre' => $usuario->rol_nombre,
            ]);

            if ($recordarme) {
                // "Recordarme" -> cookie de sesión persistente de 30 días
                setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/');
            }

            $this->redirigirSegunRol($usuario->rol_nombre);
            return;
        }

        $this->render('account/login', ['error' => null, 'email' => ''], false);
    }

    // GET /account/logout
    /** Destruye la sesion actual y vuelve al formulario de login. */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('account/login');
    }

    // GET /account/acceso-denegado
    /** Muestra la pagina de acceso insuficiente. */
    public function accesoDenegado(): void
    {
        $this->render('account/acceso_denegado');
    }

    /** Envia cada rol a su modulo inicial despues de autenticarse. */
    private function redirigirSegunRol(?string $rol): void
    {
        switch ($rol) {
            case 'Administrador':
                $this->redirect('admin/index');
                break;
            case 'Cocina':
                $this->redirect('cocina/index');
                break;
            default:
                $this->redirect('empleado/index');
        }
    }

    // GET/POST /account/recuperar-password
    /** Crea una solicitud de restablecimiento para un correo registrado. */
    public function recuperarPassword(): void
    {
        $mensaje = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identificador = (string) $this->input('identificador', '');
            [$exito, $texto] = $this->authService->crearSolicitudRestablecimiento($identificador);

            if (!$exito) {
                $error = $texto;
            } else {
                $mensaje = $texto;
            }
        }

        $this->render('account/recuperar_password', ['mensaje' => $mensaje, 'error' => $error], false);
    }

    // GET/POST /account/cambiar-password
    /** Valida y cambia la contrasena del usuario autenticado. */
    public function cambiarPassword(): void
    {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actual = (string) $this->input('contrasena_actual', '');
            $nueva = (string) $this->input('nueva_contrasena', '');
            $confirmar = (string) $this->input('confirmar_contrasena', '');

            [$exito, $mensaje] = $this->authService->cambiarContrasena(Auth::id(), $actual, $nueva, $confirmar);

            if (!$exito) {
                $this->render('account/cambiar_password', ['error' => $mensaje]);
                return;
            }

            $this->flash('exito', $mensaje);
            $this->redirigirSegunRol(Auth::role());
            return;
        }

        $this->render('account/cambiar_password', ['error' => null]);
    }
}
