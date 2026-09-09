<?php

namespace Proposals\Models;

use App\Models\Crud_model;
use Proposals\Libraries\Product_composition;

class Proposal_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'proposal_items_custom';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('proposal_items_custom');
        $items_table = $this->db->prefixTable('items');
        $services_table = $this->db->prefixTable('os_servicos');
        $has_services = $this->_table_exists($services_table);
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $proposal_id = $this->_get_clean_value($options, "proposal_id");
        if ($proposal_id) {
            $where .= " AND $table.proposal_id=$proposal_id";
        }

        $section_id = $this->_get_clean_value($options, "section_id");
        if ($section_id) {
            $where .= " AND $table.section_id=$section_id";
        }

        $in_memory = $this->_get_clean_value($options, "in_memory");
        if ($in_memory !== null && $in_memory !== "") {
            $in_memory = (int)$in_memory;
            $where .= " AND $table.in_memory=$in_memory";
        }

        $show_in_proposal = $this->_get_clean_value($options, "show_in_proposal");
        if ($show_in_proposal !== null && $show_in_proposal !== "") {
            $show_in_proposal = (int)$show_in_proposal;
            $where .= " AND $table.show_in_proposal=$show_in_proposal";
        }

        $select_title = $has_services
            ? "CASE WHEN $table.item_type='service' THEN $services_table.descricao ELSE $items_table.title END AS item_title"
            : "$items_table.title AS item_title";
        $select_unit = "CASE WHEN $table.item_type='service' THEN '' ELSE $items_table.unit_type END AS item_unit";
        $components_table = $this->db->prefixTable('item_components_custom');
        $select_kit = $this->_table_exists($components_table)
            ? ", CASE WHEN $table.item_type!='service' AND EXISTS(SELECT 1 FROM $components_table ic WHERE ic.parent_item_id=$table.item_id AND ic.deleted=0) THEN 1 ELSE 0 END AS is_kit"
            : ", 0 AS is_kit";

        $sql = "SELECT $table.*, $select_title, $select_unit $select_kit
        FROM $table
        LEFT JOIN $items_table ON $items_table.id=$table.item_id";

        if ($has_services) {
            $sql .= " LEFT JOIN $services_table ON $services_table.id=$table.item_id";
        }

        $sql .= " WHERE $table.deleted=0 $where
        ORDER BY $table.sort ASC, $table.id ASC";

        return $this->db->query($sql);
    }

    public function ci_save(&$data = array(), $id = 0)
    {
        $result = parent::ci_save($data, $id);
        if ($result) {
            $proposal_item_id = $id ? (int)$id : (int)$result;
            try {
                $composition = new Product_composition();
                $composition->sync_proposal_item($proposal_item_id);
            } catch (\Throwable $e) {
                log_message('error', '[Proposals] Falha ao sincronizar composição do item da proposta: ' . $e->getMessage());
            }
        }
        return $result;
    }

    private function _table_exists($table)
    {
        $query = $this->db->query("SHOW TABLES LIKE " . $this->db->escape($table));
        return $query && $query->getRow() ? true : false;
    }

    public function get_one($id = 0)
    {
        return parent::get_one($id);
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }

        return $this->ci_save($row, $id) ? true : false;
    }

    public function delete($id = 0, $undo = false)
    {
        $result = parent::delete($id, $undo);
        if ($result) {
            try {
                $composition = new Product_composition();
                if ($undo) {
                    $composition->sync_proposal_item((int)$id);
                } else {
                    $composition->clear_proposal_item((int)$id);
                }
            } catch (\Throwable $e) {
                log_message('error', '[Proposals] Falha ao limpar composição do item da proposta: ' . $e->getMessage());
            }
        }
        return $result;
    }
}
