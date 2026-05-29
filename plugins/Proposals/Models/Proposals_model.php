<?php

namespace Proposals\Models;

use App\Models\Crud_model;

class Proposals_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'proposals_custom';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('proposals_custom');
        $clients_table = $this->db->prefixTable('clients');
        $users_table = $this->db->prefixTable('users');
        if (!$this->db->tableExists($table)) {
            return $this->db->query("SELECT 1 AS __empty WHERE 1=0");
        }

        $fields = $this->db->getFieldNames($table) ?: [];
        $has_company_id = in_array('company_id', $fields, true);
        $has_client_id = in_array('client_id', $fields, true);
        $has_status = in_array('status', $fields, true);
        $has_title = in_array('title', $fields, true);
        $has_client_name = in_array('client_name', $fields, true);
        $has_created_at = in_array('created_at', $fields, true);
        $has_created_by = in_array('created_by', $fields, true);

        $where = "";
        $base_where = in_array('deleted', $fields, true) ? "$table.deleted=0" : "1=1";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id && $has_company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id && $has_client_id) {
            $where .= " AND $table.client_id=$client_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status && $has_status) {
            $where .= " AND $table.status='$status'";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search && $has_title) {
            $search = $this->db->escapeLikeString($search);
            $search_parts = ["$table.title LIKE '%$search%' ESCAPE '!'"];
            if ($has_client_name) {
                $search_parts[] = "$table.client_name LIKE '%$search%' ESCAPE '!'";
            }
            $where .= " AND (" . implode(' OR ', $search_parts) . ")";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($start_date && $end_date && $has_created_at) {
            $where .= " AND ($table.created_at BETWEEN '$start_date' AND '$end_date')";
        } else if ($start_date && $has_created_at) {
            $where .= " AND $table.created_at >= '$start_date'";
        } else if ($end_date && $has_created_at) {
            $where .= " AND $table.created_at <= '$end_date'";
        }

        $select_parts = ["$table.*"];
        if ($has_client_id) {
            $select_parts[] = "$clients_table.company_name AS client_company";
        }
        if ($has_created_by) {
            $select_parts[] = "CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_name";
        }

        $sql = "SELECT " . implode(", ", $select_parts) . "
        FROM $table";
        if ($has_client_id) {
            $sql .= " LEFT JOIN $clients_table ON $clients_table.id=$table.client_id";
        }
        if ($has_created_by) {
            $sql .= " LEFT JOIN $users_table ON $users_table.id=$table.created_by";
        }
        $sql .= " WHERE " . $base_where . $where;
        $sql .= " ORDER BY $table.id DESC";

        return $this->db->query($sql);
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
        return parent::delete($id, $undo);
    }

    public function calculate_totals($proposal_id)
    {
        $proposal_id = (int)$proposal_id;
        if (!$proposal_id) {
            return false;
        }

        $items_table = $this->db->prefixTable('proposal_items_custom');
        $proposal_table = $this->db->prefixTable('proposals_custom');

        $items = $this->db->query("SELECT * FROM $items_table WHERE $items_table.deleted=0 AND $items_table.proposal_id=$proposal_id AND $items_table.in_memory=1")->getResult();

        $total_cost_material = 0;
        $total_cost_service = 0;
        $total_sale = 0;
        $total_sale_material = 0;
        $total_sale_service = 0;

        foreach ($items as $item) {
            $qty = (float)$item->qty;
            $cost_unit = (float)$item->cost_unit;
            $markup_percent = (float)$item->markup_percent;
            $sale_unit = (float)$item->sale_unit;

            $cost_total = $qty * $cost_unit;
            $sale_unit_calc = $sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + ($markup_percent / 100)));
            $sale_total = $qty * $sale_unit_calc;

            if ($item->item_type === 'service') {
                $total_cost_service += $cost_total;
                $total_sale_service += $sale_total;
            } else {
                $total_cost_material += $cost_total;
                $total_sale_material += $sale_total;
            }
            $total_sale += $sale_total;
        }

        $proposal = $this->get_one($proposal_id);
        $taxes_total = 0;
        $commission_total = 0;

        $tax_product_percent = $proposal && isset($proposal->tax_product_percent) ? (float)$proposal->tax_product_percent : 0;
        $tax_service_percent = $proposal && isset($proposal->tax_service_percent) ? (float)$proposal->tax_service_percent : 0;
        $tax_service_only = $proposal && isset($proposal->tax_service_only) ? (int)$proposal->tax_service_only : 0;

        if ($tax_service_only) {
            if ($tax_service_percent > 0) {
                $taxes_total = $total_sale * ($tax_service_percent / 100);
            }
        } else {
            if ($tax_product_percent > 0) {
                $taxes_total += $total_sale_material * ($tax_product_percent / 100);
            }
            if ($tax_service_percent > 0) {
                $taxes_total += $total_sale_service * ($tax_service_percent / 100);
            }
        }

        if ($proposal) {
            if ($proposal->commission_type === 'percent') {
                $commission_total = $total_sale * ((float)$proposal->commission_value / 100);
            } else {
                $commission_total = (float)$proposal->commission_value;
            }
        }

        $profit_gross = $total_sale - $total_cost_material - $total_cost_service - $taxes_total;
        $profit_net = $profit_gross - $commission_total;

        $data = array(
            'total_cost_material' => $total_cost_material,
            'total_cost_service' => $total_cost_service,
            'total_sale' => $total_sale,
            'taxes_total' => $taxes_total,
            'commission_total' => $commission_total,
            'profit_gross' => $profit_gross,
            'profit_net' => $profit_net
        );

        return $this->ci_save($data, $proposal_id) ? true : false;
    }
}
