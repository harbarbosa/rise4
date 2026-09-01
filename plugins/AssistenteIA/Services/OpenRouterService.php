<?php

namespace AssistenteIA\Services;

class OpenRouterService
{
    public function chat(array $messages, array $tools = []): array
    {
        $key = \get_setting('assistente_ia_openrouter_key');
        $model = \get_setting('assistente_ia_model') ?: 'openai/gpt-4o-mini';

        if (!$key) {
            throw new \RuntimeException('O OpenRouter ainda não foi configurado.');
        }

        $payload = ['model' => $model, 'messages' => $messages, 'temperature' => 0.2];
        if ($tools) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $client = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
                'HTTP-Referer: ' . \base_url(),
                'X-Title: RISE CRM - Assistente IA',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $body = curl_exec($client);
        $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
        $error = curl_error($client);
        curl_close($client);

        if ($body === false || $error) {
            throw new \RuntimeException('Falha ao conectar ao OpenRouter: ' . $error);
        }

        $response = json_decode($body, true);
        if ($status >= 400 || !is_array($response)) {
            throw new \RuntimeException('OpenRouter retornou HTTP ' . $status . '.');
        }

        return $response;
    }
}
