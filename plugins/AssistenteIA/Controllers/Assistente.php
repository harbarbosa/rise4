<?php

namespace AssistenteIA\Controllers;

use App\Controllers\Security_Controller;
use AssistenteIA\Services\LiveQueryService;
use AssistenteIA\Services\OpenRouterService;
use AssistenteIA\Services\RuntimeDiscoveryService;
use AssistenteIA\Services\SystemDataService;
use AssistenteIA\Services\ToolRegistry;

class Assistente extends Security_Controller
{
    public function index()
    {
        if (!\AssistenteIA\Plugin::canAccess($this->login_user)) return \app_redirect('forbidden');
        return $this->template->rander('AssistenteIA\\Views\\chat', []);
    }

    public function chat()
    {
        if (!\AssistenteIA\Plugin::canAccess($this->login_user)) return $this->response->setStatusCode(403);

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $message = trim((string)($input['message'] ?? ''));
        if ($message === '') return $this->response->setStatusCode(422)->setJSON(['error' => 'Mensagem obrigatória.']);

        $userId = (int)($this->login_user->id ?? 0);
        $conversationId = (int)($input['conversation_id'] ?? 0);
        $db = \db_connect();
        $conversations = $db->prefixTable('ai_conversations');
        $messages = $db->prefixTable('ai_messages');

        if ($conversationId) {
            $conversation = $db->table($conversations)->where(['id' => $conversationId, 'user_id' => $userId])->get()->getRow();
            if (!$conversation) return $this->response->setStatusCode(404)->setJSON(['error' => 'Conversa não encontrada.']);
        } else {
            $db->table($conversations)->insert(['user_id' => $userId, 'title' => mb_substr($message, 0, 120), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $conversationId = $db->insertID();
        }

        $db->table($messages)->insert(['conversation_id' => $conversationId, 'user_id' => $userId, 'role' => 'user', 'content' => $message, 'created_at' => date('Y-m-d H:i:s')]);
        $db->table($conversations)->where(['id' => $conversationId, 'user_id' => $userId])->update(['updated_at' => date('Y-m-d H:i:s')]);
        $history = $db->table($messages)->where(['conversation_id' => $conversationId, 'user_id' => $userId])->orderBy('id', 'ASC')->get()->getResultArray();
        $discovery = (new RuntimeDiscoveryService())->contextFor($message);
        $knowledgePath = PLUGINPATH . 'AssistenteIA/Knowledge/RISE_CRM_SYSTEM.md';
        $knowledge = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';
        $chatMessages = [['role' => 'system', 'content' => 'Você é o assistente do RISE CRM. Consulte primeiro o conhecimento do sistema e, quando necessário, use a descoberta de rotas, controllers e models abaixo. Nunca revele dados de ferramentas que não estejam disponíveis para o usuário. Se não houver permissão, informe isso claramente. Não diga que uma entidade não existe antes de analisar o conhecimento e a descoberta.' . "\n\n" . $knowledge . "\n\n" . $discovery]];
        foreach ($history as $item) $chatMessages[] = ['role' => $item['role'], 'content' => $item['content']];

        try {
            $quickAnswer = (new SystemDataService())->quickAnswer($message, $this->login_user);
            if ($quickAnswer !== null) {
                $db->table($messages)->insert(['conversation_id' => $conversationId, 'user_id' => $userId, 'role' => 'assistant', 'content' => $quickAnswer, 'metadata' => json_encode(['source' => 'rise_native_query']), 'created_at' => date('Y-m-d H:i:s')]);
                return $this->response->setJSON(['conversation_id' => $conversationId, 'answer' => $quickAnswer]);
            }

            $openRouter = new OpenRouterService();
            $tools = (new ToolRegistry())->availableForCurrentUser($this->login_user);
            $response = $openRouter->chat($chatMessages, $tools);

            for ($round = 0; $round < 3; $round++) {
                $assistantMessage = $response['choices'][0]['message'] ?? [];
                $toolCalls = $assistantMessage['tool_calls'] ?? [];
                if (!$toolCalls) break;

                $chatMessages[] = $assistantMessage;
                foreach ($toolCalls as $toolCall) {
                    $function = $toolCall['function'] ?? [];
                    $arguments = json_decode($function['arguments'] ?? '{}', true);
                    if (!is_array($arguments)) $arguments = [];
                    try {
                        $toolResult = (new LiveQueryService())->execute((string)($arguments['entity'] ?? ''), $arguments, $this->login_user);
                    } catch (\Throwable $toolError) {
                        $toolResult = ['error' => $toolError->getMessage()];
                    }
                    $chatMessages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'] ?? '',
                        'name' => $function['name'] ?? 'consultar_sistema',
                        'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }
                $response = $openRouter->chat($chatMessages, $tools);
            }

            $answer = (string)($response['choices'][0]['message']['content'] ?? 'Não foi possível obter uma resposta.');
            $db->table($messages)->insert(['conversation_id' => $conversationId, 'user_id' => $userId, 'role' => 'assistant', 'content' => $answer, 'metadata' => json_encode(['model' => $response['model'] ?? null]), 'created_at' => date('Y-m-d H:i:s')]);
            return $this->response->setJSON(['conversation_id' => $conversationId, 'answer' => $answer]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function conversations()
    {
        $userId = (int)($this->login_user->id ?? 0);
        $db = \db_connect();
        return $this->response->setJSON($db->table($db->prefixTable('ai_conversations'))->where('user_id', $userId)->orderBy('updated_at', 'DESC')->get()->getResultArray());
    }

    public function conversation(int $conversationId)
    {
        $userId = (int)($this->login_user->id ?? 0);
        $db = \db_connect();
        $conversation = $db->table($db->prefixTable('ai_conversations'))->where(['id' => $conversationId, 'user_id' => $userId])->get()->getRowArray();
        if (!$conversation) return $this->response->setStatusCode(404)->setJSON(['error' => 'Conversa não encontrada.']);
        $conversation['messages'] = $db->table($db->prefixTable('ai_messages'))->where(['conversation_id' => $conversationId, 'user_id' => $userId])->orderBy('id', 'ASC')->get()->getResultArray();
        return $this->response->setJSON($conversation);
    }

    public function deleteConversation(int $conversationId)
    {
        $userId = (int)($this->login_user->id ?? 0);
        $db = \db_connect();
        $conversationTable = $db->prefixTable('ai_conversations');
        $messageTable = $db->prefixTable('ai_messages');
        $conversation = $db->table($conversationTable)->where(['id' => $conversationId, 'user_id' => $userId])->get()->getRow();
        if (!$conversation) return $this->response->setStatusCode(404)->setJSON(['error' => 'Conversa não encontrada.']);
        $db->transStart();
        $db->table($messageTable)->where(['conversation_id' => $conversationId, 'user_id' => $userId])->delete();
        $db->table($conversationTable)->where(['id' => $conversationId, 'user_id' => $userId])->delete();
        $db->transComplete();
        return $this->response->setJSON(['success' => $db->transStatus()]);
    }
}
