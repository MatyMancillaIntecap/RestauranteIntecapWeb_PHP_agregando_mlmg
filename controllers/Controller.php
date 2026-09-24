<?php
declare(strict_types=1);
/**
 * Controlador base del MVC.
 *
 * Centraliza renderizado de vistas, respuestas JSON, redirecciones, lectura de
 * entradas y mensajes flash para que los controladores concretos sean pequenos.
 */

abstract class Controller
{
    /** Renderiza una vista con datos y, opcionalmente, el layout comun. */
    protected function render(string $view, array $data = [], bool $useLayout = true): void
    {
        extract($data);
        $viewFile = ROOT_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        
        if (!$useLayout) {
            require $viewFile;
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require ROOT_PATH . '/views/layout.php';
    }

    /** Envia una respuesta JSON y termina la solicitud. */
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Redirige a una ruta interna relativa a BASE_URL. */
    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    /** Lee un parametro priorizando POST sobre GET. */
    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** Decodifica el cuerpo JSON y devuelve un arreglo seguro. */
    protected function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** Guarda un mensaje temporal para mostrarlo en la siguiente vista. */
    protected function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][$tipo] = $mensaje;
    }

    /** Consume y devuelve los mensajes flash de la sesion. */
    protected function getFlashes(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}

/** Devuelve y limpia los mensajes flash para las vistas. */
function flash_get_and_clear(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/** Devuelve los mensajes flash sin consumirlos. */
function flash_peek(): array
{
    return $_SESSION['flash'] ?? [];
}

/** Convierte una ruta de imagen relativa en una URL utilizable por el navegador. */
function resolve_image_url(?string $url): string
{
    if ($url === null || trim($url) === '') {
        return '';
    }

    $url = trim($url);

    if (preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }

    if (str_starts_with($url, '//')) {
        return 'https:' . $url;
    }

    $base = rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/');

    if ($base !== '' && str_starts_with($url, $base)) {
        return $url;
    }

    if (str_starts_with($url, '/')) {
        return $base . $url;
    }

    return $base . '/' . ltrim($url, '/');
}
