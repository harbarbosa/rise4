<?php

namespace RestApi\Libraries;

class PluginRouteRegistry
{
    protected array $activePlugins;
    protected ?array $pluginRoutes = null;

    public function __construct()
    {
        $this->activePlugins = $this->loadActivePlugins();
    }

    public function all(): array
    {
        if ($this->pluginRoutes !== null) {
            return $this->pluginRoutes;
        }

        $routesByPlugin = [];

        foreach ($this->activePlugins as $pluginName) {
            $routesFile = rtrim(PLUGINPATH, '/\\') . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
            if (!is_file($routesFile)) {
                continue;
            }

            $routes = $this->parseRoutesFile($routesFile);
            if (!$routes) {
                continue;
            }

            $routesByPlugin[$pluginName] = [
                'plugin' => $pluginName,
                'route_count' => count($routes),
                'routes' => $routes,
                'source_file' => $routesFile,
            ];
        }

        ksort($routesByPlugin);
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

    protected function parseRoutesFile(string $routesFile): array
    {
        $content = file_get_contents($routesFile);
        if ($content === false || $content === '') {
            return [];
        }

        $routes = [];

        $simplePattern = '/\$routes\s*->\s*(?P<method>get|post|put|patch|delete|options|head|cli|add)\s*\(\s*(?P<route>' . $this->quotedValuePattern() . ')\s*,\s*(?P<handler>' . $this->quotedValuePattern() . ')/ims';
        $matchPattern = '/\$routes\s*->\s*match\s*\(\s*\[(?P<verbs>[^\]]+)\]\s*,\s*(?P<route>' . $this->quotedValuePattern() . ')\s*,\s*(?P<handler>' . $this->quotedValuePattern() . ')/ims';

        if (preg_match_all($simplePattern, $content, $simpleMatches, PREG_SET_ORDER)) {
            foreach ($simpleMatches as $matches) {
                $routes[] = [
                    'method' => strtoupper((string) $matches['method']),
                    'route' => $this->unquoteValue($matches['route'] ?? ''),
                    'name' => $this->unquoteValue($matches['route'] ?? ''),
                    'handler' => $this->unquoteValue($matches['handler'] ?? ''),
                ];
            }
        }

        if (preg_match_all($matchPattern, $content, $matchMatches, PREG_SET_ORDER)) {
            foreach ($matchMatches as $matches) {
                $verbs = $this->extractVerbs((string) ($matches['verbs'] ?? ''));
                $route = $this->unquoteValue($matches['route'] ?? '');
                $handler = $this->unquoteValue($matches['handler'] ?? '');

                foreach ($verbs as $verb) {
                    $routes[] = [
                        'method' => $verb,
                        'route' => $route,
                        'name' => $route,
                        'handler' => $handler,
                    ];
                }
            }
        }

        usort($routes, static function (array $left, array $right): int {
            return [$left['route'], $left['method'], $left['handler']] <=> [$right['route'], $right['method'], $right['handler']];
        });

        return $routes;
    }

    protected function extractVerbs(string $verbsString): array
    {
        preg_match_all("/['\"]([A-Z]+)['\"]/", $verbsString, $matches);
        $verbs = $matches[1] ?? [];
        $verbs = array_map('strtoupper', $verbs);
        return array_values(array_unique($verbs));
    }

    protected function unquoteValue(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^([\'"])(.*)\\1$/s', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }

    protected function quotedValuePattern(): string
    {
        return "(?:'([^']*)'|\"([^\"]*)\")";
    }
}
