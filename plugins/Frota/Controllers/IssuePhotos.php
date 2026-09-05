<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class IssuePhotos extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        helper('frota');
        $this->db = db_connect('default');
    }

    protected function requireAccess(): void
    {
        if ($this->login_user && $this->login_user->is_admin) {
            return;
        }

        $permissions = $this->login_user->permissions ?? [];
        if (get_array_value($permissions, 'frota_issue') != '1' && get_array_value($permissions, 'frota_manage') != '1') {
            app_redirect('forbidden');
        }
    }

    public function upload()
    {
        $this->requireAccess();

        $issueId = (int)$this->request->getPost('issue_id');
        if (!$issueId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Ocorrência inválida.']);
        }

        $table = $this->db->table($this->db->prefixTable('frota_issues'));
        $issue = $table->where('id', $issueId)->where('deleted', 0)->get()->getRowArray();
        if (!$issue) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Ocorrência não encontrada.']);
        }

        $files = $this->request->getFileMultiple('photos') ?: [];
        if (!$files) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Nenhuma foto enviada.']);
        }

        $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $directory = FCPATH . 'files/frota/ocorrencias/' . $issueId;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Não foi possível preparar a pasta das fotos.']);
        }

        $existing = [];
        if (!empty($issue['photo_url'])) {
            $decoded = json_decode((string)$issue['photo_url'], true);
            $existing = is_array($decoded) ? $decoded : [(string)$issue['photo_url']];
        }

        $uploaded = [];
        $errors = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                $errors[] = 'Uma das fotos não pôde ser enviada.';
                continue;
            }

            if ($file->getSize() > 10 * 1024 * 1024) {
                $errors[] = $file->getClientName() . ': arquivo maior que 10 MB.';
                continue;
            }

            $mime = strtolower((string)$file->getMimeType());
            if (!isset($allowedMime[$mime])) {
                $errors[] = $file->getClientName() . ': formato não permitido.';
                continue;
            }

            $name = bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
            try {
                $file->move($directory, $name);
                $path = 'files/frota/ocorrencias/' . $issueId . '/' . $name;
                $uploaded[] = $path;
                $existing[] = $path;
            } catch (\Throwable $e) {
                log_message('error', '[Frota/Fotos] ' . $e->getMessage());
                $errors[] = $file->getClientName() . ': falha ao salvar.';
            }
        }

        if ($uploaded) {
            $table->where('id', $issueId)->update(['photo_url' => json_encode(array_values(array_unique($existing)), JSON_UNESCAPED_SLASHES)]);
        }

        return $this->response->setJSON([
            'success' => (bool)$uploaded,
            'files' => $uploaded,
            'errors' => $errors,
            'message' => $uploaded ? 'Fotos adicionadas com sucesso.' : 'Nenhuma foto foi salva.'
        ]);
    }
}
