<?php

namespace RestApi\Controllers;

use OrdemServico\Models\OsAtendimentos_members_model;
use OrdemServico\Models\OsAtendimentos_model;
use OrdemServico\Models\OsComments_model;
use OrdemServico\Models\OsFiles_model;
use OrdemServico\Models\OrdemServico_model;

/** REST mobile facade for the OrdemServico plugin. */
class OrdemServicoController extends Rest_api_Controller
{
    protected OrdemServico_model $orders;
    protected OsAtendimentos_model $attendances;
    protected OsAtendimentos_members_model $attendanceMembers;
    protected OsComments_model $commentsModel;
    protected OsFiles_model $filesModel;
    protected object $currentUser;

    public function __construct()
    {
        parent::__construct();
        $this->orders = model(OrdemServico_model::class);
        $this->attendances = model(OsAtendimentos_model::class);
        $this->attendanceMembers = model(OsAtendimentos_members_model::class);
        $this->commentsModel = model(OsComments_model::class);
        $this->filesModel = model(OsFiles_model::class);

        $validated = validateToken();
        $tokenUser = $validated['data'] ?? null;
        $email = strtolower(trim((string) ($tokenUser->email ?? $this->api_user->user ?? '')));
        $user = model('App\\Models\\Users_model')->get_one_where([
            'email' => $email,
            'user_type' => 'staff',
            'status' => 'active',
            'deleted' => 0,
        ]);
        if (!$user || empty($user->id)) {
            throw new \RuntimeException('Authenticated staff user not found.');
        }
        $this->currentUser = $user;
    }

    public function index()
    {
        $isManager = $this->isManager();
        $status = trim((string) $this->request->getGet('status'));
        $rows = $this->orders->get_details()->getResultArray();

        $rows = array_values(array_filter($rows, function (array $row) use ($isManager, $status): bool {
            if (!$isManager && (int) ($row['tecnico_id'] ?? 0) !== (int) $this->currentUser->id) {
                return false;
            }
            return $status === '' || (string) ($row['status'] ?? '') === $status;
        }));

        return $this->respond([
            'status' => true,
            'resource' => 'ordemservico_orders',
            'data' => array_map(fn (array $row) => $this->decorateOrder($row), $rows),
        ]);
    }

    public function show($id = null)
    {
        $id = (int) $id;
        $order = $this->findOrder($id);
        if (!$order) {
            return $this->failNotFound('Service order not found.');
        }

        return $this->respond([
            'status' => true,
            'resource' => 'ordemservico_order',
            'data' => $this->decorateOrder($order),
        ]);
    }

    public function start(int $id)
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return $this->failNotFound('Service order not found.');
        }
        if ((string) ($order['status'] ?? '') === 'fechada') {
            return $this->failValidationErrors('Closed service orders cannot be started.');
        }

        $saved = $this->orders->save_from_post(['status' => 'em_andamento'], $id);
        if (!$saved) {
            return $this->failServerError('Could not start service order.');
        }
        return $this->show($id);
    }

    public function finish(int $id)
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return $this->failNotFound('Service order not found.');
        }
        if ((string) ($order['status'] ?? '') === 'cancelada') {
            return $this->failValidationErrors('Cancelled service orders cannot be finished.');
        }

        $saved = $this->orders->save_from_post([
            'status' => 'fechada',
            'data_fechamento' => date('Y-m-d'),
        ], $id);
        if (!$saved) {
            return $this->failServerError('Could not finish service order.');
        }
        return $this->show($id);
    }

    public function attendances(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }

        $rows = $this->attendances->get_all_where(['os_id' => $id, 'deleted' => 0])->getResultArray();
        foreach ($rows as &$row) {
            $members = $this->attendanceMembers
                ->get_all_where(['atendimento_id' => (int) $row['id'], 'deleted' => 0])
                ->getResultArray();
            $row['member_ids'] = array_map(fn (array $member) => (int) $member['member_id'], $members);
        }

        return $this->respond(['status' => true, 'data' => $rows]);
    }

    public function checklist(int $id)
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return $this->failNotFound('Service order not found.');
        }

        try {
            $db = db_connect('default');
            $itemsTable = $db->prefixTable('os_checklist_items');
            $items = $db->table($itemsTable)
                ->where('tipo_id', (int) ($order['tipo_id'] ?? 0))
                ->where('deleted', 0)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
            return $this->respond(['status' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            return $this->respond(['status' => true, 'data' => []]);
        }
    }

    public function createAttendance(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }

        $payload = $this->payload();
        $start = str_replace('T', ' ', trim((string) ($payload['start_datetime'] ?? '')));
        $end = str_replace('T', ' ', trim((string) ($payload['end_datetime'] ?? '')));
        if ($start === '') {
            return $this->failValidationErrors('start_datetime is required.');
        }

        $data = [
            'os_id' => $id,
            'start_datetime' => $start,
            'end_datetime' => $end !== '' ? $end : null,
            'notes' => trim((string) ($payload['notes'] ?? '')),
            'defeito_apresentado' => trim((string) ($payload['defeito_apresentado'] ?? '')),
            'diagnostico' => trim((string) ($payload['diagnostico'] ?? '')),
            'solucao_encontrada' => trim((string) ($payload['solucao_encontrada'] ?? '')),
            'causa_raiz' => trim((string) ($payload['causa_raiz'] ?? '')),
            'materiais_utilizados' => trim((string) ($payload['materiais_utilizados'] ?? '')),
            'created_by' => (int) $this->currentUser->id,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $attendanceId = $this->attendances->ci_save($data);
        if (!$attendanceId) {
            return $this->failServerError('Could not save attendance.');
        }

        $memberIds = $payload['member_ids'] ?? [(int) $this->currentUser->id];
        if (!is_array($memberIds)) {
            $memberIds = explode(',', (string) $memberIds);
        }
        foreach (array_unique(array_filter(array_map('intval', $memberIds))) as $memberId) {
            $this->attendanceMembers->ci_save([
                'atendimento_id' => (int) $attendanceId,
                'member_id' => $memberId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $checklist = $payload['checklist'] ?? [];
        if (is_array($checklist)) {
            try {
                $db = db_connect('default');
                $answersTable = $db->prefixTable('os_atendimento_checklist');
                $itemsTable = $db->prefixTable('os_checklist_items');
                foreach ($checklist as $answer) {
                    if (!is_array($answer)) continue;
                    $itemId = (int) ($answer['item_id'] ?? 0);
                    if (!$itemId) continue;
                    $item = $db->table($itemsTable)->where('id', $itemId)->where('deleted', 0)->get()->getRowArray();
                    if (!$item) continue;
                    $status = (string) ($answer['status'] ?? 'pending');
                    if (!in_array($status, ['pending', 'ok', 'not_ok', 'na'], true)) $status = 'pending';
                    $db->table($answersTable)->insert([
                        'atendimento_id' => (int) $attendanceId,
                        'item_id' => $itemId,
                        'item_title' => $item['title'],
                        'status' => $status,
                        'notes' => trim((string) ($answer['notes'] ?? '')),
                        'checked_by' => (int) $this->currentUser->id,
                        'checked_at' => $status !== 'pending' ? date('Y-m-d H:i:s') : null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'deleted' => 0,
                    ]);
                }
            } catch (\Throwable $e) {
                // O atendimento continua salvo mesmo sem checklist configurado.
            }
        }

        return $this->respondCreated([
            'status' => true,
            'data' => $this->attendances->get_one((int) $attendanceId),
        ]);
    }

    public function comments(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }
        $rows = $this->commentsModel->get_all_where(['os_id' => $id, 'deleted' => 0])->getResultArray();
        return $this->respond(['status' => true, 'data' => $rows]);
    }

    public function createComment(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }
        $payload = $this->payload();
        $comment = trim((string) ($payload['comment'] ?? ''));
        if ($comment === '') {
            return $this->failValidationErrors('comment is required.');
        }
        $commentId = $this->commentsModel->ci_save([
            'os_id' => $id,
            'user_id' => (int) $this->currentUser->id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$commentId) {
            return $this->failServerError('Could not save comment.');
        }
        return $this->respondCreated(['status' => true, 'data' => $this->commentsModel->get_one((int) $commentId)]);
    }

    public function files(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }
        $rows = $this->filesModel->get_details(['os_id' => $id])->getResultArray();
        foreach ($rows as &$row) {
            $row['url'] = base_url('files/ordemservico/' . $id . '/' . rawurlencode((string) $row['file_name']));
        }
        return $this->respond(['status' => true, 'data' => $rows]);
    }

    public function uploadFiles(int $id)
    {
        if (!$this->findOrder($id)) {
            return $this->failNotFound('Service order not found.');
        }
        $files = $this->request->getFileMultiple('files');
        if (!$files) {
            $single = $this->request->getFile('file');
            $files = $single ? [$single] : [];
        }
        if (!$files) {
            return $this->failValidationErrors('At least one file is required.');
        }

        $directory = FCPATH . 'files/ordemservico/' . $id . '/';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $saved = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }
            $original = $file->getClientName();
            $name = bin2hex(random_bytes(12)) . '.' . ($file->getExtension() ?: 'bin');
            $file->move($directory, $name);
            $fileId = $this->filesModel->ci_save([
                'os_id' => $id,
                'file_name' => $name,
                'original_file_name' => $original,
                'file_size' => filesize($directory . $name) ?: 0,
                'uploaded_by' => (int) $this->currentUser->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($fileId) {
                $saved[] = [
                    'id' => (int) $fileId,
                    'original_file_name' => $original,
                    'url' => base_url('files/ordemservico/' . $id . '/' . rawurlencode($name)),
                ];
            }
        }
        return $this->respondCreated(['status' => true, 'data' => $saved]);
    }

    protected function findOrder(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = $this->orders->get_details(['id' => $id])->getRowArray();
        if (!$row || !$this->canAccess($row)) {
            return null;
        }
        return $row;
    }

    protected function canAccess(array $row): bool
    {
        return $this->isManager() || (int) ($row['tecnico_id'] ?? 0) === (int) $this->currentUser->id;
    }

    protected function isManager(): bool
    {
        if ((int) ($this->currentUser->is_admin ?? 0) === 1) {
            return true;
        }
        $permissions = $this->currentUser->permissions ?? [];
        return is_array($permissions) && (string) get_array_value($permissions, 'ordemservico_manage') === '1';
    }

    protected function decorateOrder(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['cliente_id'] = isset($row['cliente_id']) ? (int) $row['cliente_id'] : null;
        $row['tecnico_id'] = isset($row['tecnico_id']) ? (int) $row['tecnico_id'] : null;
        $row['is_assigned_to_me'] = $row['tecnico_id'] === (int) $this->currentUser->id;
        return $row;
    }
}
