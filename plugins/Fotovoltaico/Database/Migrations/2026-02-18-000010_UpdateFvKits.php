<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajustes nas tabelas de kits e itens.
 */
class UpdateFvKits extends Migration
{
    public function up()
    {
        $kits = $this->db->prefixTable('fv_kits');
        if ($this->db->tableExists($kits)) {
            if (!$this->db->fieldExists('default_losses_percent', $kits)) {
                $this->forge->addColumn('fv_kits', [
                    'default_losses_percent' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 14.00]
                ]);
                if ($this->db->fieldExists('default_losses', $kits)) {
                    $this->db->query("UPDATE {$kits} SET default_losses_percent = default_losses");
                }
            }
            if (!$this->db->fieldExists('default_markup_percent', $kits)) {
                $this->forge->addColumn('fv_kits', [
                    'default_markup_percent' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0.00]
                ]);
            }
            if (!$this->db->fieldExists('is_active', $kits)) {
                $this->forge->addColumn('fv_kits', [
                    'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1]
                ]);
            }
            if (!$this->db->fieldExists('created_by', $kits)) {
                $this->forge->addColumn('fv_kits', [
                    'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true]
                ]);
            }
            if (!$this->db->fieldExists('created_at', $kits)) {
                $this->db->query("ALTER TABLE {$kits} ADD COLUMN created_at DATETIME NULL");
            }
            if (!$this->db->fieldExists('updated_at', $kits)) {
                $this->db->query("ALTER TABLE {$kits} ADD COLUMN updated_at DATETIME NULL");
            }
            $this->tryAddIndex($kits, 'idx_active', 'is_active');
            $this->tryAddIndex($kits, 'idx_name', 'name');
        }

        $items = $this->db->prefixTable('fv_kit_items');
        if ($this->db->tableExists($items)) {
            if ($this->db->fieldExists('product_id', $items)) {
                try {
                    $this->db->query("ALTER TABLE {$items} MODIFY product_id INT(11) NULL");
                } catch (\Throwable $e) {
                    // ignora
                }
            }
            if (!$this->db->fieldExists('item_type', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'item_type' => ['type' => 'ENUM', 'constraint' => ['product', 'custom'], 'default' => 'product']
                ]);
            }
            if (!$this->db->fieldExists('name', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true]
                ]);
            }
            if (!$this->db->fieldExists('description', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
                ]);
            }
            if (!$this->db->fieldExists('qty', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'qty' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 1]
                ]);
                if ($this->db->fieldExists('quantity', $items)) {
                    $this->db->query("UPDATE {$items} SET qty = quantity");
                }
            }
            if (!$this->db->fieldExists('unit', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'unit' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true]
                ]);
            }
            if (!$this->db->fieldExists('cost', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'cost' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0]
                ]);
            }
            if (!$this->db->fieldExists('price', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0]
                ]);
            }
            if (!$this->db->fieldExists('sort_order', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0]
                ]);
            }
            if (!$this->db->fieldExists('rule_json', $items)) {
                $this->forge->addColumn('fv_kit_items', [
                    'rule_json' => ['type' => 'LONGTEXT', 'null' => true]
                ]);
            }
            if (!$this->db->fieldExists('created_at', $items)) {
                $this->db->query("ALTER TABLE {$items} ADD COLUMN created_at DATETIME NULL");
            }
            if (!$this->db->fieldExists('updated_at', $items)) {
                $this->db->query("ALTER TABLE {$items} ADD COLUMN updated_at DATETIME NULL");
            }
            $this->tryAddIndex($items, 'idx_kit', 'kit_id');
            $this->tryAddIndex($items, 'idx_sort', 'kit_id, sort_order');
        }
    }

    public function down()
    {
        // Sem reversão automática para evitar perda de dados
    }

    private function tryAddIndex($table, $name, $fields)
    {
        try {
            $this->db->query("CREATE INDEX {$name} ON {$table} ({$fields})");
        } catch (\Throwable $e) {
            // ignora se já existir
        }
    }
}
