<?php

namespace RestApi\Controllers;

use Config\Database;
use ProjectAnalizer\Models\Photos_model;

class TimelogPhotosController extends Rest_api_Controller
{
    protected Photos_model $photosModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect('default');
        $this->photosModel = model('ProjectAnalizer\\Models\\Photos_model');
    }

    public function upload(int $timelogId)
    {
        $timelogId = (int) $timelogId;
        if ($timelogId <= 0) {
            return $this->failValidationErrors('Invalid timelog id.');
        }

        $timesheetTable = $this->db->prefixTable('project_time');
        $builder = $this->db->table($timesheetTable)->where('id', $timelogId);
        if ($this->db->fieldExists('deleted', $timesheetTable)) {
            $builder->where('deleted', 0);
        }

        if (!$builder->get()->getRow()) {
            return $this->failNotFound('Timelog not found.');
        }

        $files = $this->request->getFiles();
        $photos = $files['photos'] ?? [];
        if ($photos instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $photos = [$photos];
        }

        if (!is_array($photos) || !$photos) {
            return $this->failValidationErrors('photos is required.');
        }

        $this->photosModel->ensureTableExists();
        $targetDir = FCPATH . 'files/projectanalizer/';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return $this->failServerError('Could not create photo directory.');
        }

        $saved = [];
        $errors = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        foreach ($photos as $index => $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                $errors[] = ['index' => $index, 'message' => 'Invalid uploaded file.'];
                continue;
            }

            $extension = strtolower((string) $file->getExtension());
            $mimeType = strtolower((string) $file->getMimeType());
            if (!in_array($extension, $allowedExtensions, true) || strpos($mimeType, 'image/') !== 0) {
                $errors[] = ['index' => $index, 'message' => 'Only image files are allowed.'];
                continue;
            }

            if ($file->getSize() > 15 * 1024 * 1024) {
                $errors[] = ['index' => $index, 'message' => 'Image exceeds the 15 MB limit.'];
                continue;
            }

            $newName = $file->getRandomName();
            try {
                $file->move($targetDir, $newName);
            } catch (\Throwable $e) {
                log_message('error', '[TimelogPhotosController] File move failed: {message}', ['message' => $e->getMessage()]);
                $errors[] = ['index' => $index, 'message' => 'Could not store image.'];
                continue;
            }

            $photoId = $this->photosModel->insert([
                'timelog_id' => $timelogId,
                'file_name' => $newName,
                'file_path' => 'files/projectanalizer/' . $newName,
                'uploaded_by' => 0,
                'created_at' => get_current_utc_time(),
            ]);

            if (!$photoId) {
                @unlink($targetDir . $newName);
                $errors[] = ['index' => $index, 'message' => 'Could not register image.'];
                continue;
            }

            $saved[] = [
                'id' => (int) $photoId,
                'file_name' => $newName,
                'file_path' => 'files/projectanalizer/' . $newName,
                'url' => base_url('files/projectanalizer/' . $newName),
            ];
        }

        if (!$saved) {
            return $this->failValidationErrors([
                'status' => false,
                'message' => 'No photos were uploaded.',
                'errors' => $errors,
            ]);
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Photos uploaded successfully.',
            'timelog_id' => $timelogId,
            'count' => count($saved),
            'data' => $saved,
            'errors' => $errors,
        ]);
    }
}
