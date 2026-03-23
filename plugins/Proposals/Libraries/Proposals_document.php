<?php

namespace Proposals\Libraries;

class Proposals_document
{
    private $sections_by_parent = array();
    private $items_by_section = array();
    private $mode = "detailed";
    private $show_values = true;

    public function render($proposal, $sections = array(), $items = array(), $mode = "detailed")
    {
        $this->mode = in_array($mode, array("detailed", "partial", "total_only"), true) ? $mode : "detailed";
        $this->show_values = ($this->mode === "detailed");

        $this->prepare_sections($sections);
        $this->prepare_items($items);

        $html = "<div class='proposal-doc proposal-doc-alfahp'>";
        $html .= $this->render_style();
        $html .= $this->render_header($proposal);
        $html .= $this->render_vendor_client($proposal);
        $html .= $this->render_items_tables($proposal);
        $html .= $this->render_scope_block($proposal);
        $html .= "</div>";

        return $html;
    }

    public function render_pdf($proposal, $sections = array(), $items = array(), $mode = "detailed")
    {
        $this->mode = in_array($mode, array("detailed", "partial", "total_only"), true) ? $mode : "detailed";
        $this->show_values = ($this->mode === "detailed");

        $this->prepare_sections($sections);
        $this->prepare_items($items);

        $html = "<div style='font-family: Arial, sans-serif; font-size:12px; color:#111;'>";
        $html .= $this->render_header_pdf($proposal);
        $html .= $this->render_vendor_client_pdf($proposal);
        $html .= $this->render_items_tables_pdf($proposal);
        $html .= $this->render_scope_block_pdf($proposal);
        $html .= "</div>";

        return $html;
    }

    private function prepare_sections($sections)
    {
        $this->sections_by_parent = array();
        foreach ($sections as $section) {
            $parent_id = isset($section->parent_id) && $section->parent_id ? (int)$section->parent_id : 0;
            if (!isset($this->sections_by_parent[$parent_id])) {
                $this->sections_by_parent[$parent_id] = array();
            }
            $this->sections_by_parent[$parent_id][] = $section;
        }

        foreach ($this->sections_by_parent as $parent_id => $list) {
            usort($list, function ($a, $b) {
                $as = isset($a->sort) ? (int)$a->sort : 0;
                $bs = isset($b->sort) ? (int)$b->sort : 0;
                if ($as === $bs) {
                    return (int)$a->id <=> (int)$b->id;
                }
                return $as <=> $bs;
            });
            $this->sections_by_parent[$parent_id] = $list;
        }
    }

    private function prepare_items($items)
    {
        $this->items_by_section = array();
        foreach ($items as $item) {
            if (isset($item->deleted) && (int)$item->deleted === 1) {
                continue;
            }
            $section_id = isset($item->section_id) ? (int)$item->section_id : 0;
            if (!isset($this->items_by_section[$section_id])) {
                $this->items_by_section[$section_id] = array();
            }
            $this->items_by_section[$section_id][] = $item;
        }

        foreach ($this->items_by_section as $section_id => $list) {
            usort($list, function ($a, $b) {
                $as = isset($a->sort) ? (int)$a->sort : 0;
                $bs = isset($b->sort) ? (int)$b->sort : 0;
                if ($as === $bs) {
                    return (int)$a->id <=> (int)$b->id;
                }
                return $as <=> $bs;
            });
            $this->items_by_section[$section_id] = $list;
        }
    }

    private function render_header($proposal)
    {
        $company_data_name = $this->esc($this->get_setting_value("company_data_name"));
        $company_data_cnpj = $this->esc($this->get_setting_value("company_data_cnpj"));
        $company_data_email = $this->esc($this->get_setting_value("company_data_email"));
        $company_data_phone = $this->esc($this->get_setting_value("company_data_phone"));
        $company_data_address = $this->esc($this->get_setting_value("company_data_address"));
        $company_data_city = $this->esc($this->get_setting_value("company_data_city"));
        $company_data_state = $this->esc($this->get_setting_value("company_data_state"));
        $company_data_zip = $this->esc($this->get_setting_value("company_data_zip"));
        $company_data_website = $this->esc($this->get_setting_value("company_data_website"));

        $company_name = $this->esc($this->get_setting_value("company_name"));
        $company_phone = $this->esc($this->get_setting_value("company_phone"));
        $company_email = $this->esc($this->get_setting_value("company_email"));
        $company_address = $this->esc($this->get_setting_value("company_address"));
        $company_tax = $this->esc($this->get_setting_value("company_vat_number"));
        $logo_url = function_exists("get_logo_url") ? get_logo_url() : "";

        $proposal_code = "PR-" . str_pad((int)$proposal->id, 6, "0", STR_PAD_LEFT);
        $validity_date = $this->get_validity_date($proposal);
        $title = $this->esc($proposal->title ?? "");

        $html = "<table class='proposal-doc-header-table' cellspacing='0' cellpadding='0'>";
        $html .= "<tr>";
        $html .= "<td class='proposal-doc-brand'>";
        if ($logo_url) {
            $html .= "<img class='proposal-doc-logo' src='" . $this->esc($logo_url) . "' alt='logo'>";
        }
        $brand_lines = array();
        if ($company_data_name) {
            $brand_lines[] = $company_data_name;
        }
        if ($company_data_cnpj) {
            $brand_lines[] = $company_data_cnpj;
        }
        if ($company_data_email) {
            $brand_lines[] = $company_data_email;
        }
        if ($company_data_phone) {
            $brand_lines[] = $company_data_phone;
        }
        $address_parts = array();
        if ($company_data_address) {
            $address_parts[] = $company_data_address;
        }
        $city_state = "";
        if ($company_data_city) {
            $city_state .= $company_data_city;
        }
        if ($company_data_state) {
            $city_state .= ($city_state ? " - " : "") . $company_data_state;
        }
        if ($city_state) {
            $address_parts[] = $city_state;
        }
        if ($company_data_zip) {
            $address_parts[] = $company_data_zip;
        }
        if ($address_parts) {
            $brand_lines[] = implode(", ", $address_parts);
        }
        if ($company_data_website) {
            $brand_lines[] = $company_data_website;
        }
        if ($brand_lines) {
            $html .= "<div class='proposal-doc-brand-info'>";
            foreach ($brand_lines as $line) {
                $html .= "<div>" . $line . "</div>";
            }
            $html .= "</div>";
        }
        $html .= "</td>";
        $html .= "<td class='proposal-doc-box'>";
        $html .= "<div class='proposal-doc-box-row'><strong>Orcamento No</strong><br>$proposal_code</div>";
        $html .= "<div class='proposal-doc-box-row'><strong>Modalidade</strong><br>Venda</div>";
        if ($validity_date) {
            $html .= "<div class='proposal-doc-box-row'><strong>Valido ate</strong><br>$validity_date</div>";
        }
        $html .= "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        if ($title) {
            $html .= "<div class='proposal-doc-main-title'>Orcamento " . $title . "</div>";
        }

        return $html;
    }

    private function render_header_pdf($proposal)
    {
        $company_data_name = $this->esc($this->get_setting_value("company_data_name"));
        $company_data_cnpj = $this->esc($this->get_setting_value("company_data_cnpj"));
        $company_data_email = $this->esc($this->get_setting_value("company_data_email"));
        $company_data_phone = $this->esc($this->get_setting_value("company_data_phone"));
        $company_data_address = $this->esc($this->get_setting_value("company_data_address"));
        $company_data_city = $this->esc($this->get_setting_value("company_data_city"));
        $company_data_state = $this->esc($this->get_setting_value("company_data_state"));
        $company_data_zip = $this->esc($this->get_setting_value("company_data_zip"));
        $company_data_website = $this->esc($this->get_setting_value("company_data_website"));
        $logo_url = function_exists("get_logo_url") ? get_logo_url() : "";

        $proposal_code = "PR-" . str_pad((int)$proposal->id, 6, "0", STR_PAD_LEFT);
        $validity_date = $this->get_validity_date($proposal);
        $title = $this->esc($proposal->title ?? "");

        $html = "<table style='width:100%; border:1px solid #333; border-collapse:collapse; margin-bottom:8px;' cellpadding='0' cellspacing='0'>";
        $html .= "<tr>";
        $html .= "<td style='vertical-align:top; padding:8px; border-right:1px solid #333; width:50%;'>";
        if ($logo_url) {
            $html .= "<img src='" . $this->esc($logo_url) . "' style='max-height:60px; max-width:160px;' alt='logo'>";
        }
        $brand_lines = array();
        if ($company_data_name) {
            $brand_lines[] = $company_data_name;
        }
        if ($company_data_cnpj) {
            $brand_lines[] = $company_data_cnpj;
        }
        if ($company_data_email) {
            $brand_lines[] = $company_data_email;
        }
        if ($company_data_phone) {
            $brand_lines[] = $company_data_phone;
        }
        $address_parts = array();
        if ($company_data_address) {
            $address_parts[] = $company_data_address;
        }
        $city_state = "";
        if ($company_data_city) {
            $city_state .= $company_data_city;
        }
        if ($company_data_state) {
            $city_state .= ($city_state ? " - " : "") . $company_data_state;
        }
        if ($city_state) {
            $address_parts[] = $city_state;
        }
        if ($company_data_zip) {
            $address_parts[] = $company_data_zip;
        }
        if ($address_parts) {
            $brand_lines[] = implode(", ", $address_parts);
        }
        if ($company_data_website) {
            $brand_lines[] = $company_data_website;
        }
        if ($brand_lines) {
            $html .= "<div style='margin-top:6px; font-size:10px; line-height:1.3; text-align:left;'>";
            foreach ($brand_lines as $line) {
                $html .= "<div>" . $line . "</div>";
            }
            $html .= "</div>";
        }
        $html .= "</td>";
        $html .= "<td style='vertical-align:top; padding:8px;'>";
        $html .= "<div style='border:1px solid #333; margin-bottom:6px; padding:4px; text-align:center;'><strong>Orcamento No</strong><br>" . $proposal_code . "</div>";
        $html .= "<div style='border:1px solid #333; margin-bottom:6px; padding:4px; text-align:center;'><strong>Modalidade</strong><br>Venda</div>";
        if ($validity_date) {
            $html .= "<div style='border:1px solid #333; margin-bottom:6px; padding:4px; text-align:center;'><strong>Valido ate</strong><br>" . $validity_date . "</div>";
        }
        $html .= "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        if ($title) {
            $html .= "<div style='text-align:center; font-weight:bold; border:1px solid #333; padding:6px; margin-bottom:8px;'>Orcamento " . $title . "</div>";
        }

        return $html;
    }

    private function render_vendor_client($proposal)
    {
        $seller_name = $this->esc($proposal->created_by_name ?? "");
        $seller_phone = "";
        $seller_email = "";
        if (isset($proposal->created_by) && $proposal->created_by) {
            $seller = $this->get_user_info((int)$proposal->created_by);
            $seller_phone = $this->esc($seller["phone"] ?? "");
            $seller_email = $this->esc($seller["email"] ?? "");
            if (!$seller_name && !empty($seller["name"])) {
                $seller_name = $this->esc($seller["name"]);
            }
        }

        $client = $this->get_client_info($proposal);
        $client_name = $this->esc($client["name"] ?? "");
        $client_tax = $this->esc($client["tax"] ?? "");
        $client_contact = $this->esc($client["contact"] ?? "");
        $client_phone = $this->esc($client["phone"] ?? "");
        $client_email = $this->esc($client["email"] ?? "");
        $client_address = $this->esc($client["address"] ?? "");

        $html = "<table class='proposal-doc-info-table' cellspacing='0' cellpadding='0'>";
        $html .= "<tr>";
        $html .= "<td><strong>Vendedor</strong><br>" . ($seller_name ?: "-") . "</td>";
        $html .= "<td><strong>Fone Vendedor</strong><br>" . ($seller_phone ?: "-") . "</td>";
        $html .= "<td><strong>E-mail Vendedor</strong><br>" . ($seller_email ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        $html .= "<table class='proposal-doc-client-table' cellspacing='0' cellpadding='0'>";
        $html .= "<tr>";
        $html .= "<td colspan='2'><strong>Cliente</strong><br>" . ($client_name ?: "-") . "</td>";
        $html .= "<td class='proposal-doc-client-tax'><strong>CPF/CNPJ</strong><br>" . ($client_tax ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "<td><strong>Contato</strong><br>" . ($client_contact ?: "Nao informado") . "</td>";
        $html .= "<td><strong>Telefone</strong><br>" . ($client_phone ?: "Nao informado") . "</td>";
        $html .= "<td><strong>E-mail</strong><br>" . ($client_email ?: "Nao informado") . "</td>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "<td colspan='3'><strong>Endereco</strong><br>" . ($client_address ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        return $html;
    }

    private function render_vendor_client_pdf($proposal)
    {
        $seller_name = $this->esc($proposal->created_by_name ?? "");
        $seller_phone = "";
        $seller_email = "";
        if (isset($proposal->created_by) && $proposal->created_by) {
            $seller = $this->get_user_info((int)$proposal->created_by);
            $seller_phone = $this->esc($seller["phone"] ?? "");
            $seller_email = $this->esc($seller["email"] ?? "");
            if (!$seller_name && !empty($seller["name"])) {
                $seller_name = $this->esc($seller["name"]);
            }
        }

        $client = $this->get_client_info($proposal);
        $client_name = $this->esc($client["name"] ?? "");
        $client_tax = $this->esc($client["tax"] ?? "");
        $client_contact = $this->esc($client["contact"] ?? "");
        $client_phone = $this->esc($client["phone"] ?? "");
        $client_email = $this->esc($client["email"] ?? "");
        $client_address = $this->esc($client["address"] ?? "");

        $html = "<table style='width:100%; border:1px solid #333; border-collapse:collapse; margin-bottom:8px;' cellpadding='0' cellspacing='0'>";
        $html .= "<tr>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>Vendedor</strong><br>" . ($seller_name ?: "-") . "</td>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>Fone Vendedor</strong><br>" . ($seller_phone ?: "-") . "</td>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>E-mail Vendedor</strong><br>" . ($seller_email ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        $html .= "<table style='width:100%; border:1px solid #333; border-collapse:collapse; margin-bottom:8px;' cellpadding='0' cellspacing='0'>";
        $html .= "<tr>";
        $html .= "<td style='border:1px solid #333; padding:6px;' colspan='2'><strong>Cliente</strong><br>" . ($client_name ?: "-") . "</td>";
        $html .= "<td style='border:1px solid #333; padding:6px; width:200px;'><strong>CPF/CNPJ</strong><br>" . ($client_tax ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>Contato</strong><br>" . ($client_contact ?: "Nao informado") . "</td>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>Telefone</strong><br>" . ($client_phone ?: "Nao informado") . "</td>";
        $html .= "<td style='border:1px solid #333; padding:6px;'><strong>E-mail</strong><br>" . ($client_email ?: "Nao informado") . "</td>";
        $html .= "</tr>";
        $html .= "<tr>";
        $html .= "<td style='border:1px solid #333; padding:6px;' colspan='3'><strong>Endereco</strong><br>" . ($client_address ?: "-") . "</td>";
        $html .= "</tr>";
        $html .= "</table>";

        return $html;
    }

    private function render_items_tables($proposal)
    {
        $items = $this->get_all_items();
        if ($this->mode === "total_only") {
            return $this->render_total_only_list($items);
        }

        $service_items = array();
        $material_items = array();
        foreach ($items as $item) {
            if (($item->item_type ?? "material") === "service") {
                $service_items[] = $item;
            } else {
                $material_items[] = $item;
            }
        }

        $html = "";
        if ($service_items) {
            $html .= $this->render_items_table_block("SERVICOS", $service_items, "Total Servicos");
        }
        if ($material_items) {
            $html .= $this->render_items_table_block("MATERIAIS", $material_items, "Total Materiais");
        }

        $total_all = $this->sum_items_total($items);
        $html .= "<div class='proposal-doc-grand-total'>Total: <span>" . $this->format_money($total_all) . "</span></div>";

        return $html;
    }

    private function render_items_tables_pdf($proposal)
    {
        $items = $this->get_all_items();
        if ($this->mode === "total_only") {
            return $this->render_total_only_list($items);
        }

        $service_items = array();
        $material_items = array();
        foreach ($items as $item) {
            if (($item->item_type ?? "material") === "service") {
                $service_items[] = $item;
            } else {
                $material_items[] = $item;
            }
        }

        $html = "";
        if ($service_items) {
            $html .= $this->render_items_table_block_pdf("SERVICOS", $service_items, "Total Servicos");
        }
        if ($material_items) {
            $html .= $this->render_items_table_block_pdf("MATERIAIS", $material_items, "Total Materiais");
        }

        $total_all = $this->sum_items_total($items);
        $html .= "<div style='width:100%; text-align:right; font-weight:bold; padding:6px; border-top:1px solid #333; margin:10px 0;'>Total: <span>" . $this->format_money($total_all) . "</span></div>";

        return $html;
    }

    private function render_items_table_block_pdf($title, $items, $total_label)
    {
        $html = "<div style='text-align:left; font-weight:bold; border:1px solid #333; padding:6px; margin-top:6px;'>" . $this->esc($title) . "</div>";
        $html .= "<table style='width:100%; border-collapse:collapse; border:1px solid #333; margin-bottom:6px;' cellpadding='0' cellspacing='0'>";
        $html .= "<thead><tr>";
        $html .= "<th style='border:1px solid #333; padding:6px; background:#f5f5f5;'>Descricao</th>";
        $html .= "<th style='border:1px solid #333; padding:6px; background:#f5f5f5; text-align:center;'>Quant.</th>";
        if ($this->show_values) {
            $html .= "<th style='border:1px solid #333; padding:6px; background:#f5f5f5; text-align:right;'>Unitario</th>";
            $html .= "<th style='border:1px solid #333; padding:6px; background:#f5f5f5; text-align:right;'>Desconto</th>";
            $html .= "<th style='border:1px solid #333; padding:6px; background:#f5f5f5; text-align:right;'>Total Liquido</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($items as $item) {
            $html .= "<tr>";
            $html .= "<td style='border:1px solid #333; padding:6px;'>" . $this->esc($this->item_label($item)) . "</td>";
            $html .= "<td style='border:1px solid #333; padding:6px; text-align:center;'>" . $this->format_number($item->qty ?? 0) . " UN</td>";
            if ($this->show_values) {
                $html .= "<td style='border:1px solid #333; padding:6px; text-align:right;'>" . $this->format_money($item->sale_unit ?? 0) . "</td>";
                $html .= "<td style='border:1px solid #333; padding:6px; text-align:right;'>R$ 0,00</td>";
                $html .= "<td style='border:1px solid #333; padding:6px; text-align:right;'>" . $this->format_money($item->total ?? 0) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody>";

        if ($this->show_values) {
            $total_block = $this->format_money($this->sum_items_total($items));
            $html .= "<tfoot><tr>";
            $html .= "<td style='border:1px solid #333; padding:6px;' colspan='3'></td>";
            $html .= "<td style='border:1px solid #333; padding:6px; text-align:right;'><strong>" . $this->esc($total_label) . "</strong></td>";
            $html .= "<td style='border:1px solid #333; padding:6px; text-align:right;'><strong>" . $total_block . "</strong></td>";
            $html .= "</tr></tfoot>";
        }

        $html .= "</table>";
        return $html;
    }

    private function render_items_table_block($title, $items, $total_label)
    {
        $html = "<div class='proposal-doc-table-title'>" . $this->esc($title) . "</div>";
        $html .= "<table class='proposal-doc-items-table' cellspacing='0' cellpadding='0'>";
        $html .= "<thead><tr>";
        $html .= "<th>Descricao</th>";
        $html .= "<th class='text-center'>Quant.</th>";
        if ($this->show_values) {
            $html .= "<th class='text-right'>Unitario</th>";
            $html .= "<th class='text-right'>Desconto</th>";
            $html .= "<th class='text-right'>Total Liquido</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($items as $item) {
            $html .= "<tr>";
            $html .= "<td>" . $this->esc($this->item_label($item)) . "</td>";
            $html .= "<td class='text-center'>" . $this->format_number($item->qty ?? 0) . " UN</td>";
            if ($this->show_values) {
                $html .= "<td class='text-right'>" . $this->format_money($item->sale_unit ?? 0) . "</td>";
                $html .= "<td class='text-right'>R$ 0,00</td>";
                $html .= "<td class='text-right'>" . $this->format_money($item->total ?? 0) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody>";

        if ($this->show_values) {
            $total_block = $this->format_money($this->sum_items_total($items));
            $html .= "<tfoot><tr>";
            $html .= "<td colspan='3'></td>";
            $html .= "<td class='text-right'><strong>" . $this->esc($total_label) . "</strong></td>";
            $html .= "<td class='text-right'><strong>" . $total_block . "</strong></td>";
            $html .= "</tr></tfoot>";
        }

        $html .= "</table>";
        return $html;
    }

    private function render_total_only_list($items)
    {
        if (!$items) {
            return "<div class='proposal-doc-muted'>" . app_lang("proposals_no_items") . "</div>";
        }
        $html = "<ul class='proposal-doc-list'>";
        foreach ($items as $item) {
            $html .= "<li>" . $this->esc($this->item_label($item)) . "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    private function render_scope_block($proposal)
    {
        $description = $this->nl2br($proposal->description ?? "");
        $payment_terms = $this->nl2br($proposal->payment_terms ?? "");
        $observations = $this->nl2br($proposal->observations ?? "");

        if (!$description && !$payment_terms && !$observations) {
            return "";
        }

        $html = "";
        if ($description) {
            $html .= "<div class='proposal-doc-tech-title'>DESCRICAO</div>";
            $html .= "<div class='proposal-doc-tech-box'>";
            $html .= $description;
            $html .= "</div>";
        }
        if ($payment_terms) {
            $html .= "<div class='proposal-doc-tech-title'>FORMA DE PAGAMENTO</div>";
            $html .= "<div class='proposal-doc-tech-box'>";
            $html .= $payment_terms;
            $html .= "</div>";
        }
        if ($observations) {
            $html .= "<div class='proposal-doc-tech-title'>NOTAS</div>";
            $html .= "<div class='proposal-doc-tech-box'>";
            $html .= $observations;
            $html .= "</div>";
        }
        return $html;
    }

    private function render_scope_block_pdf($proposal)
    {
        $description = $this->nl2br($proposal->description ?? "");
        $payment_terms = $this->nl2br($proposal->payment_terms ?? "");
        $observations = $this->nl2br($proposal->observations ?? "");

        if (!$description && !$payment_terms && !$observations) {
            return "";
        }

        $html = "";
        if ($description) {
            $html .= "<div style='text-align:left; font-weight:bold; border:1px solid #333; padding:6px; margin-top:10px;'>DESCRICAO</div>";
            $html .= "<div style='border:1px solid #333; padding:8px; min-height:80px;'>" . $description . "</div>";
        }
        if ($payment_terms) {
            $html .= "<div style='text-align:left; font-weight:bold; border:1px solid #333; padding:6px; margin-top:10px;'>FORMA DE PAGAMENTO</div>";
            $html .= "<div style='border:1px solid #333; padding:8px; min-height:80px;'>" . $payment_terms . "</div>";
        }
        if ($observations) {
            $html .= "<div style='text-align:left; font-weight:bold; border:1px solid #333; padding:6px; margin-top:10px;'>NOTAS</div>";
            $html .= "<div style='border:1px solid #333; padding:8px; min-height:80px;'>" . $observations . "</div>";
        }
        return $html;
    }

    private function render_sections_tables($parent_id = 0)
    {
        $sections = $this->sections_by_parent[$parent_id] ?? array();
        if (!$sections) {
            return "<div class='text-muted'>" . app_lang("proposals_no_items") . "</div>";
        }

        $html = "";
        foreach ($sections as $section) {
            $section_id = (int)$section->id;
            $title = $this->esc($section->title ?? app_lang("proposals_section"));
            $level_class = $parent_id ? "proposal-doc-subsection" : "proposal-doc-section";
            $html .= "<div class='$level_class'>";
            $html .= "<div class='proposal-doc-section-title'>$title</div>";

            $items = $this->items_by_section[$section_id] ?? array();
            if ($items) {
                $html .= $this->render_items_table($items);
            }

            $children = $this->render_sections_tables($section_id);
            if ($children) {
                $html .= $children;
            }
            $html .= "</div>";
        }

        return $html;
    }

    private function render_sections_text($parent_id = 0)
    {
        $sections = $this->sections_by_parent[$parent_id] ?? array();
        if (!$sections) {
            return "<div class='text-muted'>" . app_lang("proposals_no_items") . "</div>";
        }

        $html = "";
        foreach ($sections as $section) {
            $section_id = (int)$section->id;
            $title = $this->esc($section->title ?? app_lang("proposals_section"));
            $level_class = $parent_id ? "proposal-doc-subsection" : "proposal-doc-section";
            $html .= "<div class='$level_class'>";
            $html .= "<div class='proposal-doc-section-title'>$title</div>";

            $items = $this->items_by_section[$section_id] ?? array();
            if ($items) {
                $html .= "<ul class='proposal-doc-list'>";
                foreach ($items as $item) {
                    $html .= "<li>" . $this->esc($this->item_label($item)) . "</li>";
                }
                $html .= "</ul>";
            }

            $children = $this->render_sections_text($section_id);
            if ($children) {
                $html .= $children;
            }
            $html .= "</div>";
        }

        return $html;
    }

    private function render_items_table($items)
    {
        $html = "<table class='table table-bordered proposal-doc-table'>";
        $html .= "<thead><tr>";
        $html .= "<th>" . app_lang("item") . "</th>";
        $html .= "<th class='text-right'>" . app_lang("quantity") . "</th>";
        if ($this->show_values) {
            $html .= "<th class='text-right'>" . app_lang("rate") . "</th>";
            $html .= "<th class='text-right'>" . app_lang("total") . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        foreach ($items as $item) {
            $html .= "<tr>";
            $html .= "<td>" . $this->esc($this->item_label($item)) . "</td>";
            $html .= "<td class='text-right'>" . $this->format_number($item->qty ?? 0) . "</td>";
            if ($this->show_values) {
                $html .= "<td class='text-right'>" . $this->format_money($item->sale_unit ?? 0) . "</td>";
                $html .= "<td class='text-right'>" . $this->format_money($item->total ?? 0) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }

    private function render_total($proposal)
    {
        $total = isset($proposal->total_sale) ? (float)$proposal->total_sale : 0;
        $html = "<div class='proposal-doc-total'>";
        $html .= "<strong>" . app_lang("total") . ":</strong> " . $this->format_money($total);
        $html .= "</div>";
        return $html;
    }

    private function render_style()
    {
        return "<style>
            .proposal-doc-alfahp { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
            .proposal-doc-header-table { width: 100%; border: 1px solid #333; margin-bottom: 8px; }
            .proposal-doc-header-table td { vertical-align: top; border-right: 1px solid #333; padding: 8px; }
            .proposal-doc-header-table td:last-child { border-right: none; }
            .proposal-doc-logo { max-height: 60px; max-width: 160px; }
            .proposal-doc-brand-info { margin-top: 6px; font-size: 10px; line-height: 1.3; }
            .proposal-doc-brand-info div { text-align: left; }
            .proposal-doc-company-name { font-size: 16px; font-weight: bold; text-align: center; }
            .proposal-doc-company-line { text-align: center; margin-top: 2px; }
            .proposal-doc-box { width: 160px; }
            .proposal-doc-box-row { border: 1px solid #333; margin-bottom: 6px; padding: 4px; text-align: center; }
            .proposal-doc-main-title { text-align: center; font-weight: bold; border: 1px solid #333; padding: 6px; margin-bottom: 8px; }
            .proposal-doc-info-table, .proposal-doc-client-table { width: 100%; border: 1px solid #333; border-collapse: collapse; margin-bottom: 8px; }
            .proposal-doc-info-table td, .proposal-doc-client-table td { border: 1px solid #333; padding: 6px; }
            .proposal-doc-client-tax { width: 200px; }
            .proposal-doc-table-title { text-align: left; font-weight: bold; border: 1px solid #333; padding: 6px; margin-top: 6px; }
            .proposal-doc-items-table { width: 100%; border-collapse: collapse; border: 1px solid #333; margin-bottom: 6px; }
            .proposal-doc-items-table th, .proposal-doc-items-table td { border: 1px solid #333; padding: 6px; }
            .proposal-doc-items-table th { background: #f5f5f5; }
            .proposal-doc-total-row { width: 100%; text-align: right; font-weight: bold; margin-top: 4px; }
            .proposal-doc-total-row span { display: inline-block; min-width: 140px; text-align: right; }
            .proposal-doc-grand-total { width: 100%; text-align: right; font-weight: bold; padding: 6px; border-top: 1px solid #333; margin: 10px 0; }
            .proposal-doc-tech-title { text-align: left; font-weight: bold; border: 1px solid #333; padding: 6px; margin-top: 10px; }
            .proposal-doc-tech-box { border: 1px solid #333; padding: 8px; min-height: 80px; }
            .proposal-doc-list { margin: 6px 0 10px 16px; padding: 0; }
            .proposal-doc-muted { color: #666; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
        </style>";
    }

    private function item_label($item)
    {
        $description = trim((string)($item->description_override ?? ""));
        if ($description) {
            $description = preg_replace('/^\s*\[servico\]\s*/i', '', $description);
        }
        if ($description) {
            return $description;
        }
        $title = trim((string)($item->item_title ?? ""));
        if ($title) {
            $title = preg_replace('/^\s*\[servico\]\s*/i', '', $title);
        }
        if ($title) {
            return $title;
        }
        return app_lang("item") . " #" . (int)$item->id;
    }

    private function esc($text)
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, "UTF-8");
    }

    private function nl2br($text)
    {
        $value = trim((string)$text);
        if ($value === "") {
            return "";
        }
        return nl2br($this->esc($value));
    }

    private function format_money($value)
    {
        if (function_exists("to_currency")) {
            return to_currency($value);
        }
        return number_format((float)$value, 2, ",", ".");
    }

    private function format_number($value)
    {
        return number_format((float)$value, 2, ",", ".");
    }

    private function get_setting_value($key)
    {
        if (function_exists("get_setting")) {
            return get_setting($key);
        }
        return "";
    }

    private function get_validity_date($proposal)
    {
        $days = isset($proposal->validity_days) ? (int)$proposal->validity_days : 0;
        if ($days <= 0) {
            return "";
        }
        $base = $proposal->created_at ?? "";
        if (!$base) {
            return "";
        }
        $date = date_create($base);
        if (!$date) {
            return "";
        }
        $date->modify("+" . $days . " day");
        return $date->format("d/m/Y");
    }

    private function get_user_info($user_id)
    {
        $db = db_connect("default");
        $users_table = $db->prefixTable("users");
        $row = $db->table($users_table)
            ->select("first_name, last_name, phone, email")
            ->where("id", (int)$user_id)
            ->get()
            ->getRow();
        if (!$row) {
            return array();
        }
        $name = trim(($row->first_name ?? "") . " " . ($row->last_name ?? ""));
        return array(
            "name" => $name,
            "phone" => $row->phone ?? "",
            "email" => $row->email ?? ""
        );
    }

    private function get_client_info($proposal)
    {
        $client_name = $proposal->client_name ?? "";
        if (isset($proposal->client_company) && $proposal->client_company) {
            $client_name = $proposal->client_company;
        }
        $client_id = isset($proposal->client_id) ? (int)$proposal->client_id : 0;
        if (!$client_id) {
            return array("name" => $client_name);
        }

        $db = db_connect("default");
        $clients_table = $db->prefixTable("clients");
        $fields = $db->getFieldNames($clients_table);
        $select = array("id");
        foreach (array("company_name", "address", "city", "state", "zip", "phone", "email", "vat_number", "contact_name") as $field) {
            if (in_array($field, $fields, true)) {
                $select[] = $field;
            }
        }
        $row = $db->table($clients_table)
            ->select(implode(",", $select))
            ->where("id", $client_id)
            ->get()
            ->getRow();
        if (!$row) {
            return array("name" => $client_name);
        }

        $address_parts = array();
        if (!empty($row->address)) {
            $address_parts[] = $row->address;
        }
        $city_state = "";
        if (!empty($row->city)) {
            $city_state .= $row->city;
        }
        if (!empty($row->state)) {
            $city_state .= ($city_state ? " - " : "") . $row->state;
        }
        if ($city_state) {
            $address_parts[] = $city_state;
        }
        if (!empty($row->zip)) {
            $address_parts[] = $row->zip;
        }
        $address = $address_parts ? implode(", ", $address_parts) : "";

        return array(
            "name" => $row->company_name ?? $client_name,
            "tax" => $row->vat_number ?? "",
            "contact" => $row->contact_name ?? "",
            "phone" => $row->phone ?? "",
            "email" => $row->email ?? "",
            "address" => $address
        );
    }

    private function get_all_items()
    {
        $items = array();
        foreach ($this->items_by_section as $list) {
            foreach ($list as $item) {
                $items[] = $item;
            }
        }
        usort($items, function ($a, $b) {
            $as = isset($a->sort) ? (int)$a->sort : 0;
            $bs = isset($b->sort) ? (int)$b->sort : 0;
            if ($as === $bs) {
                return (int)$a->id <=> (int)$b->id;
            }
            return $as <=> $bs;
        });
        return $items;
    }

    private function sum_items_total($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += (float)($item->total ?? 0);
        }
        return $total;
    }
}
