<?php

namespace AssistenteIA\Services;

class RuntimeDiscoveryService
{
    public function contextFor(string $question): string
    {
        $knowledge = (new KnowledgeSyncService())->refresh();
        $terms = preg_split('/\s+/u', mb_strtolower($question));
        $terms = array_values(array_filter($terms, static fn($term) => mb_strlen($term) >= 4));
        $plugins = json_decode((string)@file_get_contents(APPPATH . 'Config/activated_plugins.json'), true) ?: [];
        $matches = [];

        foreach ($plugins as $plugin) {
            $base = PLUGINPATH . $plugin;
            $routeFile = $base . '/Config/Routes.php';
            $modelDir = $base . '/Models';
            $controllerDir = $base . '/Controllers';
            $routeText = is_file($routeFile) ? file_get_contents($routeFile) : '';
            $haystack = mb_strtolower($plugin . ' ' . $routeText . ' ' . implode(' ', is_dir($modelDir) ? array_map('basename', glob($modelDir . '/*.php') ?: []) : []));
            $score = 0;
            foreach ($terms as $term) if (mb_strpos($haystack, $term) !== false) $score++;
            if ($score > 0) {
                $matches[] = [
                    'plugin' => $plugin,
                    'routes' => $this->relevantLines($routeText, $terms),
                    'models' => is_dir($modelDir) ? array_map('basename', glob($modelDir . '/*.php') ?: []) : [],
                    'controllers' => is_dir($controllerDir) ? array_map('basename', glob($controllerDir . '/*.php') ?: []) : [],
                    'score' => $score,
                ];
            }
        }

        usort($matches, static fn($a, $b) => $b['score'] <=> $a['score']);
        $context = "Conhecimento atualizado do RISE CRM:\n" . mb_substr($knowledge, 0, 12000) . "\n\nDescoberta em tempo de execução para a pergunta: {$question}\n";
        foreach (array_slice($matches, 0, 4) as $match) {
            $context .= "\nPlugin: {$match['plugin']}\nModelos: " . implode(', ', array_slice($match['models'], 0, 20));
            if ($match['routes']) $context .= "\nRotas relevantes:\n- " . implode("\n- ", $match['routes']);
        }
        return mb_substr($context, 0, 9000);
    }

    private function relevantLines(string $text, array $terms): array
    {
        $lines = preg_split('/\R/', $text);
        $result = [];
        foreach ($lines as $line) {
            $lineLower = mb_strtolower($line);
            foreach ($terms as $term) {
                if (mb_strpos($lineLower, $term) !== false) {
                    $result[] = trim($line);
                    break;
                }
            }
            if (count($result) >= 12) break;
        }
        return array_values(array_unique($result));
    }
}
