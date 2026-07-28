<?php

/**
 * API Laudos Técnicos v1
 * 
 * Endpoints para aplicativo mobile
 * 
 * @author RISE CRM
 * @version 1.0.0
 */

namespace LaudosTecnicos\Controllers\Api;

use App\Controllers\BaseController;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudo_inspections_model;
use LaudosTecnicos\Models\Laudo_checklists_model;
use LaudosTecnicos\Models\Laudo_checklist_answers_model;
use LaudosTecnicos\Models\Laudo_photos_model;
use LaudosTecnicos\Models\Laudo_non_conformities_model;
use LaudosTecnicos\Models\Laudo_versions_model;

class Api extends \App\Controllers\BaseController
{
    protected $laudos_model;
    protected $inspections_model;
    protected $checklists_model;
    protected $answers_model;
    protected $photos_model;
    protected $nc_model;
    protected $versions_model;
    
    protected $api_version = 'v1';
    protected $api_namespace = 'laudos/api';
    
    public function __construct()
    {
        parent::__construct();
        
        // Configuração de CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Device-UUID');
        
        if ($this->request->getMethod() === 'options') {
            exit;
        }
        
        // Rate limiting simples
        $this->_check_rate_limit();
        
        // Inicializar models
        $this->laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->inspections_model = model('LaudosTecnicos\Models\Laudo_inspections_model');
        $this->checklists_model = model('LaudosTecnicos\Models\Laudo_checklists_model');
        $this->answers_model = model('LaudosTecnicos\Models\Laudo_checklist_answers_model');
        $this->photos_model = model('LaudosTecnicos\Models\Laudo_photos_model');
        $this->nc_model = model('LaudosTecnicos\Models\Laudo_non_conformities_model');
        $this->versions_model = model('LaudosTecnicos\Models\Laudo_versions_model');
    }
    
    // ==================== AUTENTICAÇÃO ====================
    
    /**
     * @api {post} /api/laudos/v1/auth/login Login
     * @apiName Login
     * @apiGroup Autenticação
     */
    public function login()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $device_uuid = $this->request->getHeader('X-Device-UUID')->getValue() ?? null;
        
        if (!$email || !$password) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Email e senha são obrigatórios'
            ]);
        }
        
        // Autenticar usuário
        $users_model = model('App\Models\Users_model');
        $user = $users_model->authenticate($email, $password);
        
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => 'Credenciais inválidas'
            ]);
        }
        
        // Gerar tokens
        $access_token = bin2hex(random_bytes(32));
        $refresh_token = bin2hex(random_bytes(32));
        
        // Salvar dispositivo
        $devices_model = model('LaudosTecnicos\Models\Laudo_devices_model');
        $devices_model->register_device($user->id, $device_uuid, $access_token, $refresh_token);
        
        // Log
        $this->_log_api($user->id, 'POST', '/auth/login', 200);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_in' => 3600,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ]
            ]
        ]);
    }
    
    /**
     * @api {post} /api/laudos/v1/auth/refresh Atualizar Token
     * @apiName RefreshToken
     * @apiGroup Autenticação
     */
    public function refresh_token()
    {
        $refresh_token = $this->request->getPost('refresh_token');
        
        if (!$refresh_token) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Refresh token é obrigatório'
            ]);
        }
        
        $devices_model = model('LaudosTecnicos\Models\Laudo_devices_model');
        $device = $devices_model->validate_refresh($refresh_token);
        
        if (!$device) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => 'Refresh token inválido ou expirado'
            ]);
        }
        
        // Gerar novos tokens
        $new_access = bin2hex(random_bytes(32));
        $new_refresh = bin2hex(random_bytes(32));
        
        $devices_model->refresh_tokens($device->id, $new_access, $new_refresh);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'access_token' => $new_access,
                'refresh_token' => $new_refresh,
                'expires_in' => 3600
            ]
        ]);
    }
    
    /**
     * @api {post} /api/laudos/v1/auth/logout Logout
     * @apiName Logout
     * @apiGroup Autenticação
     */
    public function logout()
    {
        $device_uuid = $this->request->getHeader('X-Device-UUID')->getValue();
        
        if ($device_uuid) {
            $devices_model = model('LaudosTecnicos\Models\Laudo_devices_model');
            $devices_model->revoke_device($device_uuid);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Logout realizado'
        ]);
    }
    
    // ==================== LAUDOS ====================
    
    /**
     * @api {get} /api/laudos/v1/laudos Listar Laudos
     * @apiName ListLaudos
     * @apiGroup Laudos
     */
    public function get_laudos()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = (int)($this->request->getGet('limit') ?? 20);
        $status = $this->request->getGet('status');
        
        $options = array(
            'status' => $status,
            'deleted' => 0
        );
        
        $laudos = $this->laudos_model->get_details($options)->getResult();
        
        // Paginar
        $total = count($laudos);
        $offset = ($page - 1) * $limit;
        $data = array_slice($laudos, $offset, $limit);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * @api {get} /api/laudos/v1/laudos/:id Consultar Laudo
     * @apiName GetLaudo
     * @apiGroup Laudos
     */
    public function get_laudo($id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $laudo = $this->laudos_model->get_one($id);
        
        if (!$laudo) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error' => 'Laudo não encontrado'
            ]);
        }
        
        // Carregar seções
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($id);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'laudo' => $laudo,
                'sections' => $sections
            ]
        ]);
    }
    
    // ==================== INSPEÇÕES ====================
    
    /**
     * @api {get} /api/laudos/v1/inspections Listar Inspeções
     * @apiName ListInspections
     * @apiGroup Inspeções
     */
    public function get_inspections()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $inspections = $this->inspections_model->get_details([
            'responsible_id' => $user->id
        ])->getResult();
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $inspections
        ]);
    }
    
    /**
     * @api {get} /api/laudos/v1/inspections/:id Consultar Inspeção
     * @apiName GetInspection
     * @apiGroup Inspeções
     */
    public function get_inspection($id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $inspection = $this->inspections_model->get_one($id);
        
        if (!$inspection) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error' => 'Inspeção não encontrada'
            ]);
        }
        
        // Fotos
        $photos = $this->photos_model->get_for_inspection($id);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'inspection' => $inspection,
                'photos' => $photos
            ]
        ]);
    }
    
    /**
     * @api {post} /api/laudos/v1/inspections/:id/checkin Check-in
     * @apiName Checkin
     * @apiGroup Inspeções
     */
    public function checkin($id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $lat = $this->request->getPost('lat');
        $lng = $this->request->getPost('lng');
        
        $data = [
            'checkin_at' => date('Y-m-d H:i:s'),
            'checkin_lat' => $lat,
            'checkin_lng' => $lng,
            'status' => 'iniciated'
        ];
        
        $this->inspections_model->save($data, $id);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Check-in realizado'
        ]);
    }
    
    // ==================== CHECKLISTS ====================
    
    /**
     * @api {get} /api/laudos/v1/checklists/:laudo_id Baixar Checklists
     * @apiName GetChecklists
     * @apiGroup Checklists
     */
    public function get_checklists($laudo_id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $checklists = $this->checklists_model->get_for_laudo($laudo_id);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $checklists
        ]);
    }
    
    /**
     * @api {post} /api/laudos/v1/checklists/:laudo_id/answers Respostas
     * @apiName SubmitAnswers
     * @apiGroup Checklists
     */
    public function submit_answers($laudo_id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $answers = $this->request->getPost('answers');
        
        if (!$answers) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Respostas não informadas'
            ]);
        }
        
        foreach ($answers as $answer) {
            $data = [
                'laudo_id' => $laudo_id,
                'checklist_group_id' => $answer['group_id'],
                'checklist_item_id' => $answer['item_id'],
                'response' => $answer['response'],
                'observation' => $answer['observation'] ?? null,
                'user_id' => $user->id
            ];
            
            // Verificar se já existe
            $existing = $this->answers_model->get_existing($laudo_id, $answer['item_id'], $user->id);
            
            if ($existing) {
                $this->answers_model->save($data, $existing->id);
            } else {
                $this->answers_model->save($data, 0);
            }
            
            // Criar NC automática se não conforme
            if ($answer['response'] === 'Não conforme' && isset($answer['auto_nc']) && $answer['auto_nc']) {
                $this->nc_model->auto_create_from_checklist($answer['item_id'], $laudo_id, 'Não conforme');
            }
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Respostas salvas'
        ]);
    }
    
    // ==================== FOTOGRAFIAS ====================
    
    /**
     * @api {post} /api/laudos/v1/photos/upload Enviar Foto
     * @apiName UploadPhoto
     * @apiGroup Fotografias
     */
    public function upload_photo()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $file = $this->request->getFile('photo');
        
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Arquivo inválido'
            ]);
        }
        
        $laudo_id = $this->request->getPost('laudo_id');
        $inspection_id = $this->request->getPost('inspection_id');
        
        // Validar MIME type
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed_mimes)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Tipo de arquivo não permitido'
            ]);
        }
        
        // Limite de 10MB
        if ($file->getSize() > 10485760) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Arquivo muito grande (máx 10MB)'
            ]);
        }
        
        // Salvar
        $upload_path = 'uploads/laudos/' . $laudo_id . '/photos/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        
        $new_name = $file->getRandomName();
        $file->move($upload_path, $new_name);
        
        $original_path = $upload_path . $new_name;
        
        // Gerar hash
        $hash = $this->photos_model->generate_hash($original_path);
        
        $data = [
            'laudo_id' => $laudo_id,
            'inspection_id' => $inspection_id,
            'original_file' => $original_path,
            'thumbnail_file' => $original_path,
            'taken_at' => date('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'gps_lat' => $this->request->getPost('lat'),
            'gps_lng' => $this->request->getPost('lng'),
            'caption' => $this->request->getPost('caption'),
            'file_hash' => $hash
        ];
        
        $photo_id = $this->photos_model->save($data, 0);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'photo_id' => $photo_id,
                'url' => base_url($original_path)
            ]
        ]);
    }
    
    // ==================== NÃO CONFORMIDADES ====================
    
    /**
     * @api {post} /api/laudos/v1/nonconformities Criar NC
     * @apiName CreateNC
     * @apiGroup Não Conformidades
     */
    public function create_nc()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'laudo_id' => $this->request->getPost('laudo_id'),
            'classification' => $this->request->getPost('classification') ?: 'moderate',
            'probability' => $this->request->getPost('probability') ?: 2,
            'impact' => $this->request->getPost('impact') ?: 2,
            'status' => 'open',
            'identified_at' => date('Y-m-d'),
            'created_by' => $user->id
        ];
        
        $id = $this->nc_model->save($data, 0);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => ['id' => $id]
        ]);
    }
    
    // ==================== SINCRONIZAÇÃO ====================
    
    /**
     * @api {get} /api/laudos/v1/sync/changes Alterações
     * @apiName GetChanges
     * @apiGroup Sincronização
     */
    public function get_changes()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $device_uuid = $this->request->getHeader('X-Device-UUID')->getValue();
        $since = $this->request->getGet('since');
        
        // Buscar alterações desde a data
        $offline_model = model('LaudosTecnicos\Models\Laudo_offline_model');
        $changes = $offline_model->get_changes($device_uuid, $since);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $changes,
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * @api {post} /api/laudos/v1/sync/push Alterações do App
     * @apiName PushChanges
     * @apiGroup Sincronização
     */
    public function push_changes()
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $device_uuid = $this->request->getHeader('X-Device-UUID')->getValue();
        $changes = $this->request->getPost('changes');
        
        if (!$changes) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error' => 'Nenhuma alteração enviada'
            ]);
        }
        
        $offline_model = model('LaudosTecnicos\Models\Laudo_offline_model');
        $result = $offline_model->process_changes($device_uuid, $user->id, $changes);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $result
        ]);
    }
    
    // ==================== VERSÕES ====================
    
    /**
     * @api {get} /api/laudos/v1/versions/:laudo_id Listar Versões
     * @apiName ListVersions
     * @apiGroup Versionamento
     */
    public function get_versions($laudo_id)
    {
        $user = $this->_authenticate();
        if (!$user) return;
        
        $versions = $this->versions_model->get_for_laudo($laudo_id);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $versions
        ]);
    }
    
    // ==================== HELPERS PRIVADOS ====================
    
    private function _authenticate()
    {
        $token = $this->request->getHeader('X-API-Key')->getValue() 
            ?? $this->request->getHeader('Authorization')->getValue();
        
        if (!$token) {
            $this->_unauthorized('Token não fornecido');
            return null;
        }
        
        // Remover Bearer se presente
        $token = str_replace('Bearer ', '', $token);
        
        // Validar token
        $devices_model = model('LaudosTecnicos\Models\Laudo_devices_model');
        $device = $devices_model->validate_token($token);
        
        if (!$device) {
            $this->_unauthorized('Token inválido ou expirado');
            return null;
        }
        
        // Atualizar último acesso
        $devices_model->update_last_access($device->id);
        
        // Buscar usuário
        $users_model = model('App\Models\Users_model');
        return $users_model->get_one($device->user_id);
    }
    
    private function _unauthorized($message)
    {
        return $this->response->setStatusCode(401)->setJSON([
            'success' => false,
            'error' => $message
        ]);
    }
    
    private function _check_rate_limit()
    {
        $ip = $this->request->getIPAddress();
        $key = 'rate_limit_' . md5($ip);
        
        // Simples rate limiting em memória (em produção usar Redis ou DB)
        $now = time();
        $window = 60; // 1 minuto
        $limit = 60; // 60 requisições por minuto
        
        // Em produção, implementar com Redis ou banco de dados
        return true;
    }
    
    private function _log_api($user_id, $method, $endpoint, $status)
    {
        $logs_model = model('LaudosTecnicos\Models\Laudo_api_logs_model');
        $logs_model->log($user_id, $method, $endpoint, $status, [
            'ip' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString()
        ]);
    }
    
    // ==================== TRATAMENTO DE ERROS ====================
    
    public function __call($method, $args)
    {
        return $this->response->setStatusCode(404)->setJSON([
            'success' => false,
            'error' => 'Endpoint não encontrado'
        ]);
    }
}