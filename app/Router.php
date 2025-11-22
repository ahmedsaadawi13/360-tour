<?php
/**
 * Simple Router Class
 *
 * Handles routing for the application based on query string or URI parsing
 */

class Router
{
    private $routes = [];
    private $middlewares = [];

    /**
     * Add GET route
     */
    public function get($route, $controller, $action)
    {
        $this->addRoute('GET', $route, $controller, $action);
    }

    /**
     * Add POST route
     */
    public function post($route, $controller, $action)
    {
        $this->addRoute('POST', $route, $controller, $action);
    }

    /**
     * Add route for any method
     */
    public function any($route, $controller, $action)
    {
        $this->addRoute('ANY', $route, $controller, $action);
    }

    /**
     * Add middleware
     */
    public function middleware($name, $callback)
    {
        $this->middlewares[$name] = $callback;
    }

    /**
     * Internal method to add route
     */
    private function addRoute($method, $route, $controller, $action)
    {
        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * Dispatch request
     */
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $this->getRequestUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->convertRouteToRegex($route['route']);
            if (preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches); // Remove full match
                $this->callController($route['controller'], $route['action'], $matches);
                return;
            }
        }

        // No route found
        http_response_code(404);
        view('errors.404');
    }

    /**
     * Get request URI
     */
    private function getRequestUri()
    {
        // Support both query string routing (?route=) and URI routing
        if (isset($_GET['route'])) {
            return '/' . trim($_GET['route'], '/');
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $uri;
    }

    /**
     * Convert route pattern to regex
     */
    private function convertRouteToRegex($route)
    {
        // Convert :param to regex capture group
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    /**
     * Call controller action
     */
    private function callController($controllerName, $action, $params = [])
    {
        $controllerFile = __DIR__ . '/Controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            die("Controller not found: {$controllerName}");
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            die("Controller class not found: {$controllerName}");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            die("Action not found: {$controllerName}@{$action}");
        }

        call_user_func_array([$controller, $action], $params);
    }
}
