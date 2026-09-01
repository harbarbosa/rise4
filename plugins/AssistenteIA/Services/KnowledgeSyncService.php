<?php

namespace AssistenteIA\Services;

class KnowledgeSyncService
{
    private const START = '<!-- ASSISTENTE_RUNTIME_INVENTORY_START -->';
    private const END = '<!-- ASSISTENTE_RUNTIME_INVENTORY_END -->';

    public function refresh(): string
    {
        $knowledgePath = PLUGINPATH . 'AssistenteIA/Knowledge/RISE_CRM_SYSTEM.md';
        $signaturePath = PLUGINPATH . 'AssistenteIA/Knowledge/.runtime_signature';
        $signature = $this->signature();

        if (is_file($signaturePath) && trim((string)file_get_contents($signaturePath)) === $signature && is_file($knowledgePath)) {
            return (string)file_get_contents($knowledgePath);
        }

        $base = is_file($knowledgePath) ? (string)file_get_contents($knowledgePath) : '# Conhecimento do RISE CRM' . PHP_EOL;
        $inventory = $this->buildInventory();
        $block = self::START . PHP_EOL . $inventory . PHP_EOL . self::END;
        $pattern = '/' . preg_quote(self::START, '/') . '.*?' . preg_quote(self::END, '/') . '/s';
        $updated = preg_match($pattern, $base) ? preg_replace($pattern, $block, $base) : rtrim($base) . PHP_EOL . PHP_EOL . $block . PHP_EOL;
        file_put_contents($knowledgePath, $updated);
        file_put_contents($signaturePath, $signature);
        return $updated;
    }

    public function entities(): array
    {
        $this->refresh();
        $entities = [];
        $db = \db_connect();
        foreach ($db->listTables() as $table) {
            $logical = preg_replace('/^[a-z0-9]+_/', '', strtolower((string)$table));
            if ($logical && !in_array($logical, ['users', 'settings', 'sessions', 'ci_sessions'], true)) {
                $entities[] = 'auto:' . $table;
            }
        }
        return array_slice(array_values(array_unique($entities)), 0, 120);
    }

    /**
     * Retorna módulos e tabelas que podem ser consultados pelo usuário atual.
     * A permissão é descoberta no próprio plugin; não há lista fixa de módulos.
     */
    public function resourcesForUser($user): array
    {
        $this->refresh();
        if ($user && !empty($user->is_admin)) return array_merge($this->moduleEntities(), $this->entities());

        $resources = [];
        foreach ($this->activePlugins() as $plugin) {
            if ($this->pluginHasPermission($plugin, $user)) $resources[] = 'module:' . strtolower($plugin);
        }
        return array_values(array_unique($resources));
    }

    public function canAccessModule(string $plugin, $user): bool
    {
        if ($user && !empty($user->is_admin)) return true;
        return $this->pluginHasPermission($plugin, $user);
    }

    public function moduleTables(string $plugin): array
    {
        $needle = strtolower(preg_replace('/[^a-z0-9]/i', '', $plugin));
        if ($needle === '') return [];
        $tables = [];
        foreach ((array)\db_connect()->listTables() as $table) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$table));
            if (strpos($normalized, $needle) === false) continue;
            if (preg_match('/(settings|config|migration|session|log|audit|notification)/i', (string)$table)) continue;
            $tables[] = (string)$table;
        }
        return array_values(array_unique($tables));
    }

    private function moduleEntities(): array
    {
        return array_map(static fn($plugin) => 'module:' . strtolower($plugin), $this->activePlugins());
    }

    private function activePlugins(): array
    {
        return json_decode((string)@file_get_contents(APPPATH . 'Config/activated_plugins.json'), true) ?: [];
    }

    private function pluginHasPermission(string $plugin, $user): bool
    {
        if (!$user) return false;
        $permissions = (array)($user->permissions ?? []);
        $files = [];
        $base = PLUGINPATH . $plugin;
        foreach (['index.php', 'Helpers', 'Controllers', 'Config'] as $path) {
            $full = $base . '/' . $path;
            if (is_file($full)) $files[] = $full;
            elseif (is_dir($full)) $files = array_merge($files, $this->phpFiles($full));
        }
        $permissionNames = [];
        foreach ($files as $file) {
            $content = (string)@file_get_contents($file);
            preg_match_all('/[\'\"]([a-z][a-z0-9_]*(?:view|access|create|manage|read|approve|edit|delete|reports|settings))[a-z0-9_]*[\'\"]/i', $content, $matches);
            foreach ($matches[1] ?? [] as $candidate) {
                $permissionNames[] = strtolower($candidate);
            }
            preg_match_all('/[\'\"]([a-z][a-z0-9_]*(?:_view|_access|_create|_manage|_read|_approve|_edit|_delete|_reports|_settings))[\'\"]/i', $content, $matches2);
            foreach ($matches2[1] ?? [] as $candidate) {
                $permissionNames[] = strtolower($candidate);
            }
        }
        foreach (array_unique($permissionNames) as $permission) {
            $value = $permissions[$permission] ?? null;
            if ($value === true || $value === 1 || in_array(strtolower((string)$value), ['1', 'all', 'read', 'read_only', 'own', 'specific'], true)) return true;
        }
        return false;
    }

    private function phpFiles(string $dir): array
    {
        $files = [];
        foreach (glob($dir . '/*.php') ?: [] as $file) $files[] = $file;
        foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $subdir) $files = array_merge($files, $this->phpFiles($subdir));
        return $files;
    }

    private function buildInventory(): string
    {
        $plugins = json_decode((string)@file_get_contents(APPPATH . 'Config/activated_plugins.json'), true) ?: [];
        $out = "## Inventário automático atualizado em " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "Este bloco foi gerado pelo sistema. Ele inclui recursos encontrados no filesystem e no banco; dados de negócio devem ser consultados em tempo real.\n\n";

        foreach ($plugins as $plugin) {
            $base = PLUGINPATH . $plugin;
            $routes = $this->routeLines($base . '/Config/Routes.php');
            $models = $this->models($base . '/Models');
            $controllers = $this->files($base . '/Controllers', '*.php');
            $views = $this->files($base . '/Views', '*.php');
            $out .= "### Plugin {$plugin}\n";
            if ($routes) $out .= "- Rotas: " . implode(', ', array_slice($routes, 0, 80)) . "\n";
            if ($models) $out .= "- Models: " . implode(', ', array_slice($models, 0, 80)) . "\n";
            if ($controllers) $out .= "- Controllers: " . implode(', ', array_slice($controllers, 0, 80)) . "\n";
            if ($views) $out .= "- Views: " . count($views) . " arquivos encontrados\n";
            $out .= "\n";
        }

        $tables = \db_connect()->listTables();
        $out .= "### Tabelas encontradas no banco\n";
        $out .= implode(', ', array_slice(array_map('strval', $tables), 0, 250)) . "\n";
        return $out;
    }

    private function signature(): string
    {
        $files = [APPPATH . 'Config/activated_plugins.json'];
        $plugins = json_decode((string)@file_get_contents($files[0]), true) ?: [];
        foreach ($plugins as $plugin) {
            foreach (['Config/Routes.php', 'Plugin.php'] as $relative) $files[] = PLUGINPATH . $plugin . '/' . $relative;
            foreach (glob(PLUGINPATH . $plugin . '/Models/*.php') ?: [] as $file) $files[] = $file;
            foreach (glob(PLUGINPATH . $plugin . '/Controllers/*.php') ?: [] as $file) $files[] = $file;
        }
        $parts = [];
        foreach ($files as $file) if (is_file($file)) $parts[] = $file . ':' . filemtime($file) . ':' . filesize($file);
        return sha1(implode('|', $parts));
    }

    private function routeLines(string $file): array
    {
        if (!is_file($file)) return [];
        preg_match_all('/\$routes->(?:get|post|put|delete|match)\s*\(([^;]+)/i', file_get_contents($file), $matches);
        return array_map(static fn($line) => preg_replace('/\s+/', ' ', trim($line)), $matches[0] ?? []);
    }

    private function models(string $dir): array
    {
        $result = [];
        foreach ($this->files($dir, '*.php') as $file) {
            $result[] = basename($file, '.php');
        }
        return $result;
    }

    private function files(string $dir, string $pattern): array
    {
        return is_dir($dir) ? (glob($dir . '/' . $pattern) ?: []) : [];
    }
}
