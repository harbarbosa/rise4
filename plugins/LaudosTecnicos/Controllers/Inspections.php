<?php

namespace LaudosTecnicos\Controllers;

class Inspections extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureInspectionsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\inspections\\index', array(
            'can_manage_inspections' => \LaudosTecnicos\Plugin::canManageInspections($this->login_user),
            'statuses' => $this->statusOptions(),
            'responsibles_dropdown' => model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff')),
            'clients_dropdown' => model('App\\Models\\Clients_model')->get_id_and_text_dropdown(array('company_name')),
        ));
    }

    public function agenda()
    {
        $this->ensureInspectionsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\inspections\\agenda', array(
            'can_manage_inspections' => \LaudosTecnicos\Plugin::canManageInspections($this->login_user),
        ));
    }

    public function agenda_events()
    {
        $this->ensureInspectionsAccess();

        $events = $this->inspections_model->get_calendar_events(array(
            'status' => trim((string) $this->request->getGet('status')),
            'responsible_id' => (int) $this->request->getGet('responsible_id'),
            'client_id' => (int) $this->request->getGet('client_id'),
            'inspection_date' => trim((string) $this->request->getGet('inspection_date')),
        ));

        return $this->response->setJSON($events);
    }

    public function list_data()
    {
        $this->ensureInspectionsAccess();

        $rows = $this->inspections_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'inspection_date' => trim((string) $this->request->getPost('inspection_date')),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/inspecoes/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . anchor(get_uri('laudostecnicos/inspecoes/view/' . $row->id), "<i data-feather='eye' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-primary', 'title' => app_lang('view')));
            $actions .= ' ' . anchor(get_uri('laudostecnicos/inspecoes/mobile/' . $row->id), "<i data-feather='smartphone' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-info', 'title' => app_lang('laudostecnicos_inspection_mobile')));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->code),
                anchor(get_uri('laudostecnicos/laudos/view/' . (int) $row->laudo_id), esc($row->laudo_number ?: '-'), array('target' => '_blank')),
                esc($row->client_name ?: '-'),
                esc($row->unit_name ?: '-'),
                esc($row->location_name ?: '-'),
                esc($row->inspection_type ?: '-'),
                esc($row->inspection_date ?: '-'),
                esc($row->start_time ?: '-'),
                esc($row->duration_minutes ?: 0),
                esc($row->responsible_name ?: '-'),
                $this->statusBadge((string) ($row->status ?: 'planned')),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureInspectionsAccess();

        $id = (int) $id;
        $model_info = $id ? $this->inspections_model->get_one($id) : null;
        if (!$model_info) {
            $model_info = (object) array(
                'id' => '',
                'code' => '',
                'laudo_id' => (int) $this->request->getPost('laudo_id'),
                'client_id' => (int) $this->request->getPost('client_id'),
                'unit_name' => '',
                'location_name' => '',
                'inspection_type' => '',
                'inspection_date' => trim((string) $this->request->getPost('inspection_date')),
                'start_time' => trim((string) $this->request->getPost('start_time')) ?: '08:00:00',
                'end_time' => '',
                'duration_minutes' => 60,
                'responsible_id' => (int) ($this->login_user->id ?? 0),
                'team_json' => '[]',
                'vehicle' => '',
                'equipments_json' => '[]',
                'observations' => '',
                'status' => 'planned',
                'address' => '',
                'latitude' => '',
                'longitude' => '',
            );
        }

        return $this->template->view('LaudosTecnicos\\Views\\inspections\\modal_form', array(
            'model_info' => $model_info,
            'statuses' => $this->statusOptions(),
            'clients_dropdown' => model('App\\Models\\Clients_model')->get_id_and_text_dropdown(array('company_name')),
            'laudos_rows' => $this->laudos_model->get_details(array())->getResult(),
            'responsibles_dropdown' => model('App\\Models\\Users_model')->get_id_and_text_dropdown(array('first_name', 'last_name'), array('deleted' => 0, 'user_type' => 'staff')),
            'equipments_dropdown' => $this->equipments_model->get_active_dropdown(true),
        ));
    }

    public function save()
    {
        $this->ensureInspectionsAccess();

        $id = (int) $this->request->getPost('id');
        $data = $this->collectPayload();
        $data['created_by'] = (int) ($this->login_user->id ?? 0);
        $data['updated_by'] = (int) ($this->login_user->id ?? 0);

        if ($data['laudo_id'] <= 0 || trim((string) $data['inspection_date']) === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $conflicts = $this->inspections_model->has_conflict($data, $id);
        if ($conflicts) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Conflito detectado na agenda.',
                'conflicts' => $conflicts,
            ));
        }

        $saved_id = $this->inspections_model->save_from_post($data, $id ?: null);
        if (!$saved_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'id' => is_numeric($saved_id) ? (int) $saved_id : $id,
        ));
    }

    public function view($id = 0)
    {
        $this->ensureInspectionsAccess();
        $inspection = $this->inspections_model->get_one_with_details((int) $id);
        if (!$inspection || !$inspection->id) {
            show_404();
        }

        return $this->template->rander('LaudosTecnicos\\Views\\inspections\\view', array(
            'inspection' => $inspection,
            'checkins' => $this->inspection_checkins_model->get_by_inspection((int) $inspection->id),
            'photos' => $this->inspection_photos_model->get_by_inspection((int) $inspection->id),
            'checklist_progress' => $this->checklist_responses_model->get_progress(array('inspection_id' => (int) $inspection->id, 'laudo_id' => (int) $inspection->laudo_id)),
            'completion_issues' => $this->inspections_model->validate_completion((int) $inspection->id),
            'statuses' => $this->statusOptions(),
        ));
    }

    public function mobile($id = 0)
    {
        return $this->view($id);
    }

    public function checkin($id = 0)
    {
        $this->ensureInspectionsAccess();

        $inspection = $this->inspections_model->get_one((int) $id);
        if (!$inspection || !$inspection->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $checkin_id = $this->inspection_checkins_model->log_checkin(array(
            'inspection_id' => (int) $inspection->id,
            'laudo_id' => (int) $inspection->laudo_id,
            'checked_at' => get_current_utc_time(),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'accuracy' => $this->request->getPost('accuracy'),
            'user_id' => (int) ($this->login_user->id ?? 0),
            'device' => trim((string) $this->request->getPost('device')),
            'distance_meters' => $this->request->getPost('distance_meters'),
            'observation' => trim((string) $this->request->getPost('observation')),
            'source' => trim((string) $this->request->getPost('source')) ?: 'mobile',
            'ip_address' => $this->request->getIPAddress(),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        ), 'checkin');

        if (!$checkin_id) {
            return $this->response->setJSON(array('success' => false));
        }

        $this->inspections_model->save_from_post(array('checkin_at' => get_current_utc_time(), 'status' => 'in_progress', 'updated_by' => (int) ($this->login_user->id ?? 0)), (int) $inspection->id);
        return $this->response->setJSON(array('success' => true));
    }

    public function checkout($id = 0)
    {
        $this->ensureInspectionsAccess();

        $inspection = $this->inspections_model->get_one((int) $id);
        if (!$inspection || !$inspection->id) {
            return $this->response->setJSON(array('success' => false));
        }

        $checkin_id = $this->inspection_checkins_model->log_checkin(array(
            'inspection_id' => (int) $inspection->id,
            'laudo_id' => (int) $inspection->laudo_id,
            'checked_at' => get_current_utc_time(),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'accuracy' => $this->request->getPost('accuracy'),
            'user_id' => (int) ($this->login_user->id ?? 0),
            'device' => trim((string) $this->request->getPost('device')),
            'distance_meters' => $this->request->getPost('distance_meters'),
            'observation' => trim((string) $this->request->getPost('observation')),
            'source' => trim((string) $this->request->getPost('source')) ?: 'mobile',
            'ip_address' => $this->request->getIPAddress(),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        ), 'checkout');

        if (!$checkin_id) {
            return $this->response->setJSON(array('success' => false));
        }

        $this->inspections_model->save_from_post(array('checkout_at' => get_current_utc_time(), 'status' => 'completed', 'updated_by' => (int) ($this->login_user->id ?? 0)), (int) $inspection->id);
        return $this->response->setJSON(array('success' => true));
    }

    public function start($id = 0)
    {
        $this->ensureInspectionsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->inspections_model->start((int) $id)));
    }

    public function pause($id = 0)
    {
        $this->ensureInspectionsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->inspections_model->pause((int) $id)));
    }

    public function finish($id = 0)
    {
        $this->ensureInspectionsAccess();

        $allow_incomplete = $this->request->getPost('allow_incomplete') ? 1 : 0;
        $issues = $this->inspections_model->validate_completion((int) $id);
        if ($issues && !$allow_incomplete) {
            return $this->response->setJSON(array('success' => false, 'issues' => $issues, 'message' => 'Existem pendências para concluir.'));
        }

        $ok = $this->inspections_model->finish((int) $id);
        if (!$ok) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($allow_incomplete) {
            $this->inspections_model->save_from_post(array(
                'comments' => trim((string) $this->request->getPost('incomplete_reason')),
                'updated_by' => (int) ($this->login_user->id ?? 0),
            ), (int) $id);
        }

        return $this->response->setJSON(array('success' => true, 'issues' => $issues));
    }

    public function improductive($id = 0)
    {
        $this->ensureInspectionsAccess();

        $ok = $this->inspections_model->mark_improductive((int) $id, array(
            'improductive_reason' => $this->request->getPost('improductive_reason'),
            'improductive_evidence' => $this->request->getPost('improductive_evidence'),
            'client_contact_name' => $this->request->getPost('client_contact_name'),
            'suggested_new_date' => $this->request->getPost('suggested_new_date'),
            'costs' => $this->request->getPost('costs'),
            'comments' => $this->request->getPost('comments'),
        ));

        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function upload_photo()
    {
        $this->ensureInspectionsAccess();

        $inspection_id = (int) $this->request->getPost('inspection_id');
        if (!$inspection_id || empty($_FILES['photo'])) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $photo = $_FILES['photo'];
        if (!empty($photo['error'])) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $target_dir = rtrim(getcwd() . DIRECTORY_SEPARATOR . get_setting('timeline_file_path'), "\\/") . DIRECTORY_SEPARATOR . 'laudostecnicos' . DIRECTORY_SEPARATOR . 'inspections' . DIRECTORY_SEPARATOR;
        if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true) && !is_dir($target_dir)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $safe_name = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($photo['name']));
        $filename = 'insp_' . uniqid() . '_' . $safe_name;
        $destination = $target_dir . $filename;
        if (!move_uploaded_file($photo['tmp_name'], $destination)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $thumb = $target_dir . 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $this->createThumbnail($destination, $thumb);

        $save_ok = $this->inspection_photos_model->save_photo(array(
            'inspection_id' => $inspection_id,
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'file_path' => str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $destination),
            'thumbnail_path' => is_file($thumb) ? str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $thumb) : str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $destination),
            'original_file_name' => $photo['name'],
            'caption' => trim((string) $this->request->getPost('caption')),
            'photo_number' => (int) $this->request->getPost('photo_number'),
            'taken_at' => trim((string) $this->request->getPost('taken_at')) ?: get_current_utc_time(),
            'user_id' => (int) ($this->login_user->id ?? 0),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'location_text' => trim((string) $this->request->getPost('location_text')),
            'sector' => trim((string) $this->request->getPost('sector')),
            'equipment_id' => (int) $this->request->getPost('equipment_id'),
            'checklist_id' => (int) $this->request->getPost('checklist_id'),
            'measurement_id' => (int) $this->request->getPost('measurement_id'),
            'nonconformity_id' => (int) $this->request->getPost('nonconformity_id'),
            'observation' => trim((string) $this->request->getPost('observation')),
            'hash_value' => sha1_file($destination),
            'is_cover' => $this->request->getPost('is_cover') ? 1 : 0,
            'is_before' => $this->request->getPost('is_before') ? 1 : 0,
            'is_after' => $this->request->getPost('is_after') ? 1 : 0,
            'sort' => (int) $this->request->getPost('sort'),
            'metadata' => array('source_file' => $photo['name']),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        ));

        return $this->response->setJSON(array('success' => (bool) $save_ok, 'file' => $save_ok ? $filename : ''));
    }

    public function set_cover()
    {
        $this->ensureInspectionsAccess();
        $ok = $this->inspection_photos_model->set_cover((int) $this->request->getPost('inspection_id'), (int) $this->request->getPost('photo_id'));
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function reorder_photos()
    {
        $this->ensureInspectionsAccess();
        $ids = $this->request->getPost('photo_ids');
        $ids = is_array($ids) ? $ids : array();
        $ok = $this->inspection_photos_model->reorder((int) $this->request->getPost('inspection_id'), $ids);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function delete_photo()
    {
        $this->ensureInspectionsAccess();
        $photo_id = (int) $this->request->getPost('id');
        $photo = $this->inspection_photos_model->get_one($photo_id);
        if (!$photo || !$photo->id) {
            return $this->response->setJSON(array('success' => false));
        }

        $this->inspection_photos_model->delete($photo_id);
        return $this->response->setJSON(array('success' => true));
    }

    public function validate_completion($id = 0)
    {
        $this->ensureInspectionsAccess();
        return $this->response->setJSON(array(
            'success' => true,
            'issues' => $this->inspections_model->validate_completion((int) $id),
        ));
    }

    public function delete()
    {
        $this->ensureInspectionsAccess();
        $id = (int) $this->request->getPost('id');
        $inspection = $this->inspections_model->get_one($id);
        if (!$inspection || !$inspection->id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (!in_array((string) ($inspection->status ?? 'planned'), array('planned', 'scheduled', 'rescheduled', 'canceled'), true)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $ok = $this->inspections_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    private function collectPayload(): array
    {
        $team = $this->request->getPost('team_members');
        if (is_array($team)) {
            $team = array_values(array_filter(array_map('intval', $team)));
        } else {
            $team = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', trim((string) $team), -1, PREG_SPLIT_NO_EMPTY) ?: array())));
        }
        $equipments = $this->request->getPost('equipment_ids');
        if (is_array($equipments)) {
            $equipments = array_values(array_filter(array_map('intval', $equipments)));
        } else {
            $equipments = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', trim((string) $equipments), -1, PREG_SPLIT_NO_EMPTY) ?: array())));
        }

        return array(
            'code' => trim((string) $this->request->getPost('code')),
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'unit_name' => trim((string) $this->request->getPost('unit_name')),
            'location_name' => trim((string) $this->request->getPost('location_name')),
            'inspection_type' => trim((string) $this->request->getPost('inspection_type')),
            'inspection_date' => trim((string) $this->request->getPost('inspection_date')),
            'start_time' => trim((string) $this->request->getPost('start_time')),
            'end_time' => trim((string) $this->request->getPost('end_time')),
            'duration_minutes' => (int) $this->request->getPost('duration_minutes'),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'team_json' => laudostecnicos_safe_json($team),
            'vehicle' => trim((string) $this->request->getPost('vehicle')),
            'equipments_json' => laudostecnicos_safe_json($equipments),
            'observations' => trim((string) $this->request->getPost('observations')),
            'status' => trim((string) $this->request->getPost('status')) ?: 'planned',
            'progress_percent' => (float) ($this->request->getPost('progress_percent') ?: 0),
            'client_contact_name' => trim((string) $this->request->getPost('client_contact_name')),
            'suggested_new_date' => trim((string) $this->request->getPost('suggested_new_date')),
            'costs_json' => trim((string) $this->request->getPost('costs_json')),
            'comments' => trim((string) $this->request->getPost('comments')),
            'source' => trim((string) $this->request->getPost('source')) ?: 'web',
            'address' => trim((string) $this->request->getPost('address')),
            'latitude' => trim((string) $this->request->getPost('latitude')),
            'longitude' => trim((string) $this->request->getPost('longitude')),
        );
    }

    private function statusOptions(): array
    {
        return array(
            'planned' => 'Planejada',
            'scheduled' => 'Agendada',
            'confirmed' => 'Confirmada',
            'traveling' => 'Em deslocamento',
            'in_progress' => 'Iniciada',
            'paused' => 'Pausada',
            'completed' => 'Concluída',
            'improductive' => 'Improdutiva',
            'rescheduled' => 'Reagendada',
            'canceled' => 'Cancelada',
        );
    }

    private function statusBadge(string $status): string
    {
        $map = array(
            'planned' => 'bg-secondary',
            'scheduled' => 'bg-primary',
            'confirmed' => 'bg-success',
            'traveling' => 'bg-info',
            'in_progress' => 'bg-primary',
            'paused' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'improductive' => 'bg-danger',
            'rescheduled' => 'bg-purple',
            'canceled' => 'bg-dark',
        );

        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($this->statusOptions()[$status] ?? $status) . '</span>';
    }

    private function createThumbnail(string $source, string $destination): void
    {
        if (!function_exists('imagecreatefromstring')) {
            copy($source, $destination);
            return;
        }

        $contents = @file_get_contents($source);
        if ($contents === false) {
            return;
        }

        $image = @imagecreatefromstring($contents);
        if (!$image) {
            copy($source, $destination);
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 480;
        $ratio = $width > $height ? ($max / max(1, $width)) : ($max / max(1, $height));
        $new_width = max(1, (int) round($width * $ratio));
        $new_height = max(1, (int) round($height * $ratio));
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagejpeg($thumb, $destination, 82);
        imagedestroy($thumb);
        imagedestroy($image);
    }
}
