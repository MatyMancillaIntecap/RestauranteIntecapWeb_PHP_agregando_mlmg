<?php
declare(strict_types=1);
/**
 * Router frontal del MVC.
 *
 * Traduce una URL /controlador/accion/parametros a una clase y metodo PHP,
 * respetando la carpeta publica donde Apache ejecuto el front controller.
 */

// Router mínimo: traduce /controlador/accion/param en una invocación de método PHP
class Router
{
    /** Analiza la URL, carga el controlador y ejecuta la accion solicitada. */
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $appDir = dirname($scriptDir);
        if ($appDir === '.' || $appDir === '\\') {
            $appDir = '/';
        }

        if ($appDir !== '/' && str_starts_with($uri, $appDir)) {
            $uri = substr($uri, strlen($appDir));
        }

        $uri = trim($uri, '/');
        $segments = $uri === '' ? [] : explode('/', $uri);

        // Ruta raíz: redirigir al HomeController que decide según sesión
        $controllerSegment = $segments[0] ?? 'home';
        $actionSegment     = $segments[1] ?? 'index';
        $params = array_slice($segments, 2);

        $controllerClass = ucfirst($this->toCamelCase($controllerSegment)) . 'Controller';
        $actionMethod = $this->toCamelCase($actionSegment);

        $controllerFile = ROOT_PATH . '/controllers/' . $controllerClass . '.php';

        if (!file_exists($controllerFile)) {
            http_response_code(404);
            echo "Controlador no encontrado: {$controllerClass}";
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerClass)) {
            http_response_code(404);
            echo "Clase de controlador no encontrada: {$controllerClass}";
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionMethod)) {
            http_response_code(404);
            echo "Acción no encontrada: {$actionMethod}";
            return;
        }

        call_user_func_array([$controller, $actionMethod], $params);
    }

    /** Convierte segmentos kebab-case o snake_case a camelCase. */
    private function toCamelCase(string $segment): string
    {
        $segment = str_replace(['-', '_'], ' ', $segment);
        $segment = ucwords($segment);
        return lcfirst(str_replace(' ', '', $segment));
    }
}
