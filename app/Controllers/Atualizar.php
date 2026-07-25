<?php

namespace App\Controllers;

use App\Controllers\Security_Controller;

class Atualizar extends Security_Controller
{
    private $token_seguranca = 'rise_atualizar_2024';
    
    public function __construct()
    {
        parent::__construct(true);
    }

    public function index()
    {
        // Verificar token de segurança
        $token = $this->request->getGet('token') ?? $this->request->getPost('token');
        
        if ($token !== $this->token_seguranca) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Token de segurança inválido'
            ]);
        }

        // Verificar se é admin
        if (!isset($this->login_user->id) || !$this->access_only_admin()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores.'
            ]);
        }

        $result = [
            'success' => true,
            'steps' => []
        ];

        // Passo 1: Git Pull
        $output = [];
        $return_var = 0;
        exec('cd ' . ROOTPATH . ' && git fetch origin main 2>&1', $output, $return_var);
        
        $result['steps'][] = [
            'step' => 'git fetch',
            'success' => $return_var === 0,
            'output' => implode("\n", $output)
        ];

        if ($return_var === 0) {
            // Passo 2: Git Pull
            $output = [];
            exec('cd ' . ROOTPATH . ' && git reset --hard origin/main 2>&1', $output, $return_var);
            
            $result['steps'][] = [
                'step' => 'git reset --hard',
                'success' => $return_var === 0,
                'output' => implode("\n", $output)
            ];

            // Passo 3: Executar install.php do plugin Proposals se existir
            $proposals_install = ROOTPATH . 'plugins/Proposals/install.php';
            if (file_exists($proposals_install)) {
                $output = [];
                exec('php ' . $proposals_install . ' 2>&1', $output, $return_var);
                
                $result['steps'][] = [
                    'step' => 'Proposals install.php',
                    'success' => $return_var === 0,
                    'output' => implode("\n", $output)
                ];
            }
        }

        $result['message'] = $result['success'] ? 'Sistema atualizado com sucesso!' : 'Erro ao atualizar sistema';

        return $this->response->setJSON($result);
    }

    // Rota para executar apenas o SQL de atualização do banco
    public function banco()
    {
        $token = $this->request->getGet('token') ?? $this->request->getPost('token');
        
        if ($token !== $this->token_seguranca) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Token de segurança inválido'
            ]);
        }

        if (!isset($this->login_user->id) || !$this->access_only_admin()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado'
            ]);
        }

        $db = db_connect();
        $dbprefix = get_db_prefix();
        
        // Verificar se a coluna já existe
        $fields = $db->getFieldNames($dbprefix . 'proposal_sections_custom');
        
        if (!in_array('description', $fields)) {
            $sql = "ALTER TABLE `{$dbprefix}proposal_sections_custom` ADD COLUMN `description` TEXT NULL AFTER `title`";
            try {
                $db->query($sql);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Coluna description adicionada com sucesso!'
                ]);
            } catch (\Exception $e) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Erro: ' . $e->getMessage()
                ]);
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Coluna description já existe'
        ]);
    }
}