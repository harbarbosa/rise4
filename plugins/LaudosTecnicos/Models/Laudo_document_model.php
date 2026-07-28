<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;
use TCPDF;

class Laudo_document_model extends Crud_model
{
    protected $table = 'laudo_document_config';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        
        return parent::ci_save($data, $id);
    }

    public function get_default_config()
    {
        return array(
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
            'font_family' => 'Arial, sans-serif',
            'font_size' => 12,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 20,
            'margin_right' => 20,
            'show_cover' => 1,
            'show_toc' => 1,
            'show_page_numbers' => 1,
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'show_qrcode' => 1,
            'confidentiality_text' => 'Documento confidencial. Reprodução vedada sem autorização.'
        );
    }
}

class Laudo_shares_model extends Crud_model
{
    protected $table = 'laudo_shares';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND active=1 ORDER BY created_at DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_by_token($token)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE token='$token' AND active=1 LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
            $data['token'] = bin2hex(random_bytes(32));
        }
        
        return parent::ci_save($data, $id);
    }

    public function increment_access($id)
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query("UPDATE $table SET current_accesses = current_accesses + 1 WHERE id=$id");
    }

    public function revoke($id)
    {
        return parent::ci_save(array('active' => 0), $id);
    }

    public function log_access($share_id, $action)
    {
        $logs_model = model(Laudo_share_logs_model::class);
        $logs_model->log($share_id, $action);
    }
}

class Laudo_share_logs_model extends Crud_model
{
    protected $table = 'laudo_share_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log($share_id, $action)
    {
        $data = array(
            'share_id' => $share_id,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'visited_at' => get_my_local_time()
        );
        
        return parent::ci_save($data, 0);
    }

    public function get_for_share($share_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE share_id=$share_id ORDER BY visited_at DESC";
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_client_responses_model extends Crud_model
{
    protected $table = 'laudo_client_responses';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id ORDER BY created_at DESC";
        return $this->db->query($sql)->getResult();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        
        if (!$id) {
            $data['created_at'] = get_my_local_time();
        }
        
        return parent::ci_save($data, $id);
    }

    public function respond($id, $status, $comment)
    {
        $data = array(
            'status' => $status,
            'comment' => $comment,
            'responded_at' => get_my_local_time()
        );
        
        return parent::ci_save($data, $id);
    }
}

class Laudo_notifications_model extends Crud_model
{
    protected $table = 'laudo_notifications';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function create($laudo_id, $type, $title, $message, $user_id = null, $sent_to = null)
    {
        $data = array(
            'laudo_id' => $laudo_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'user_id' => $user_id,
            'sent_to' => $sent_to,
            'created_at' => get_my_local_time()
        );
        
        return parent::ci_save($data, 0);
    }

    public function get_for_user($user_id, $limit = 20)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE user_id=$user_id ORDER BY created_at DESC LIMIT $limit";
        return $this->db->query($sql)->getResult();
    }

    public function get_unread_count($user_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT COUNT(*) as count FROM $table WHERE user_id=$user_id AND `read`=0";
        return $this->db->query($sql)->getRow()->count;
    }

    public function mark_as_read($id)
    {
        return parent::ci_save(array('read' => 1), $id);
    }
}

// Helper para gerar PDF
class Laudo_pdf_generator
{
    private $laudo;
    private $sections;
    private $config;
    private $version;

    public function __construct($laudo, $sections = array(), $config = array(), $version = null)
    {
        $this->laudo = $laudo;
        $this->sections = $sections;
        $this->config = array_merge(array(
            'primary_color' => '#007bff',
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'margin' => 15
        ), $config);
        $this->version = $version;
    }

    public function generate()
    {
        // Criar PDF
        $pdf = new TCPDF(
            $this->config['orientation'] === 'landscape' ? 'L' : 'P',
            'mm',
            $this->config['paper_size'],
            true,
            'UTF-8',
            false
        );

        // Configurações
        $pdf->SetCreator('RISE CRM - Laudos Técnicos');
        $pdf->SetAuthor($this->laudo->responsible_name ?? 'Sistema');
        $pdf->SetTitle($this->laudo->title ?? 'Laudo Técnico');
        $pdf->SetSubject($this->laudo->laudo_number ?? '');

        // Margens
        $pdf->SetMargins($this->config['margin'], $this->config['margin'], $this->config['margin']);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Página em branco
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Adicionar página
        $pdf->AddPage();

        // Capa
        $this->renderCover($pdf);

        // Sumário
        $pdf->AddPage();
        $this->renderTOC($pdf);

        // Conteúdo
        foreach ($this->sections as $section) {
            $pdf->AddPage();
            $this->renderSection($pdf, $section);
        }

        return $pdf->Output('', 'S');
    }

    private function renderCover($pdf)
    {
        $html = '
        <div style="text-align: center; padding-top: 100px;">
            <h1 style="color: ' . $this->config['primary_color'] . '; font-size: 24pt;">LAUDO TÉCNICO</h1>
            <h2 style="color: #666; font-size: 18pt;">' . ($this->laudo->title ?? '') . '</h2>
            <br><br>
            <p><strong>Número:</strong> ' . ($this->laudo->laudo_number ?? '-') . '</p>
            <p><strong>Revisão:</strong> ' . ($this->version ? $this->version->version . '-' . $this->version->revision : '00-00') . '</p>
            <p><strong>Data:</strong> ' . ($this->laudo->created_at ?? date('d/m/Y')) . '</p>
            <br><br>
            <p><strong>Cliente:</strong><br>' . ($this->laudo->company_name ?? '-') . '</p>
            <p><strong>Unidade:</strong><br>' . ($this->laudo->location ?? '-') . '</p>
            <br><br>
            <p><strong>Responsável Técnico:</strong><br>' . ($this->laudo->technical_name ?? '-') . '</p>
            <p><strong>CREA:</strong> ' . ($this->laudo->council_number ?? '-') . '</p>
        </div>
        ';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function renderTOC($pdf)
    {
        $html = '
        <h2 style="color: ' . $this->config['primary_color'] . ';">Sumário</h2>
        <ul>
        ';
        
        foreach ($this->sections as $section) {
            $html .= '<li>' . $section->name . '</li>';
        }
        
        $html .= '</ul>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function renderSection($pdf, $section)
    {
        $html = '
        <h2 style="color: ' . $this->config['primary_color'] . '; border-bottom: 1px solid ' . $this->config['primary_color'] . ';">' . $section->name . '</h2>
        <div style="padding: 10px 0;">
            ' . nl2br($section->value ?? '-') . '
        </div>
        ';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    public static function generate_qrcode($data, $size = 100)
    {
        require_once(APPPATH . 'ThirdParty/phpqrcode/qrlib.php');
        
        $qrcode_file = 'uploads/laudos/qrcodes/' . uniqid() . '.png';
        if (!is_dir('uploads/laudos/qrcodes')) {
            mkdir('uploads/laudos/qrcodes', 0755, true);
        }
        
        QRcode::png($data, $qrcode_file, 'M', $size, 2);
        
        return $qrcode_file;
    }
}