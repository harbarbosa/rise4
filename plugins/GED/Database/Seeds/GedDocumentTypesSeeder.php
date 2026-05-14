<?php

namespace GED\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GedDocumentTypesSeeder extends Seeder
{
    public function run()
    {
        $table = $this->db->prefixTable('ged_document_types');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $types = array(
            array('name' => 'ASO', 'has_expiration' => 1, 'description' => 'Atestado de Saude Ocupacional'),
            array('name' => 'NR6', 'has_expiration' => 1, 'description' => 'Equipamento de Protecao Individual'),
            array('name' => 'NR7', 'has_expiration' => 1, 'description' => 'Programa de Controle Medico de Saude Ocupacional'),
            array('name' => 'NR9', 'has_expiration' => 1, 'description' => 'Programa de Prevencao de Riscos Ambientais'),
            array('name' => 'NR10', 'has_expiration' => 1, 'description' => 'Seguranca em instalacoes e servicos em eletricidade'),
            array('name' => 'NR35', 'has_expiration' => 1, 'description' => 'Trabalho em altura'),
            array('name' => 'RG', 'has_expiration' => 0, 'description' => 'Documento de identificacao'),
            array('name' => 'CPF', 'has_expiration' => 0, 'description' => 'Cadastro de Pessoa Fisica'),
            array('name' => 'CNH', 'has_expiration' => 1, 'description' => 'Carteira Nacional de Habilitacao'),
            array('name' => 'PIS', 'has_expiration' => 0, 'description' => 'Programa de Integracao Social'),
            array('name' => 'CTPS', 'has_expiration' => 0, 'description' => 'Carteira de Trabalho e Previdencia Social'),
            array('name' => 'Contrato Social', 'has_expiration' => 0, 'description' => 'Documento societario da empresa'),
            array('name' => 'Cartao CNPJ', 'has_expiration' => 0, 'description' => 'Cadastro Nacional da Pessoa Juridica'),
            array('name' => 'Inscricao Estadual', 'has_expiration' => 0, 'description' => 'Inscricao estadual da empresa'),
            array('name' => 'Inscricao Municipal', 'has_expiration' => 0, 'description' => 'Inscricao municipal da empresa'),
            array('name' => 'Certidao Federal', 'has_expiration' => 1, 'description' => 'Regularidade federal'),
            array('name' => 'Certidao FGTS', 'has_expiration' => 1, 'description' => 'Regularidade do FGTS'),
            array('name' => 'Certidao Estadual', 'has_expiration' => 1, 'description' => 'Regularidade estadual'),
            array('name' => 'Certidao Municipal', 'has_expiration' => 1, 'description' => 'Regularidade municipal'),
            array('name' => 'Certidao Trabalhista', 'has_expiration' => 1, 'description' => 'Regularidade trabalhista'),
            array('name' => 'Alvara de Funcionamento', 'has_expiration' => 1, 'description' => 'Licenca de funcionamento'),
            array('name' => 'Licenca Sanitaria', 'has_expiration' => 1, 'description' => 'Licenca sanitaria'),
            array('name' => 'Licenca Ambiental', 'has_expiration' => 1, 'description' => 'Licenca ambiental'),
            array('name' => 'AVCB', 'has_expiration' => 1, 'description' => 'Auto de Vistoria do Corpo de Bombeiros'),
            array('name' => 'Apolice de Seguro', 'has_expiration' => 1, 'description' => 'Seguro vigente da empresa ou do contrato'),
            array('name' => 'ART', 'has_expiration' => 1, 'description' => 'Anotacao de Responsabilidade Tecnica'),
            array('name' => 'Laudo Tecnico', 'has_expiration' => 1, 'description' => 'Laudo tecnico operacional ou pericial'),
            array('name' => 'LTCAT', 'has_expiration' => 1, 'description' => 'Laudo Tecnico das Condicoes Ambientais do Trabalho'),
            array('name' => 'PPP', 'has_expiration' => 0, 'description' => 'Perfil Profissiografico Previdenciario'),
            array('name' => 'CAT', 'has_expiration' => 0, 'description' => 'Comunicacao de Acidente de Trabalho'),
            array('name' => 'Ficha de EPI', 'has_expiration' => 0, 'description' => 'Controle de entrega de EPI'),
            array('name' => 'Certificado de Treinamento', 'has_expiration' => 1, 'description' => 'Comprovante de capacitacao ou treinamento'),
            array('name' => 'Comprovante de Vacina', 'has_expiration' => 1, 'description' => 'Comprovante de imunizacao'),
            array('name' => 'Contrato de Prestacao de Servicos', 'has_expiration' => 0, 'description' => 'Contrato com fornecedor ou parceiro'),
        );

        $existing = $this->db->table($table)->select('id, name')->get()->getResultArray();
        $name_map = array();
        foreach ($existing as $row) {
            $name_map[strtolower(trim((string) $row['name']))] = (int) $row['id'];
        }

        $now = date('Y-m-d H:i:s');
        foreach ($types as $type) {
            $payload = array(
                'name' => $type['name'],
                'description' => $type['description'],
                'has_expiration' => (int) $type['has_expiration'],
                'is_active' => 1,
                'updated_at' => $now,
            );

            $key = strtolower(trim((string) $type['name']));
            if (isset($name_map[$key])) {
                $this->db->table($table)->where('id', $name_map[$key])->update($payload);
                continue;
            }

            $payload['created_at'] = $now;
            $this->db->table($table)->insert($payload);
        }
    }
}
