<?php

$install_file = __DIR__ . '/install.sql';
if (!file_exists($install_file)) {
    return array("success" => false, "errors" => array("install.sql not found"));
}

$db = db_connect('default');
$dbprefix = get_db_prefix();
$sql = file_get_contents($install_file);
if (!$sql) {
    return array("success" => false, "errors" => array("install.sql is empty"));
}

$result = array(
    "success" => true,
    "tables" => array(),
    "executed" => array(),
    "errors" => array()
);

$sql = str_replace('{{DB_PREFIX}}', $dbprefix, $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $statement) {
    if (!$statement) {
        continue;
    }

    $query_ok = $db->query($statement);
    $result["executed"][] = $statement;

    if (preg_match("/CREATE TABLE IF NOT EXISTS `([^`]+)`/i", $statement, $match)) {
        $result["tables"][] = $match[1];
    }

    if (!$query_ok) {
        $result["success"] = false;
        $result["errors"][] = "Failed: " . $statement;
    }
}

$alter_errors = array();
$columns_to_add = array(
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "section_id",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `section_id` INT(11) NULL AFTER `proposal_id`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "item_type",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `item_type` VARCHAR(20) NULL AFTER `item_id`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "description_override",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `description_override` TEXT NULL AFTER `item_type`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "cost_unit",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `cost_unit` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `description_override`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "qty",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `qty` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `cost_unit`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "markup_percent",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `markup_percent` DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER `qty`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "sale_unit",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `sale_unit` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `markup_percent`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "total",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `total` DECIMAL(16,2) NOT NULL DEFAULT 0 AFTER `sale_unit`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "show_in_proposal",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `show_in_proposal` TINYINT(1) NOT NULL DEFAULT 1 AFTER `total`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "show_values_in_proposal",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `show_values_in_proposal` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_in_proposal`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "in_memory",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `in_memory` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_values_in_proposal`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "sort",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `sort` INT(11) NOT NULL DEFAULT 0 AFTER `in_memory`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "created_by",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `created_by` INT(11) NULL AFTER `sort`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "created_at",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "updated_at",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "deleted",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `updated_at`"
    ),
    array(
        "table" => $dbprefix . "proposal_items_custom",
        "column" => "item_id_nullable",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposal_items_custom` MODIFY `item_id` INT(11) NULL"
    ),
    array(
        "table" => $dbprefix . "proposals_module_settings_custom",
        "column" => "default_markup_percent",
        "sql" => "ALTER TABLE `" . $dbprefix . "proposals_module_settings_custom` ADD COLUMN `default_markup_percent` DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER `default_commission_value`"
    )
);

$proposals_table = $dbprefix . "proposals_custom";
$display_after = $db->fieldExists("status", $proposals_table) ? " AFTER `status`" : "";
$commission_type_after = $db->fieldExists("display_mode", $proposals_table) ? " AFTER `display_mode`" : "";
$commission_value_after = $db->fieldExists("commission_type", $proposals_table) ? " AFTER `commission_type`" : "";
$tax_product_after = $db->fieldExists("commission_value", $proposals_table) ? " AFTER `commission_value`" : "";
$tax_service_after = $db->fieldExists("tax_product_percent", $proposals_table) ? " AFTER `tax_product_percent`" : "";
$tax_service_only_after = $db->fieldExists("tax_service_percent", $proposals_table) ? " AFTER `tax_service_percent`" : "";
$taxes_after = $db->fieldExists("tax_service_only", $proposals_table) ? " AFTER `tax_service_only`" : "";
$total_cost_material_after = $db->fieldExists("taxes_snapshot_json", $proposals_table) ? " AFTER `taxes_snapshot_json`" : "";
$total_cost_service_after = $db->fieldExists("total_cost_material", $proposals_table) ? " AFTER `total_cost_material`" : "";
$total_sale_after = $db->fieldExists("total_cost_service", $proposals_table) ? " AFTER `total_cost_service`" : "";
$taxes_total_after = $db->fieldExists("total_sale", $proposals_table) ? " AFTER `total_sale`" : "";
$commission_total_after = $db->fieldExists("taxes_total", $proposals_table) ? " AFTER `taxes_total`" : "";
$profit_gross_after = $db->fieldExists("commission_total", $proposals_table) ? " AFTER `commission_total`" : "";
$profit_net_after = $db->fieldExists("profit_gross", $proposals_table) ? " AFTER `profit_gross`" : "";

$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "display_mode",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `display_mode` VARCHAR(20) NOT NULL DEFAULT 'detailed'" . $display_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "commission_type",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `commission_type` VARCHAR(20) NOT NULL DEFAULT 'percent'" . $commission_type_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "commission_value",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0" . $commission_value_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "taxes_snapshot_json",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `taxes_snapshot_json` LONGTEXT NULL" . $taxes_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "tax_product_percent",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `tax_product_percent` DECIMAL(8,2) NOT NULL DEFAULT 0" . $tax_product_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "tax_service_percent",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `tax_service_percent` DECIMAL(8,2) NOT NULL DEFAULT 0" . $tax_service_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "tax_service_only",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `tax_service_only` TINYINT(1) NOT NULL DEFAULT 0" . $tax_service_only_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "total_cost_material",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `total_cost_material` DECIMAL(16,2) NOT NULL DEFAULT 0" . $total_cost_material_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "total_cost_service",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `total_cost_service` DECIMAL(16,2) NOT NULL DEFAULT 0" . $total_cost_service_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "total_sale",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `total_sale` DECIMAL(16,2) NOT NULL DEFAULT 0" . $total_sale_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "taxes_total",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `taxes_total` DECIMAL(16,2) NOT NULL DEFAULT 0" . $taxes_total_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "commission_total",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `commission_total` DECIMAL(16,2) NOT NULL DEFAULT 0" . $commission_total_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "profit_gross",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `profit_gross` DECIMAL(16,2) NOT NULL DEFAULT 0" . $profit_gross_after
);
$columns_to_add[] = array(
    "table" => $proposals_table,
    "column" => "profit_net",
    "sql" => "ALTER TABLE `" . $proposals_table . "` ADD COLUMN `profit_net` DECIMAL(16,2) NOT NULL DEFAULT 0" . $profit_net_after
);

$items_table = $dbprefix . "items";
$columns_to_add[] = array(
    "table" => $items_table,
    "column" => "ca_code",
    "sql" => "ALTER TABLE `" . $items_table . "` ADD COLUMN `ca_code` VARCHAR(100) NULL AFTER `title`"
);
$columns_to_add[] = array(
    "table" => $items_table,
    "column" => "cost",
    "sql" => "ALTER TABLE `" . $items_table . "` ADD COLUMN `cost` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `rate`"
);
$columns_to_add[] = array(
    "table" => $items_table,
    "column" => "sale",
    "sql" => "ALTER TABLE `" . $items_table . "` ADD COLUMN `sale` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `cost`"
);
$columns_to_add[] = array(
    "table" => $items_table,
    "column" => "markup",
    "sql" => "ALTER TABLE `" . $items_table . "` ADD COLUMN `markup` DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER `sale`"
);

foreach ($columns_to_add as $column) {
    $table = $column["table"];
    $field = $column["column"];
    if ($field === "item_id_nullable") {
        if ($db->tableExists($table)) {
            $query_ok = $db->query($column["sql"]);
            $result["executed"][] = $column["sql"];
            if (!$query_ok) {
                $result["success"] = false;
                $alter_errors[] = "Failed: " . $column["sql"] . " | " . json_encode($db->error());
            }
        }
        continue;
    }

    if ($db->tableExists($table) && !$db->fieldExists($field, $table)) {
        $query_ok = $db->query($column["sql"]);
        $result["executed"][] = $column["sql"];
        if (!$query_ok) {
            $result["success"] = false;
            $alter_errors[] = "Failed: " . $column["sql"] . " | " . json_encode($db->error());
        }
    }
}

if ($alter_errors) {
    $result["errors"] = array_merge($result["errors"], $alter_errors);
}

return $result;
