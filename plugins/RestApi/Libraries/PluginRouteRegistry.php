<?php

namespace RestApi\Libraries;

use CodeIgniter\Router\DefinedRouteCollector;
use CodeIgniter\Router\RouteCollectionInterface;

class PluginRouteRegistry
{
    protected RouteCollectionInterface $routes;
    protected array $activePlugins;
    protected ?array $pluginRoutes = null;

    public function __construct(RouteCollectionInterface $routes)
    {
        $this->routes = $routes;
        $this->activePlugins = $this->loadActivePlugins();
    }

    public function all(): array
    {
        if ($this->pluginRoutes !== null) {
            return $this->pluginRoutes;
        }

        $collection = $this->routes->loadRoutes();
        $collector = new DefinedRouteCollector($collection);

        $routesByPlugin = [];

        foreach ($collector->collect() as $route) {
            $pluginName = $this->detectPluginName((string) ($route['handler'] ?? ''));
            if (!$pluginName || !in_array($pluginName, $this->activePlugins, true)) {
                continue;
            }

            if (!isset($routesByPlugin[$pluginName])) {
                $routesByPlugin[$pluginName] = [
                    'plugin' => $pluginName,
                    'route_count' => 0,
                    'routes' => [],
                ];
            }

            $routesByPlugin[$pluginName]['routes'][] = [
                'method' => strtoupper((string) ($route['method'] ?? 'GET')),
                'route' => (string) ($route['route'] ?? ''),
                'name' => (string) ($route['name'] ?? ''),
                'handler' => (string) ($route['handler'] ?? ''),
            ];
            $routesByPlugin[$pluginName]['route_count']++;
        }

        ksort($routesByPlugin);
        foreach ($routesByPlugin as &$pluginData) {
            usort($pluginData['routes'], static function (array $left, array $right): int {
                return [$left['route'], $left['method'], $left['handler']] <=> [$right['route'], $right['method'], $right['handler']];
            });
        }
        unset($pluginData);

        $this->pluginRoutes = $routesByPlugin;

        return $this->pluginRoutes;
    }

    public function get(string $plugin): ?array
    {
        $plugin = trim($plugin);
        if ($plugin === '') {
            return null;
        }

        $all = $this->all();
        return $all[$plugin] ?? null;
    }

    protected function loadActivePlugins(): array
    {
        $file = APPPATH . 'Config/activated_plugins.json';
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $decoded)));
    }

    protected function detectPluginName(string $handler): ?string
    {
        if ($handler === '') {
            return null;
        }

        if (preg_match('~^([A-Za-z0-9_]+)\\\\Controllers\\\\~', $handler, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
