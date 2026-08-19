<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\App_Controller;
use LaudosTecnicos\Models\LaudoActionPlans_model;
use LaudosTecnicos\Models\LaudoChecklistResponses_model;
use LaudosTecnicos\Models\LaudoChecklists_model;
use LaudosTecnicos\Models\LaudoDocuments_model;
use LaudosTecnicos\Models\LaudoEquipments_model;
use LaudosTecnicos\Models\LaudoInspectionCheckins_model;
use LaudosTecnicos\Models\LaudoInspectionPhotos_model;
use LaudosTecnicos\Models\LaudoInspections_model;
use LaudosTecnicos\Models\LaudoMeasurements_model;
use LaudosTecnicos\Models\LaudoNonconformities_model;
use LaudosTecnicos\Models\LaudoPlatform_model;
use LaudosTecnicos\Models\LaudoStatusHistory_model;
use LaudosTecnicos\Models\LaudoStatusTransitions_model;
use LaudosTecnicos\Models\Laudos_model;

class Api_v1 extends App_Controller
{
    private LaudoPlatform_model $platform_model;
    private Laudos_model $laudos_model;
    private LaudoInspections_model $inspections_model;
    private LaudoChecklists_model $checklists_model;
    private LaudoChecklistResponses_model $checklist_responses_model;
    private LaudoMeasurements_model $measurements_model;
    private LaudoNonconformities_model $nonconformities_model;
    private LaudoInspectionCheckins_model $checkins_model;
    private LaudoInspectionPhotos_model $photos_model;
    private LaudoDocuments_model $documents_model;
    private LaudoActionPlans_model $action_plans_model;
    private LaudoStatusHistory_model $status_history_model;
    private LaudoStatusTransitions_model $transitions_model;

    private ?object $api_user = null;
    private ?object $api_device = null;
    private ?object $api_token = null;
    private int $auth_error_status = 401;

    public function __construct()
    {
        parent::__construct();
        \LaudosTecnicos\Plugin::runMigrations();
        $this->applyCorsHeaders();

        $this->platform_model = model(LaudoPlatform_model::class);
        $this->laudos_model = model(Laudos_model::class);
        $this->inspections_model = model(LaudoInspections_model::class);
        $this->checklists_model = model(LaudoChecklists_model::class);
        $this->checklist_responses_model = model(LaudoChecklistResponses_model::class);
        $this->measurements_model = model(LaudoMeasurements_model::class);
        $this->nonconformities_model = model(LaudoNonconformities_model::class);
        $this->checkins_model = model(LaudoInspectionCheckins_model::class);
        $this->photos_model = model(LaudoInspectionPhotos_model::class);
        $this->documents_model = model(LaudoDocuments_model::class);
        $this->action_plans_model = model(LaudoActionPlans_model::class);
        $this->status_history_model = model(LaudoStatusHistory_model::class);
        $this->transitions_model = model(LaudoStatusTransitions_model::class);
    }

    public function login()
    {
        if (!$this->requestIsAllowed()) {
            return $this->json(array('success' => false, 'message' => 'Requisicao bloqueada.'), 429);
        }

        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        if ($email === '' || $password === '') {
            return $this->json(array('success' => false, 'message' => 'Credenciais invalidas.'), 400);
        }

        $user = $this->authenticateUser($email, $password);
        if (!$user) {
            return $this->json(array('success' => false, 'message' => 'Autenticacao falhou.'), 401);
        }

        $device_id = $this->platform_model->upsert_device(array(
            'device_uuid' => trim((string) $this->request->getPost('device_uuid')),
            'user_id' => (int) $user->id,
            'device_name' => trim((string) $this->request->getPost('device_name')),
            'platform' => trim((string) $this->request->getPost('platform')),
            'app_version' => trim((string) $this->request->getPost('app_version')),
            'push_token' => trim((string) $this->request->getPost('push_token')),
            'last_ip_address' => $this->request->getIPAddress(),
            'last_seen_at' => get_current_utc_time(),
            'created_by' => (int) $user->id,
            'updated_by' => (int) $user->id,
        ));

        $device_row = $device_id ? $this->platform_model->get_one((int) $device_id) : null;
        $tokens = $this->issueTokenPair($user, $device_row);
        if (!$tokens) {
            return $this->json(array('success' => false, 'message' => 'Falha ao emitir token.'), 500);
        }

        $this->api_user = $user;
        $this->api_device = $device_row;
        $this->api_token = (object) array('id' => $tokens['access_id']);

        return $this->json(array(
            'success' => true,
            'data' => array(
                'user' => $this->serializeUser($user),
                'device' => $device_row,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => (int) get_setting('api_access_token_lifetime'),
                'refresh_expires_in' => (int) get_setting('api_refresh_token_lifetime'),
            ),
        ));
    }

    public function logout()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $this->platform_model->revoke_token((int) $this->api_token->id, 'logout');
        if (!empty($this->api_device->id)) {
            $this->platform_model->revoke_device((int) $this->api_device->id, 'logout');
        }

        return $this->json(array('success' => true, 'message' => 'Sessao encerrada.'));
    }

    public function refresh()
    {
        if (!$this->requestIsAllowed()) {
            return $this->json(array('success' => false, 'message' => 'Requisicao bloqueada.'), 429);
        }

        $refresh_token = trim((string) $this->request->getPost('refresh_token'));
        if ($refresh_token === '') {
            return $this->json(array('success' => false, 'message' => 'Refresh token ausente.'), 400);
        }

        $refresh_row = $this->platform_model->find_refresh_token($refresh_token);
        if (!$refresh_row || !empty($refresh_row->revoked_at)) {
            return $this->json(array('success' => false, 'message' => 'Refresh token invalido.'), 401);
        }

        $user = $this->Users_model->get_one((int) $refresh_row->user_id);
        $device = null;
        if (!empty($refresh_row->device_id)) {
            $device = $this->platform_model->get_one((int) $refresh_row->device_id);
            if (!$device || empty($device->id)) {
                $device = null;
            }
        }
        if (!$user || !$user->id) {
            return $this->json(array('success' => false, 'message' => 'Usuario nao encontrado.'), 404);
        }

        $this->platform_model->revoke_user_tokens((int) $user->id);
        $tokens = $this->issueTokenPair($user, $device);
        if (!$tokens) {
            return $this->json(array('success' => false, 'message' => 'Falha ao renovar token.'), 500);
        }

        return $this->json(array(
            'success' => true,
            'data' => array(
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => (int) get_setting('api_access_token_lifetime'),
            ),
        ));
    }

    public function profile()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array(
            'success' => true,
            'data' => array(
                'user' => $this->serializeUser($this->api_user),
                'device' => $this->api_device,
                'permissions' => $this->api_user->permissions ?? array(),
            ),
        ));
    }

    public function devices()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array(
            'success' => true,
            'data' => $this->platform_model->get_devices_for_user((int) $this->api_user->id),
        ));
    }

    public function revoke_device($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $ok = $this->platform_model->revoke_device((int) $id, 'revoked via api');
        return $this->json(array('success' => (bool) $ok));
    }

    public function laudos()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        if ($this->request->getMethod(true) === 'GET') {
            $rows = $this->laudos_model->get_details($this->laudoFilters())->getResult();
            return $this->json(array('success' => true, 'data' => $rows));
        }

        $payload = $this->requestData();
        $id = (int) get_array_value($payload, 'id');
        $payload['created_by'] = (int) $this->api_user->id;
        $payload['updated_by'] = (int) $this->api_user->id;
        if (empty($payload['status'])) {
            $payload['status'] = 'draft';
        }

        $saved = $this->laudos_model->save_from_post($payload, $id ?: null);
        if (!$saved) {
            return $this->json(array('success' => false, 'message' => app_lang('error_occurred')), 500);
        }

        $laudo_id = $id ?: (int) $saved;
        $this->platform_model->save_sync_record(array(
            'entity_type' => 'laudo',
            'entity_id' => $laudo_id,
            'user_id' => (int) $this->api_user->id,
            'device_uuid' => (string) ($this->api_device->device_uuid ?? ''),
            'payload_json' => laudostecnicos_safe_json($payload),
            'record_hash' => sha1(laudostecnicos_safe_json($payload)),
        ));

        return $this->json(array('success' => true, 'data' => $this->laudos_model->get_one_with_details($laudo_id)));
    }

    public function laudo($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $row = $this->laudos_model->get_one_with_details((int) $id);
        return $this->json(array('success' => (bool) ($row && $row->id), 'data' => $row));
    }

    public function laudo_history($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $history = $this->status_history_model->get_details(array('laudo_id' => (int) $id))->getResult();
        return $this->json(array('success' => true, 'data' => $history));
    }

    public function laudo_status($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $laudo = $this->laudos_model->get_one_with_details((int) $id);
        $allowed = $laudo ? $this->transitions_model->get_allowed_transitions((string) ($laudo->status ?? '')) : array();
        return $this->json(array('success' => true, 'data' => array('laudo' => $laudo, 'allowed_transitions' => $allowed)));
    }

    public function laudo_pending($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $progress = $this->checklist_responses_model->get_progress(array('laudo_id' => (int) $id));
        $inspections = $this->inspections_model->get_details(array('laudo_id' => (int) $id))->getResult();
        $nonconformities = $this->nonconformities_model->get_details(array('laudo_id' => (int) $id))->getResult();
        return $this->json(array('success' => true, 'data' => array(
            'checklist_progress' => $progress,
            'inspections_count' => count($inspections),
            'nonconformities_count' => count($nonconformities),
        )));
    }

    public function laudos_draft()
    {
        return $this->laudos();
    }

    public function inspections()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $rows = $this->inspections_model->get_details($this->inspectionFilters())->getResult();
        return $this->json(array('success' => true, 'data' => $rows));
    }

    public function inspection($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array('success' => true, 'data' => $this->inspections_model->get_one_with_details((int) $id)));
    }

    public function inspection_package($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $inspection = $this->inspections_model->get_one_with_details((int) $id);
        if (!$inspection || !$inspection->id) {
            return $this->json(array('success' => false, 'message' => app_lang('record_not_found')), 404);
        }

        return $this->json(array(
            'success' => true,
            'data' => array(
                'inspection' => $inspection,
                'checkins' => $this->checkins_model->get_by_inspection((int) $id),
                'photos' => $this->photos_model->get_by_inspection((int) $id),
                'checklist_progress' => $this->checklist_responses_model->get_progress(array('laudo_id' => (int) $inspection->laudo_id)),
            ),
        ));
    }

    public function inspection_start($id = 0) { return $this->inspectionAction((int) $id, 'start'); }
    public function inspection_pause($id = 0) { return $this->inspectionAction((int) $id, 'pause'); }
    public function inspection_finish($id = 0) { return $this->inspectionAction((int) $id, 'finish'); }

    public function inspection_checkin($id = 0)
    {
        return $this->inspectionCheck((int) $id, 'checkin');
    }

    public function inspection_checkout($id = 0)
    {
        return $this->inspectionCheck((int) $id, 'checkout');
    }

    public function inspection_improductive($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $ok = $this->inspections_model->mark_improductive((int) $id, $this->requestData());
        return $this->json(array('success' => (bool) $ok));
    }

    public function checklists_download($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array('success' => true, 'data' => json_decode($this->checklists_model->export_json((int) $id), true)));
    }

    public function checklists_responses()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $responses = get_array_value($this->requestData(), 'responses', array());
        $saved = $this->checklist_responses_model->save_bulk(is_array($responses) ? $responses : array(), array(
            'user_id' => (int) $this->api_user->id,
            'created_by' => (int) $this->api_user->id,
            'updated_by' => (int) $this->api_user->id,
            'source' => 'mobile',
            'ip_address' => $this->request->getIPAddress(),
        ));
        return $this->json(array('success' => (bool) $saved, 'saved' => (int) $saved));
    }

    public function checklists_batch()
    {
        return $this->checklists_responses();
    }

    public function checklists_progress($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array('success' => true, 'data' => $this->checklist_responses_model->get_progress(array('laudo_id' => (int) $id))));
    }

    public function checklists_pending($id = 0)
    {
        return $this->checklists_progress($id);
    }

    public function measurements()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $filters = array('laudo_id' => (int) $this->request->getGet('laudo_id'));
        return $this->json(array('success' => true, 'data' => $this->measurements_model->get_details($filters)->getResult()));
    }

    public function measurement_create()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $payload['created_by'] = (int) $this->api_user->id;
        $payload['updated_by'] = (int) $this->api_user->id;
        $saved = $this->measurements_model->save_from_post($payload, null);
        if (!$saved) {
            return $this->json(array('success' => false), 500);
        }

        return $this->json(array('success' => true, 'data' => $saved));
    }

    public function measurement_update($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $payload['updated_by'] = (int) $this->api_user->id;
        $saved = $this->measurements_model->save_from_post($payload, (int) $id);
        return $this->json(array('success' => (bool) $saved));
    }

    public function measurement_equipment($id = 0)
    {
        return $this->measurement_update($id);
    }

    public function measurement_photo($id = 0)
    {
        return $this->uploadPhotoToMeasurement((int) $id);
    }

    public function upload_photo()
    {
        return $this->uploadInspectionPhoto();
    }

    public function upload_photos()
    {
        return $this->uploadInspectionPhoto(true);
    }

    public function upload_file()
    {
        return $this->uploadGenericFile();
    }

    public function upload_signature()
    {
        return $this->uploadGenericFile(true);
    }

    public function upload_update($id = 0)
    {
        return $this->updateInspectionPhoto((int) $id);
    }

    public function upload_delete($id = 0)
    {
        return $this->deleteInspectionPhoto((int) $id);
    }

    public function nonconformities()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $rows = $this->nonconformities_model->get_details(array('laudo_id' => (int) $this->request->getGet('laudo_id')))->getResult();
        return $this->json(array('success' => true, 'data' => $rows));
    }

    public function nonconformity_create()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $payload['created_by'] = (int) $this->api_user->id;
        $payload['updated_by'] = (int) $this->api_user->id;
        $saved = $this->nonconformities_model->save_from_post($payload, null);
        return $this->json(array('success' => (bool) $saved, 'data' => $saved));
    }

    public function nonconformity_update($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $payload['updated_by'] = (int) $this->api_user->id;
        $saved = $this->nonconformities_model->save_from_post($payload, (int) $id);
        return $this->json(array('success' => (bool) $saved));
    }

    public function nonconformity_classify($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $nc = $this->nonconformities_model->get_one_with_details((int) $id);
        if (!$nc || !$nc->id) {
            return $this->json(array('success' => false), 404);
        }

        $risk = $this->nonconformities_model->resolve_risk((int) ($nc->probability ?? 1), (int) ($nc->impact ?? 1), (int) ($nc->category_id ?? 0));
        return $this->json(array('success' => true, 'data' => $risk));
    }

    public function nonconformity_photo($id = 0)
    {
        return $this->uploadGenericFile(false, 'nonconformity', (int) $id);
    }

    public function nonconformity_correction($id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $payload['status'] = 'corrected';
        $payload['corrected_at'] = get_current_utc_time();
        $saved = $this->nonconformities_model->save_from_post($payload, (int) $id);
        return $this->json(array('success' => (bool) $saved));
    }

    public function sync_pull()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $sync_cursor = trim((string) $this->request->getGet('cursor'));
        $laudos = $this->laudos_model->get_details(array('search' => trim((string) $this->request->getGet('search'))))->getResult();
        $inspections = $this->inspections_model->get_details(array())->getResult();
        $ncs = $this->nonconformities_model->get_details(array())->getResult();

        return $this->json(array(
            'success' => true,
            'data' => array(
                'cursor' => $sync_cursor ?: get_current_utc_time(),
                'laudos' => $laudos,
                'inspections' => $inspections,
                'nonconformities' => $ncs,
                'updated_at' => get_current_utc_time(),
            ),
        ));
    }

    public function sync_push()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $payload = $this->requestData();
        $items = is_array(get_array_value($payload, 'items')) ? get_array_value($payload, 'items') : array();
        $saved = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->platform_model->save_sync_record(array_merge($item, array(
                'user_id' => (int) $this->api_user->id,
                'device_uuid' => (string) ($this->api_device->device_uuid ?? ''),
                'payload_json' => laudostecnicos_safe_json($item),
                'record_hash' => sha1(laudostecnicos_safe_json($item)),
            )));
            $saved++;
        }

        return $this->json(array('success' => true, 'saved' => $saved));
    }

    public function sync_status()
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        return $this->json(array(
            'success' => true,
            'data' => array(
                'last_sync_at' => $this->api_device->last_sync_at ?? null,
                'device_uuid' => $this->api_device->device_uuid ?? '',
                'status' => 'ok',
            ),
        ));
    }

    private function authorize(): bool
    {
        if (!$this->requestIsAllowed()) {
            $this->auth_error_status = 429;
            return false;
        }

        $token = $this->bearerToken();
        if ($token === '') {
            return false;
        }

        $token_row = $this->platform_model->find_access_token($token);
        if (!$token_row || !empty($token_row->revoked_at)) {
            return false;
        }

        if (!empty($token_row->expires_at) && strtotime((string) $token_row->expires_at) < time()) {
            return false;
        }

        $user = $this->Users_model->get_access_info((int) $token_row->user_id);
        if (!$user || !$user->id) {
            return false;
        }

        $this->api_user = $user;
        $this->api_token = $token_row;
        $this->api_device = null;
        if (!empty($token_row->device_id)) {
            $this->api_device = $this->platform_model->get_one((int) $token_row->device_id);
            if ($this->api_device) {
                if (!empty($this->api_device->revoked_at) || (isset($this->api_device->is_active) && (int) $this->api_device->is_active === 0)) {
                    return false;
                }
                if (!empty($this->api_device->expires_at) && strtotime((string) $this->api_device->expires_at) < time()) {
                    return false;
                }
            }
        }

        $this->platform_model->log_request(array(
            'user_id' => (int) $user->id,
            'device_id' => (int) ($token_row->device_id ?? 0),
            'method' => $this->request->getMethod(true),
            'endpoint' => uri_string(),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
        ));

        return true;
    }

    private function authError()
    {
        $status = $this->auth_error_status ?: 401;
        return $this->json(array('success' => false, 'message' => $status === 429 ? 'Limite de requisicoes excedido.' : 'Nao autorizado.'), $status);
    }

    private function requestIsAllowed(): bool
    {
        if (get_setting('api_require_https') === '1' && !$this->request->isSecure()) {
            return false;
        }

        $limit = max(1, (int) get_setting('api_rate_limit_per_minute'));
        $table = $this->db->prefixTable('laudo_api_request_logs');
        if ($this->db->tableExists($table)) {
            $since = date('Y-m-d H:i:s', time() - 60);
            $count = (int) $this->db->table($table)
                ->where('ip_address', $this->request->getIPAddress())
                ->where('created_at >=', $since)
                ->countAllResults();
            if ($count >= $limit) {
                return false;
            }
        }

        return true;
    }

    private function applyCorsHeaders(): void
    {
        $origin = trim((string) $this->request->getHeaderLine('Origin'));
        if ($origin === '') {
            return;
        }

        $allowed = array_filter(array_map('trim', explode(',', (string) get_setting('api_cors_origins'))));
        if (in_array('*', $allowed, true) || in_array($origin, $allowed, true)) {
            $this->response->setHeader('Access-Control-Allow-Origin', $origin);
            $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
            $this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
            $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        }
    }

    private function bearerToken(): string
    {
        $header = (string) $this->request->getHeaderLine('Authorization');
        if ($header !== '' && stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }

        return trim((string) $this->request->getGetPost('access_token'));
    }

    private function requestData(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : array();
    }

    private function json(array $payload, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function authenticateUser(string $email, string $password)
    {
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');
        $email = strtolower(trim($email));

        $rows = $this->db->table($users_table)
            ->where('email', $email)
            ->where('deleted', 0)
            ->where('status', 'active')
            ->where('disable_login', 0)
            ->get()
            ->getResult();

        foreach ($rows as $user) {
            if (!$this->verifyPassword($user->password ?? '', $password)) {
                continue;
            }

            if ($user->user_type === 'client') {
                $client = $this->db->table($clients_table)->where('id', (int) $user->client_id)->where('deleted', 0)->get()->getRow();
                if (!$client) {
                    continue;
                }
            }

            return $this->Users_model->get_access_info((int) $user->id);
        }

        return null;
    }

    private function verifyPassword(string $stored, string $plain): bool
    {
        if ($stored === '') {
            return false;
        }

        if (strlen($stored) === 60 && password_verify($plain, $stored)) {
            return true;
        }

        return $stored === md5($plain);
    }

    private function issueTokenPair($user, $device_row): array
    {
        $access_lifetime = max(600, (int) get_setting('api_access_token_lifetime'));
        $refresh_lifetime = max($access_lifetime, (int) get_setting('api_refresh_token_lifetime'));

        $access_token = laudostecnicos_generate_token(64);
        $refresh_token = laudostecnicos_generate_token(64);

        $access = $this->platform_model->create_token(array(
            'user_id' => (int) $user->id,
            'device_id' => (int) ($device_row->id ?? 0),
            'token_type' => 'access',
            'plain_token' => $access_token,
            'expires_at' => date('Y-m-d H:i:s', time() + $access_lifetime),
            'refresh_expires_at' => date('Y-m-d H:i:s', time() + $refresh_lifetime),
            'scope_json' => array('mobile', 'offline', 'sync'),
            'created_by' => (int) $user->id,
            'updated_by' => (int) $user->id,
        ));

        if (!$access) {
            return array();
        }

        $refresh = $this->platform_model->create_token(array(
            'user_id' => (int) $user->id,
            'device_id' => (int) ($device_row->id ?? 0),
            'token_type' => 'refresh',
            'plain_token' => $refresh_token,
            'expires_at' => date('Y-m-d H:i:s', time() + $refresh_lifetime),
            'scope_json' => array('refresh'),
            'created_by' => (int) $user->id,
            'updated_by' => (int) $user->id,
        ));

        return array(
            'access_id' => (int) get_array_value($access, 'id'),
            'access_token' => get_array_value($access, 'plain_token'),
            'refresh_token' => get_array_value($refresh, 'plain_token'),
        );
    }

    private function serializeUser($user): array
    {
        return array(
            'id' => (int) ($user->id ?? 0),
            'name' => trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))),
            'email' => trim((string) ($user->email ?? '')),
            'user_type' => trim((string) ($user->user_type ?? '')),
            'client_id' => (int) ($user->client_id ?? 0),
            'is_admin' => (int) ($user->is_admin ?? 0),
        );
    }

    private function inspectionFilters(): array
    {
        return array(
            'search' => trim((string) $this->request->getGet('search')),
            'status' => trim((string) $this->request->getGet('status')),
            'responsible_id' => (int) $this->request->getGet('responsible_id'),
            'client_id' => (int) $this->request->getGet('client_id'),
            'inspection_date' => trim((string) $this->request->getGet('inspection_date')),
        );
    }

    private function laudoFilters(): array
    {
        return array(
            'search' => trim((string) $this->request->getGet('search')),
            'client_id' => (int) $this->request->getGet('client_id'),
            'contact_id' => (int) $this->request->getGet('contact_id'),
            'project_id' => (int) $this->request->getGet('project_id'),
            'type_id' => (int) $this->request->getGet('type_id'),
            'category_id' => (int) $this->request->getGet('category_id'),
            'responsible_id' => (int) $this->request->getGet('responsible_id'),
            'reviewer_id' => (int) $this->request->getGet('reviewer_id'),
            'approver_id' => (int) $this->request->getGet('approver_id'),
            'status' => trim((string) $this->request->getGet('status')),
            'priority' => trim((string) $this->request->getGet('priority')),
            'unit_name' => trim((string) $this->request->getGet('unit_name')),
        );
    }

    private function inspectionAction(int $id, string $action)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $ok = false;
        if ($action === 'start') {
            $ok = $this->inspections_model->start($id);
        } elseif ($action === 'pause') {
            $ok = $this->inspections_model->pause($id);
        } elseif ($action === 'finish') {
            $ok = $this->inspections_model->finish($id);
        }

        return $this->json(array('success' => (bool) $ok));
    }

    private function inspectionCheck(int $id, string $check_type)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $inspection = $this->inspections_model->get_one_with_details($id);
        if (!$inspection || !$inspection->id) {
            return $this->json(array('success' => false, 'message' => app_lang('record_not_found')), 404);
        }

        $saved = $this->checkins_model->log_checkin(array(
            'inspection_id' => $id,
            'laudo_id' => (int) ($inspection->laudo_id ?? 0),
            'checked_at' => get_current_utc_time(),
            'latitude' => trim((string) $this->request->getPost('latitude')),
            'longitude' => trim((string) $this->request->getPost('longitude')),
            'accuracy' => trim((string) $this->request->getPost('accuracy')),
            'user_id' => (int) $this->api_user->id,
            'device' => trim((string) $this->request->getPost('device')),
            'distance_meters' => trim((string) $this->request->getPost('distance_meters')),
            'observation' => trim((string) $this->request->getPost('observation')),
            'source' => 'mobile',
            'ip_address' => $this->request->getIPAddress(),
            'created_by' => (int) $this->api_user->id,
            'updated_by' => (int) $this->api_user->id,
        ), $check_type);

        return $this->json(array('success' => (bool) $saved));
    }

    private function uploadInspectionPhoto(bool $multiple = false)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $files = $multiple ? $this->request->getFiles() : array('file' => $this->request->getFile('file'));
        $saved = array();
        $inspection_id = (int) $this->request->getPost('inspection_id');
        $folder = rtrim(WRITEPATH, "\\/") . DIRECTORY_SEPARATOR . 'laudostecnicos' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR;
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $file_list = array();
        if ($multiple && isset($files['files']) && is_array($files['files'])) {
            $file_list = $files['files'];
        } elseif (isset($files['file'])) {
            $file_list = array($files['file']);
        }

        foreach ($file_list as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $mime = strtolower((string) $file->getMimeType());
            if (strpos($mime, 'image/') !== 0 && strpos($mime, 'application/pdf') !== 0) {
                continue;
            }

            $name = laudostecnicos_generate_token(24) . '.' . $file->guessExtension();
            $target = $folder . $name;
            $file->move($folder, $name);

            $photo_id = $this->photos_model->save_photo(array(
                'inspection_id' => $inspection_id,
                'laudo_id' => (int) $this->request->getPost('laudo_id'),
                'file_path' => str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $target),
                'thumbnail_path' => str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $target),
                'original_file_name' => $file->getClientName(),
                'caption' => trim((string) $this->request->getPost('caption')),
                'taken_at' => get_current_utc_time(),
                'user_id' => (int) $this->api_user->id,
                'location_text' => trim((string) $this->request->getPost('location_text')),
                'sector' => trim((string) $this->request->getPost('sector')),
                'equipment_id' => (int) $this->request->getPost('equipment_id'),
                'checklist_id' => (int) $this->request->getPost('checklist_id'),
                'measurement_id' => (int) $this->request->getPost('measurement_id'),
                'nonconformity_id' => (int) $this->request->getPost('nonconformity_id'),
                'observation' => trim((string) $this->request->getPost('observation')),
                'hash_value' => sha1_file($target),
                'created_by' => (int) $this->api_user->id,
                'updated_by' => (int) $this->api_user->id,
            ));
            if ($photo_id) {
                $saved[] = $photo_id;
            }
        }

        return $this->json(array('success' => true, 'saved' => $saved));
    }

    private function uploadGenericFile(bool $signature = false, string $entity_type = '', int $entity_id = 0)
    {
        if (!$this->authorize()) {
            return $this->authError();
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->json(array('success' => false, 'message' => 'Arquivo invalido.'), 400);
        }

        $folder = rtrim(WRITEPATH, "\\/") . DIRECTORY_SEPARATOR . 'laudostecnicos' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . ($signature ? 'signatures' : 'files') . DIRECTORY_SEPARATOR;
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $name = laudostecnicos_generate_token(24) . '.' . $file->guessExtension();
        $file->move($folder, $name);

        return $this->json(array(
            'success' => true,
            'data' => array(
                'path' => str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $folder . $name),
                'name' => $file->getClientName(),
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
            ),
        ));
    }

    private function updateInspectionPhoto(int $id)
    {
        return $this->json(array('success' => true, 'id' => $id));
    }

    private function deleteInspectionPhoto(int $id)
    {
        return $this->json(array('success' => true, 'id' => $id));
    }

    private function uploadPhotoToMeasurement(int $measurement_id)
    {
        return $this->uploadGenericFile(false, 'measurement', $measurement_id);
    }
}
