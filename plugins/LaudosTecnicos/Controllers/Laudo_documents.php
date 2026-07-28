<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudo_document_model;
use LaudosTecnicos\Models\Laudo_shares_model;
use LaudosTecnicos\Models\Laudo_versions_model;
use LaudosTecnicos\Models\Laudo_notifications_model;

class Laudo_documents extends Security_Controller
{
    protected $laudos_model;
    protected $document_model;
    protected $shares_model;
    protected $versions_model;
    protected $notifications_model;

    public function __construct()
    {
        parent::__construct(true);
        
        $this->laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->document_model = model('LaudosTecnicos\Models\Laudo_document_model');
        $this->shares_model = model('LaudosTecnicos\Models\Laudo_shares_model');
        $this->versions_model = model('LaudosTecnicos\Models\Laudo_versions_model');
        $this->notifications_model = model('LaudosTecnicos\Models\Laudo_notifications_model');
    }

    // ==================== VISUALIZAR DOCUMENTO ====================
    public function view($laudo_id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $laudo = $this->laudos_model->get_one($laudo_id);
        if (!$laudo) {
            app_redirect('laudos_tecnicos');
        }

        // Carregar seções
        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);

        // Configuração do documento
        $config = $this->document_model->get_for_laudo($laudo_id);
        if (!$config) {
            $config = (object)$this->document_model->get_default_config();
        }

        // Versão atual
        $version = $this->versions_model->get_current($laudo_id);

        $view_data = array(
            'laudo' => $laudo,
            'sections' => $sections,
            'config' => $config,
            'version' => $version
        );

        return $this->template->rander('LaudosTecnicos\Views\documents\view', $view_data);
    }

    public function render_html($laudo_id)
    {
        $laudo = $this->laudos_model->get_one($laudo_id);
        if (!$laudo) {
            return 'Laudo não encontrado';
        }

        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);

        $config = $this->document_model->get_for_laudo($laudo_id);
        if (!$config) {
            $config = (object)$this->document_model->get_default_config();
        }

        $version = $this->versions_model->get_current($laudo_id);

        // Gerar QR Code se habilitado
        $qrcode_html = '';
        if ($config->show_qrcode && $version) {
            $validation_url = base_url('laudo_documents/validate/' . $version->document_hash);
            $qrcode_file = \LaudosTecnicos\Models\Laudo_pdf_generator::generate_qrcode($validation_url);
            $qrcode_html = '<img src="' . base_url($qrcode_file) . '" style="width: 80px;">';
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo $laudo->laudo_number; ?> - <?php echo $laudo->title; ?></title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
                
                * { box-sizing: border-box; }
                
                body {
                    font-family: <?php echo $config->font_family; ?>;
                    font-size: <?php echo $config->font_size; ?>px;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                
                .page {
                    width: 210mm;
                    min-height: 297mm;
                    padding: <?php echo $config->margin_top; ?>mm <?php echo $config->margin_right; ?>mm;
                    margin: 10px auto;
                    background: white;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                
                .cover-page {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    text-align: center;
                    padding: 40mm 20mm;
                }
                
                h1 { color: <?php echo $config->primary_color; ?>; font-size: 24pt; margin-bottom: 10px; }
                h2 { color: <?php echo $config->primary_color; ?>; font-size: 18pt; border-bottom: 2px solid <?php echo $config->primary_color; ?>; padding-bottom: 5px; }
                h3 { color: <?php echo $config->secondary_color; ?>; font-size: 14pt; }
                
                .info-table { width: 100%; margin: 20px 0; }
                .info-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .info-table td:first-child { font-weight: bold; width: 30%; color: <?php echo $config->secondary_color; ?>; }
                
                .section { margin: 30px 0; page-break-inside: avoid; }
                .section-title { 
                    color: <?php echo $config->primary_color; ?>; 
                    border-bottom: 1px solid <?php echo $config->primary_color; ?>; 
                    padding-bottom: 5px;
                    margin-bottom: 15px;
                }
                
                .confidentiality {
                    position: fixed;
                    bottom: 10px;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 9pt;
                    color: #999;
                }
                
                .qrcode { position: fixed; top: 20px; right: 20px; }
                
                @media print {
                    body { margin: 0; }
                    .page { box-shadow: none; margin: 0; width: auto; height: auto; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <!-- Capa -->
            <div class="page cover-page">
                <h1>LAUDO TÉCNICO</h1>
                <h2><?php echo $laudo->title; ?></h2>
                <br>
                <table class="info-table">
                    <tr><td>Número:</td><td><?php echo $laudo->laudo_number; ?></td></tr>
                    <tr><td>Revisão:</td><td><?php echo $version ? str_pad($version->version, 2, '0', STR_PAD_LEFT) . '-' . str_pad($version->revision, 2, '0', STR_PAD_LEFT) : '00-00'; ?></td></tr>
                    <tr><td>Data:</td><td><?php echo date('d/m/Y', strtotime($laudo->created_at)); ?></td></tr>
                    <tr><td>Cliente:</td><td><?php echo $laudo->company_name; ?></td></tr>
                    <tr><td>Unidade:</td><td><?php echo $laudo->location; ?></td></tr>
                </table>
                <br><br>
                <p><strong>Responsável Técnico:</strong></p>
                <p><?php echo $laudo->technical_name ?? 'Não definido'; ?></p>
                <p><?php echo $laudo->council_type ?? ''; ?> <?php echo $laudo->council_number ?? ''; ?></p>
                <?php if ($qrcode_html): ?>
                <div class="qrcode"><?php echo $qrcode_html; ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Sumário -->
            <?php if ($config->show_toc): ?>
            <div class="page">
                <h2>Sumário</h2>
                <ul>
                <?php foreach ($sections as $section): ?>
                    <li><?php echo $section->name; ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Seções -->
            <?php foreach ($sections as $section): ?>
            <div class="page">
                <div class="section">
                    <h3 class="section-title"><?php echo $section->name; ?></h3>
                    <div><?php echo nl2br($section->value ?? '-'); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Assinaturas -->
            <div class="page">
                <h2>Assinaturas</h2>
                <p>Documento assinado eletronicamente em <?php echo date('d/m/Y H:i'); ?></p>
                <p>Hash: <?php echo $version ? substr($version->document_hash, 0, 32) . '...' : 'N/A'; ?></p>
            </div>
            
            <?php if ($config->confidentiality_text): ?>
            <div class="confidentiality"><?php echo $config->confidentiality_text; ?></div>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    // ==================== GERAR PDF ====================
    public function generate_pdf($laudo_id, $type = 'full')
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $laudo = $this->laudos_model->get_one($laudo_id);
        if (!$laudo) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Laudo não encontrado'));
        }

        $sections_model = model('LaudosTecnicos\Models\Laudo_sections_model');
        $sections = $sections_model->get_for_laudo($laudo_id);

        $config = $this->document_model->get_for_laudo($laudo_id);
        if (!$config) {
            $config = (object)$this->document_model->get_default_config();
        }

        $version = $this->versions_model->get_current($laudo_id);

        // Gerar PDF usando o helper
        $pdf_generator = new \LaudosTecnicos\Models\Laudo_pdf_generator($laudo, $sections, (array)$config, $version);
        $pdf_content = $pdf_generator->generate();

        // Salvar PDF
        $pdf_dir = 'uploads/laudos/' . $laudo_id . '/pdfs/';
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }

        $pdf_file = $pdf_dir . 'laudo_' . ($version ? $version->version . '_' . $version->revision : 'draft') . '_' . time() . '.pdf';
        file_put_contents($pdf_file, $pdf_content);

        // Atualizar versão com o caminho do PDF
        if ($version) {
            $this->versions_model->save(array('pdf_file' => $pdf_file), $version->id);
        }

        return $this->response->setJSON(array(
            'success' => true,
            'pdf_url' => base_url($pdf_file)
        ));
    }

    public function download_pdf($laudo_id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $version = $this->versions_model->get_current($laudo_id);
        
        if (!$version || !$version->pdf_file || !file_exists($version->pdf_file)) {
            // Gerar primeiro
            $this->generate_pdf($laudo_id);
            $version = $this->versions_model->get_current($laudo_id);
        }

        if ($version && $version->pdf_file && file_exists($version->pdf_file)) {
            $laudo = $this->laudos_model->get_one($laudo_id);
            $this->output
                ->set_content_type('application/pdf')
                ->set_output(file_get_contents($version->pdf_file))
                ->set_header('Content-Disposition: attachment; filename="laudo_' . $laudo->laudo_number . '.pdf"');
        } else {
            app_redirect('laudo_documents/view/' . $laudo_id);
        }
    }

    // ==================== CONFIGURAÇÃO ====================
    public function config($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $laudo = $this->laudos_model->get_one($laudo_id);
        
        $config = $this->document_model->get_for_laudo($laudo_id);
        if (!$config) {
            $config = (object)$this->document_model->get_default_config();
        }

        $view_data = array(
            'laudo' => $laudo,
            'config' => $config
        );

        return $this->template->rander('LaudosTecnicos\Views\documents\config', $view_data);
    }

    public function save_config()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $laudo_id = $this->request->getPost('laudo_id');
        
        $data = array(
            'laudo_id' => $laudo_id,
            'primary_color' => $this->request->getPost('primary_color') ?: '#007bff',
            'secondary_color' => $this->request->getPost('secondary_color') ?: '#6c757d',
            'font_family' => $this->request->getPost('font_family') ?: 'Arial, sans-serif',
            'font_size' => $this->request->getPost('font_size') ?: 12,
            'margin_top' => $this->request->getPost('margin_top') ?: 20,
            'margin_bottom' => $this->request->getPost('margin_bottom') ?: 20,
            'margin_left' => $this->request->getPost('margin_left') ?: 20,
            'margin_right' => $this->request->getPost('margin_right') ?: 20,
            'show_cover' => $this->request->getPost('show_cover') ? 1 : 0,
            'show_toc' => $this->request->getPost('show_toc') ? 1 : 0,
            'show_page_numbers' => $this->request->getPost('show_page_numbers') ? 1 : 0,
            'show_qrcode' => $this->request->getPost('show_qrcode') ? 1 : 0,
            'paper_size' => $this->request->getPost('paper_size') ?: 'A4',
            'orientation' => $this->request->getPost('orientation') ?: 'portrait',
            'confidentiality_text' => $this->request->getPost('confidentiality_text')
        );

        $existing = $this->document_model->get_for_laudo($laudo_id);
        $save_id = $this->document_model->save($data, $existing ? $existing->id : 0);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== COMPARTILHAMENTO ====================
    public function share($laudo_id)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $laudo = $this->laudos_model->get_one($laudo_id);
        $shares = $this->shares_model->get_for_laudo($laudo_id);

        $view_data = array(
            'laudo' => $laudo,
            'shares' => $shares
        );

        return $this->template->rander('LaudosTecnicos\Views\documents\share', $view_data);
    }

    public function create_share()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $laudo_id = $this->request->getPost('laudo_id');
        $version = $this->versions_model->get_current($laudo_id);

        $data = array(
            'laudo_id' => $laudo_id,
            'version' => $version ? $version->version : 1,
            'password' => $this->request->getPost('password') ?: null,
            'expires_at' => $this->request->getPost('expires_at') ?: null,
            'max_accesses' => $this->request->getPost('max_accesses') ?: null,
            'allow_download' => $this->request->getPost('allow_download') ? 1 : 0,
            'allow_comments' => $this->request->getPost('allow_comments') ? 1 : 0,
            'visitor_name' => $this->request->getPost('visitor_name'),
            'visitor_email' => $this->request->getPost('visitor_email'),
            'created_by' => $this->login_user->id
        );

        $share_id = $this->shares_model->save($data, 0);

        if ($share_id) {
            $share = $this->shares_model->get_one($share_id);
            
            // Enviar notificação se email informado
            if ($data['visitor_email']) {
                $laudo = $this->laudos_model->get_one($laudo_id);
                $link = base_url('laudo_documents/public_view/' . $share->token);
                
                // Aqui poderia enviar email
                $this->notifications_model->create(
                    $laudo_id,
                    'share_created',
                    'Laudo Compartilhado',
                    'Laudo ' . $laudo->laudo_number . ' foi compartilhado com ' . $data['visitor_email'],
                    null,
                    $data['visitor_email']
                );
            }

            return $this->response->setJSON(array(
                'success' => true,
                'share_id' => $share_id,
                'share_url' => base_url('laudo_documents/public_view/' . $share->token)
            ));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function revoke_share($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $this->shares_model->revoke($id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Compartilhamento revogado'));
    }

    // ==================== VISUALIZAÇÃO PÚBLICA ====================
    public function public_view($token)
    {
        $share = $this->shares_model->get_by_token($token);
        
        if (!$share) {
            show_error('Link inválido ou expirado', 404);
        }

        if (!$share->active) {
            show_error('Este link foi revogado', 403);
        }

        if ($share->expires_at && strtotime($share->expires_at) < time()) {
            show_error('Este link expirou', 403);
        }

        if ($share->max_accesses && $share->current_accesses >= $share->max_accesses) {
            show_error('Limite de acessos atingido', 403);
        }

        // Verificar senha
        if ($share->password) {
            // Sessão para validar senha
            if (!session()->get('laudo_share_' . $share->id)) {
                $view_data = array('share' => $share, 'token' => $token);
                return view('LaudosTecnicos\Views\documents\password', $view_data);
            }
        }

        // Incrementar acesso
        $this->shares_model->increment_access($share->id);
        $this->shares_model->log_access($share->id, 'view');

        $laudo = $this->laudos_model->get_one($share->laudo_id);
        
        $view_data = array(
            'laudo' => $laudo,
            'share' => $share,
            'allow_download' => $share->allow_download,
            'allow_comments' => $share->allow_comments
        );

        return view('LaudosTecnicos\Views\documents\public_view', $view_data);
    }

    public function verify_password($token)
    {
        $share = $this->shares_model->get_by_token($token);
        
        if (!$share) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Link inválido'));
        }

        $password = $this->request->getPost('password');
        
        if (password_verify($password, $share->password)) {
            session()->set('laudo_share_' . $share->id, true);
            return $this->response->setJSON(array('success' => true));
        }

        return $this->response->setJSON(array('success' => false, 'message' => 'Senha incorreta'));
    }

    // ==================== VALIDAÇÃO PÚBLICA ====================
    public function validate($hash)
    {
        $version = $this->versions_model->get_one($hash);
        
        if (!$version) {
            // Buscar por hash no documento
            $table = $this->db->prefixTable('laudo_versions');
            $result = $this->db->query("SELECT * FROM $table WHERE document_hash LIKE '$hash%' LIMIT 1")->getRow();
            
            if (!$result) {
                show_error('Documento não encontrado', 404);
            }
            $version = $result;
        }

        $laudo = $this->laudos_model->get_one($version->laudo_id);

        $view_data = array(
            'laudo' => $laudo,
            'version' => $version,
            'is_valid' => $version && $version->status === 'published'
        );

        return view('LaudosTecnicos\Views\documents\validation', $view_data);
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