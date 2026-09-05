<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class IssuePhotos extends Security_Controller
{
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

        $files = $this->request->getFiles();
        $photos = $files['photos'] ?? [];
        if (!is_array($photos)) {
            $photos = [$photos];
        }

        if (!$photos) {
            return $this->response->setJSON(['success' => true, 'files' => []]);
        }

        $targetDir = FCPATH . 'files/frota/issues/';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível preparar a pasta de fotos.'
            ]);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $saved = [];

        foreach ($photos as $photo) {
            if (!$photo || !$photo->isValid() || $photo->hasMoved()) {
                continue;
            }

            if ($photo->getSize() > 10 * 1024 * 1024) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Cada foto deve ter no máximo 10 MB.'
                ]);
            }

            $mime = $photo->getMimeType();
            if (!in_array($mime, $allowed, true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Formato inválido. Envie JPG, PNG ou WEBP.'
                ]);
            }

            $extension = strtolower($photo->getExtension() ?: 'jpg');
            $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $photo->move($targetDir, $fileName);
            $saved[] = base_url('files/frota/issues/' . $fileName);
        }

        return $this->response->setJSON([
            'success' => true,
            'files' => $saved
        ]);
    }
}
