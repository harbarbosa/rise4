<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Atualizar extends Controller
{
    private $token_seguranca = 'rise_atualizar_2024';
    
    public function __construct()
    {
        helper(['url', 'text', 'function']);
    }

    public function index()
    {
        // Verificar token de segurança
        $token = $this->request->getGet('token') ?? $this->request->getPost('token');
        
        if ($token !== $this->token_seguranca) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Token de segurança inválido'
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
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Token de segurança inválido'
            ]);
        }

        $db = db_connect();
        
        // Obter o prefixo das tabelas
        $dbprefix = $db->getPrefix();
        
        $results = [];
        
        // 1. Verificar e adicionar coluna description na tabela de seções
        try {
            $fields = $db->getFieldNames($dbprefix . 'proposal_sections_custom');
            if (!in_array('description', $fields)) {
                $sql = "ALTER TABLE `{$dbprefix}proposal_sections_custom` ADD COLUMN `description` TEXT NULL AFTER `title`";
                $db->query($sql);
                $results[] = 'Coluna description adicionada em proposal_sections_custom';
            } else {
                $results[] = 'Coluna description já existe em proposal_sections_custom';
            }
        } catch (\Exception $e) {
            $results[] = 'Erro proposal_sections_custom: ' . $e->getMessage();
        }
        
        // 2. Verificar e adicionar coluna item_type na tabela de itens
        try {
            $fields = $db->getFieldNames($dbprefix . 'items');
            if (!in_array('item_type', $fields)) {
                $sql = "ALTER TABLE `{$dbprefix}items` ADD COLUMN `item_type` VARCHAR(20) NOT NULL DEFAULT 'material' AFTER `markup`";
                $db->query($sql);
                $results[] = 'Coluna item_type adicionada em items';
            } else {
                $results[] = 'Coluna item_type já existe em items';
            }
        } catch (\Exception $e) {
            $results[] = 'Erro items: ' . $e->getMessage();
        }

        // 3. Verificar e adicionar coluna proposal_id na tabela de projetos
        try {
            $fields = $db->getFieldNames($dbprefix . 'projects');
            if (!in_array('proposal_id', $fields)) {
                $sql = "ALTER TABLE `{$dbprefix}projects` ADD COLUMN `proposal_id` INT(11) NULL AFTER `client_id`";
                $db->query($sql);
                $results[] = 'Coluna proposal_id adicionada em projects';
            } else {
                $results[] = 'Coluna proposal_id já existe em projects';
            }
        } catch (\Exception $e) {
            $results[] = 'Erro projects: ' . $e->getMessage();
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => implode('. ', $results)
        ]);
    }
}