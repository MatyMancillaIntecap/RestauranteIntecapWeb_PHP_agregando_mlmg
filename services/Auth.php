<?php
declare(strict_types=1);
/**
 * Fachada de autenticacion basada en la sesion PHP.
 *
 * Guarda el usuario autenticado, expone comprobaciones de identidad y protege
 * rutas que requieren una sesion o un rol concreto.
 */

class Auth
{
    /** Inicia sesion para el usuario y regenera el identificador de sesion. */
    public static function login(array $usuario): void
    {
        $_SESSION['auth_user'] = [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol_nombre'],
        ];
        session_regenerate_id(true);
    }

    /** Cierra la sesion y elimina su cookie del navegador. */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /** @return bool True cuando existe un usuario autenticado. */
    public static function check(): bool
    {
        return isset($_SESSION['auth_user']);
    }

    /** @return array|null Datos publicos del usuario autenticado. */
    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    /** @return int Identificador del usuario actual o cero si no hay sesion. */
    public static function id(): int
    {
        return (int) ($_SESSION['auth_user']['id'] ?? 0);
    }

    /** @return string|null Nombre del rol actual. */
    public static function role(): ?string
    {
        return $_SESSION['auth_user']['rol'] ?? null;
    }

    /** Redirige al login cuando la solicitud no tiene una sesion valida. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/account/login');
            exit;
        }
    }

    /**
     * Obliga a que exista sesion y que el rol actual este permitido.
     *
     * @param array<string> $rolesPermitidos Roles que pueden acceder a la ruta.
     */
    public static function requireRole(array $rolesPermitidos): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $rolesPermitidos, true)) {
            header('Location: ' . BASE_URL . '/account/acceso-denegado');
            exit;
        }
    }
}
