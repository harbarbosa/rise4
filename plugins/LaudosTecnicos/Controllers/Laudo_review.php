<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudo_versions_model;
use LaudosTecnicos\Models\Laudo_approvals_model;
use LaudosTecnicos\Models\Laudo_signatures_model;
use LaudosTecnicos\Models\Laudo_technical_professionals_model;
use LaudosTecnicos\Models\Laudo_review_comments_model;
use LaudosTecnicos\Models\Laudo_pendencies_model;

class Laudo_review extends Security_Controller
{
    protected $laudos_model;
    protected $versions_model;
    protected $approvals_model;
    protected $signatures_model;
    protected $professionals_model;
    protected $comments_model;
    protected $pendencies_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->versions_model = model('LaudosTecnicos\Models\Laudo_versions_model');
        $this->approvals_model = model('LaudosTecnicos\Models\Laudo_approvals_model');
        $this->signatures_model = model('LaudosTecnicos\Models\Laudo_signatures_model');
        $this->professionals_model = model('LaudosTecnicos\Models\Laudo_technical_professionals_model');
        $this->comments_model = model('LaudosTecnicos\Models\Laudo_review_comments_model');
        $this->pendencies_model = model('LaudosTecnicos\Models\Laudo_pendencies_model');
    }

    // ==================== REVISÃO ====================
    public function review($laudo_id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $laudo = $this->laudos_model->get_one($laudo_id);
        if (!$laudo) {
            app_redirect('laudos_tecnicos');
        }

        // Carregar seções e campos
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);
        
        // Comentários
        $comments = $this->comments_model->get_for_laudo($laudo_id);
        
        // Pendências
        $pendencies = $this->pendencies_model->get_for_laudo($laudo_id);
        
        // Versões
        $versions = $this->versions_model->get_for_laudo($laudo_id);

        $view_data = array(
            'laudo' => $laudo,
            'sections' => $sections,
            'comments' => $comments,
            'pendencies' => $pendencies,
            'versions' => $versions,
            'open_comments' => $this->comments_model->get_open_count($laudo_id),
            'open_pendencies' => $this->pendencies_model->get_open_count($laudo_id)
        );

        return $this->template->rander('LaudosTecnicos\Views\review\review', $view_data);
    }

    public function add_comment()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'laudo_id' => $this->request->getPost('laudo_id'),
            'section_id' => $this->request->getPost('section_id') ?: null,
            'field_name' => $this->request->getPost('field_name'),
            'author_id' => $this->login_user->id,
            'text' => $this->request->getPost('text'),
            'status' => 'open',
            'priority' => $this->request->getPost('priority') ?: 'normal'
        );

        $save_id = $this->comments_model->save($data, 0);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => 'Comentário adicionado'));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function resolve_comment($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $response = $this->request->getPost('response');
        $this->comments_model->resolve($id, $this->login_user->id, $response);

        return $this->response->setJSON(array('success' => true, 'message' => 'Comentário resolvido'));
    }

    public function add_pendency()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'laudo_id' => $this->request->getPost('laudo_id'),
            'type' => $this->request->getPost('type'),
            'description' => $this->request->getPost('description'),
            'status' => 'pending',
            'created_by' => $this->login_user->id
        );

        $save_id = $this->pendencies_model->save($data, 0);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => 'Pendente adicionada'));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function resolve_pendency($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $this->pendencies_model->resolve($id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Pendente resolvida'));
    }

    public function finish_review($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        // Verificar pendências abertas
        $open_pendencies = $this->pendencies_model->get_open_count($laudo_id);
        $open_comments = $this->comments_model->get_open_count($laudo_id);

        if ($open_pendencies > 0 || $open_comments > 0) {
            return $this->response->setJSON(array(
                'success' => false, 
                'message' => "Existe(m) $open_pendencies pendência(s) e $open_comments comentário(s) aberto(s)"
            ));
        }

        // Atualizar status
        $this->laudos_model->save(array('review_status' => 'completed'), $laudo_id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Revisão finalizada'));
    }

    // ==================== APROVAÇÃO ====================
    public function approve($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        // Validar pré-requisitos
        $validation = $this->_validate_for_approval($laudo_id);
        if (!$validation['valid']) {
            return $this->response->setJSON(array('success' => false, 'message' => $validation['message']));
        }

        $comment = $this->request->getPost('comment');
        $version = $this->laudos_model->get_one($laudo_id)->current_version ?? 1;

        $this->approvals_model->approve($laudo_id, $version, $this->login_user->id, $comment);
        
        // Atualizar status do laudo
        $this->laudos_model->save(array('approval_status' => 'approved'), $laudo_id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Laudo aprovado'));
    }

    public function reject_approval($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $comment = $this->request->getPost('comment');
        $version = $this->laudos_model->get_one($laudo_id)->current_version ?? 1;

        $this->approvals_model->reject($laudo_id, $version, $this->login_user->id, $comment);
        
        // Atualizar status do laudo
        $this->laudos_model->save(array('approval_status' => 'rejected'), $laudo_id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Laudo rejeitado'));
    }

    private function _validate_for_approval($laudo_id)
    {
        $laudo = $this->laudos_model->get_one($laudo_id);
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);
        
        $errors = array();

        // Verificar campos obrigatórios vazios
        foreach ($sections as $section) {
            if ($section->required && empty($section->value)) {
                $errors[] = "Seção '{$section->name}': campo obrigatório vazio";
            }
        }

        // Verificar评论 abertos
        $open_comments = $this->comments_model->get_open_count($laudo_id);
        if ($open_comments > 0) {
            $errors[] = "$open_comments comentário(s) de revisão aberto(s)";
        }

        // Verificar pendências
        $open_pendencies = $this->pendencies_model->get_open_count($laudo_id);
        if ($open_pendencies > 0) {
            $errors[] = "$open_pendencies pendência(s) não resolvida(s)";
        }

        // Verificar responsável técnico
        if (!$laudo->technical_professional_id) {
            $errors[] = 'Responsável técnico não definido';
        }

        // Verificar revisão completa
        if ($laudo->review_status !== 'completed') {
            $errors[] = 'Revisão técnica não finalizada';
        }

        if (count($errors) > 0) {
            return array('valid' => false, 'message' => implode("\n", $errors));
        }

        return array('valid' => true);
    }

    // ==================== ASSINATURAS ====================
    public function sign($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $version = $this->laudos_model->get_one($laudo_id)->current_version ?? 1;
        $signer_name = $this->request->getPost('signer_name');
        $signer_document = $this->request->getPost('signer_document');
        $signer_role = $this->request->getPost('signer_role');
        $signature_data = $this->request->getPost('signature_data'); // Base64 ou dados do canvas

        $save_id = $this->signatures_model->add_signature(
            $laudo_id,
            $version,
            $signer_name,
            $signer_document,
            $signer_role,
            $signature_data,
            $this->login_user->id
        );

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => 'Assinatura registrada'));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function get_signatures($laudo_id, $version = null)
    {
        $signatures = $this->signatures_model->get_for_laudo($laudo_id, $version);
        return $this->response->setJSON($signatures);
    }

    // ==================== VERSIONAMENTO ====================
    public function create_version($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $reason = $this->request->getPost('reason');
        
        // Coletar conteúdo atual
        $laudo = $this->laudos_model->get_one($laudo_id);
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);
        
        $content = array(
            'laudo' => (array)$laudo,
            'sections' => array()
        );
        
        foreach ($sections as $s) {
            $content['sections'][] = array(
                'id' => $s->id,
                'name' => $s->name,
                'value' => $s->value,
                'order' => $s->sort
            );
        }

        $version_id = $this->versions_model->create_version($laudo_id, $content, $reason, $this->login_user->id);

        if ($version_id) {
            return $this->response->setJSON(array('success' => true, 'version_id' => $version_id));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function publish_version($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $this->versions_model->publish($id);
        
        $version = $this->versions_model->get_one($id);
        
        // Atualizar versão atual no laudo
        $this->laudos_model->save(array('current_version' => $version->version), $version->laudo_id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Versão publicada'));
    }

    public function compare_versions($laudo_id)
    {
        $v1 = $this->request->getGet('v1');
        $v2 = $this->request->getGet('v2');

        $differences = $this->versions_model->compare($laudo_id, $v1, $v2);

        return $this->response->setJSON(array(
            'success' => true,
            'differences' => $differences
        ));
    }

    public function view_version($id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $version = $this->versions_model->get_one($id);
        
        if (!$version) {
            app_redirect('laudos_tecnicos');
        }

        $content = json_decode($version->content_json, true);
        
        $view_data = array(
            'version' => $version,
            'content' => $content
        );

        return $this->template->rander('LaudosTecnicos\Views\review\version_view', $view_data);
    }

    // ==================== RESPONSÁVEIS TÉCNICOS ====================
    public function professionals()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $professionals = $this->professionals_model->get_active();

        $view_data = array(
            'professionals' => $professionals
        );

        return $this->template->rander('LaudosTecnicos\Views\review\professionals', $view_data);
    }

    public function professional_form($id = 0)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->professionals_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\review\professional_form', $view_data);
    }

    public function professional_save()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'cpf' => $this->request->getPost('cpf'),
            'council_type' => $this->request->getPost('council_type'),
            'council_number' => $this->request->getPost('council_number'),
            'council_state' => $this->request->getPost('council_state'),
            'specialty' => $this->request->getPost('specialty'),
            'role' => $this->request->getPost('role'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'status' => $this->request->getPost('status') ?: 'active',
            'validity_start' => $this->request->getPost('validity_start'),
            'validity_end' => $this->request->getPost('validity_end'),
            'art_number' => $this->request->getPost('art_number'),
            'rrt_number' => $this->request->getPost('rrt_number')
        );

        $save_id = $this->professionals_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== HELPERS ====================
    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_view') == '1';
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}