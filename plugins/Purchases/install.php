<?php

// execute SQL for Purchases plugin
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

// ensure required columns for upgrades
try {
    $requests_table = $db->prefixTable('purchases_requests');
    $req_fields = $db->getFieldNames($requests_table);
    if (is_array($req_fields)) {
        $req_columns = array(
            'request_code_number' => "ALTER TABLE `{$requests_table}` ADD `request_code_number` INT(11) NULL AFTER `company_id`",
            'request_code' => "ALTER TABLE `{$requests_table}` ADD `request_code` VARCHAR(50) NULL AFTER `request_code_number`",
            'project_id' => "ALTER TABLE `{$requests_table}` ADD `project_id` INT(11) NULL AFTER `request_code`",
            'client_id' => "ALTER TABLE `{$requests_table}` ADD `client_id` INT(11) NULL AFTER `project_id`",
            'os_id' => "ALTER TABLE `{$requests_table}` ADD `os_id` INT(11) NULL AFTER `client_id`",
            'is_internal' => "ALTER TABLE `{$requests_table}` ADD `is_internal` TINYINT(1) NOT NULL DEFAULT 0 AFTER `os_id`",
            'cost_center' => "ALTER TABLE `{$requests_table}` ADD `cost_center` VARCHAR(255) NULL AFTER `project_id`",
            'priority' => "ALTER TABLE `{$requests_table}` ADD `priority` VARCHAR(20) NOT NULL DEFAULT 'medium' AFTER `cost_center`",
            'requested_by' => "ALTER TABLE `{$requests_table}` ADD `requested_by` INT(11) NULL AFTER `requester_id`",
            'submitted_at' => "ALTER TABLE `{$requests_table}` ADD `submitted_at` DATETIME NULL AFTER `total`",
            'approved_by' => "ALTER TABLE `{$requests_table}` ADD `approved_by` INT(11) NULL AFTER `submitted_at`",
            'approved_at' => "ALTER TABLE `{$requests_table}` ADD `approved_at` DATETIME NULL AFTER `approved_by`",
            'rejected_by' => "ALTER TABLE `{$requests_table}` ADD `rejected_by` INT(11) NULL AFTER `approved_at`",
            'rejected_at' => "ALTER TABLE `{$requests_table}` ADD `rejected_at` DATETIME NULL AFTER `rejected_by`",
            'rejected_reason' => "ALTER TABLE `{$requests_table}` ADD `rejected_reason` TEXT NULL AFTER `rejected_at`",
            'converted_by' => "ALTER TABLE `{$requests_table}` ADD `converted_by` INT(11) NULL AFTER `rejected_reason`",
            'converted_at' => "ALTER TABLE `{$requests_table}` ADD `converted_at` DATETIME NULL AFTER `converted_by`"
        );

        foreach ($req_columns as $field => $statement) {
            if (!in_array($field, $req_fields)) {
                $ok = $db->query($statement);
                $result["executed"][] = $statement;
                if (!$ok) {
                    $result["success"] = false;
                    $result["errors"][] = "Failed: " . $statement;
                }
            }
        }
    }

    $items_table = $db->prefixTable('purchases_request_items');
    $item_fields = $db->getFieldNames($items_table);
    if (is_array($item_fields)) {
        if (!in_array('desired_date', $item_fields)) {
            $statement = "ALTER TABLE `{$items_table}` ADD `desired_date` DATE NULL AFTER `unit`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
        if (!in_array('note', $item_fields)) {
            $statement = "ALTER TABLE `{$items_table}` ADD `note` TEXT NULL AFTER `desired_date`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
    }

    $orders_table = $db->prefixTable('purchases_orders');
    $order_fields = $db->getFieldNames($orders_table);
    if (is_array($order_fields)) {
        $order_columns = array(
            'po_code_number' => "ALTER TABLE `{$orders_table}` ADD `po_code_number` INT(11) NULL AFTER `request_id`",
            'po_code' => "ALTER TABLE `{$orders_table}` ADD `po_code` VARCHAR(50) NULL AFTER `po_code_number`",
            'project_id' => "ALTER TABLE `{$orders_table}` ADD `project_id` INT(11) NULL AFTER `supplier_id`",
            'cost_center' => "ALTER TABLE `{$orders_table}` ADD `cost_center` VARCHAR(255) NULL AFTER `project_id`",
            'expected_delivery_date' => "ALTER TABLE `{$orders_table}` ADD `expected_delivery_date` DATE NULL AFTER `order_date`",
            'delivery_address' => "ALTER TABLE `{$orders_table}` ADD `delivery_address` TEXT NULL AFTER `expected_delivery_date`",
            'payment_terms' => "ALTER TABLE `{$orders_table}` ADD `payment_terms` VARCHAR(255) NULL AFTER `delivery_address`"
        );

        foreach ($order_columns as $field => $statement) {
            if (!in_array($field, $order_fields)) {
                $ok = $db->query($statement);
                $result["executed"][] = $statement;
                if (!$ok) {
                    $result["success"] = false;
                    $result["errors"][] = "Failed: " . $statement;
                }
            }
        }
    }

    $quote_prices_table = $db->prefixTable('purchases_quotation_item_prices');
    $quote_fields = $db->getFieldNames($quote_prices_table);
    if (is_array($quote_fields)) {
        if (!in_array('is_winner', $quote_fields)) {
            $statement = "ALTER TABLE `{$quote_prices_table}` ADD `is_winner` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notes`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
        if (!in_array('delivery_date', $quote_fields)) {
            $statement = "ALTER TABLE `{$quote_prices_table}` ADD `delivery_date` DATE NULL AFTER `lead_time_days`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
    }

    $receipts_table = $db->prefixTable('purchases_goods_receipts');
    $receipt_fields = $db->getFieldNames($receipts_table);
    if (is_array($receipt_fields)) {
        if (!in_array('received_by', $receipt_fields)) {
            $statement = "ALTER TABLE `{$receipts_table}` ADD `received_by` INT(11) NULL AFTER `order_id`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
        if (!in_array('nf_number', $receipt_fields)) {
            $statement = "ALTER TABLE `{$receipts_table}` ADD `nf_number` VARCHAR(100) NULL AFTER `received_by`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
    }

    $receipt_items_table = $db->prefixTable('purchases_goods_receipt_items');
    $receipt_item_fields = $db->getFieldNames($receipt_items_table);
    if (is_array($receipt_item_fields)) {
        if (!in_array('note', $receipt_item_fields)) {
            $statement = "ALTER TABLE `{$receipt_items_table}` ADD `note` TEXT NULL AFTER `quantity_received`";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
    }
} catch (\Throwable $e) {
    $result["success"] = false;
    $result["errors"][] = $e->getMessage();
}

try {
    $notification_settings_table = $db->prefixTable('notification_settings');
    $notification_events = array(
        "purchase_request_sent_for_quotation",
        "purchase_request_sent_to_quotation",
        "purchase_request_quotation_in_progress",
        "purchase_request_quotation_finalized",
        "purchase_request_awaiting_approval",
        "purchase_request_approval_partial",
        "purchase_request_approved_for_po",
        "purchase_request_rejected",
        "purchase_request_po_created",
        "purchase_request_po_sent",
        "purchase_request_partial_received",
        "purchase_request_received"
    );
    $sort = 900;
    foreach ($notification_events as $notification_event) {
        $notification_exists = $db->query("SELECT id FROM $notification_settings_table WHERE event='$notification_event' AND deleted=0")->getRow();
        if ($notification_exists) {
            $sort += 1;
            continue;
        }
        $statement = "INSERT INTO $notification_settings_table (event, category, enable_email, enable_web, enable_slack, notify_to_team, notify_to_team_members, notify_to_terms, sort, deleted)
            VALUES ('" . $notification_event . "', 'purchases', 1, 1, 0, '', '', '', " . $sort . ", 0)";
        $ok = $db->query($statement);
        $result["executed"][] = $statement;
        if (!$ok) {
            $result["success"] = false;
            $result["errors"][] = "Failed: " . $statement;
        }
        $sort += 1;
    }

    $email_templates_table = $db->prefixTable('email_templates');
    $template_name = "purchase_request_sent_for_quotation";
    $template_exists = $db->query("SELECT id FROM $email_templates_table WHERE template_name='$template_name' AND deleted=0")->getRow();
    if (!$template_exists) {
        $subject = "Nova requisicao de compra {REQUEST_CODE} para cotacao";
        $default_message = "<div style=\"background-color:#f7f7f7;padding:20px;\">
    <div style=\"max-width:640px;margin:0 auto;background-color:#ffffff;border-radius:4px;\">
        <div style=\"padding:20px 24px;\">
            <h2 style=\"margin:0 0 12px 0;\">Requisicao enviada para cotacao</h2>
            <p>Uma requisicao de compra foi enviada para cotacao.</p>
            <p><strong>Codigo:</strong> {REQUEST_CODE}</p>
            <p><strong>Prioridade:</strong> {REQUEST_PRIORITY}</p>
            <p><strong>Solicitado por:</strong> {REQUESTED_BY}</p>
            <p><strong>Observacao:</strong> {REQUEST_NOTE}</p>
            <p><a href=\"{REQUEST_URL}\" target=\"_blank\">Abrir requisicao</a></p>
            <p style=\"margin-top:20px;\">{SIGNATURE}</p>
        </div>
    </div>
</div>";
        $statement = "INSERT INTO $email_templates_table (template_name, email_subject, default_message, custom_message, template_type, language, deleted)
            VALUES (" . $db->escape($template_name) . ", " . $db->escape($subject) . ", " . $db->escape($default_message) . ", '', 'default', '', 0)";
        $ok = $db->query($statement);
        $result["executed"][] = $statement;
        if (!$ok) {
            $result["success"] = false;
            $result["errors"][] = "Failed: " . $statement;
        }
    }

    $template_name = "purchase_request_status_update";
    $template_exists = $db->query("SELECT id FROM $email_templates_table WHERE template_name='$template_name' AND deleted=0")->getRow();
    if (!$template_exists) {
        $subject = "Atualizacao da requisicao {REQUEST_CODE}";
        $default_message = "<div style=\"background-color:#f7f7f7;padding:20px;\">
    <div style=\"max-width:640px;margin:0 auto;background-color:#ffffff;border-radius:4px;\">
        <div style=\"padding:20px 24px;\">
            <h2 style=\"margin:0 0 12px 0;\">Atualizacao da requisicao</h2>
            <p>A requisicao foi atualizada para o status: <strong>{REQUEST_STATUS}</strong>.</p>
            <p><strong>Codigo:</strong> {REQUEST_CODE}</p>
            <p><strong>Prioridade:</strong> {REQUEST_PRIORITY}</p>
            <p><strong>Solicitado por:</strong> {REQUESTED_BY}</p>
            <p><strong>Observacao:</strong> {REQUEST_NOTE}</p>
            <p><a href=\"{REQUEST_URL}\" target=\"_blank\">Abrir requisicao</a></p>
            <p style=\"margin-top:20px;\">{SIGNATURE}</p>
        </div>
    </div>
</div>";
        $statement = "INSERT INTO $email_templates_table (template_name, email_subject, default_message, custom_message, template_type, language, deleted)
            VALUES (" . $db->escape($template_name) . ", " . $db->escape($subject) . ", " . $db->escape($default_message) . ", '', 'default', '', 0)";
        $ok = $db->query($statement);
        $result["executed"][] = $statement;
        if (!$ok) {
            $result["success"] = false;
            $result["errors"][] = "Failed: " . $statement;
        }
    }
} catch (\Throwable $e) {
    $result["success"] = false;
    $result["errors"][] = $e->getMessage();
}

try {
    $approvals_table = $db->prefixTable('purchases_request_approvals');
    $approvers_table = $db->prefixTable('purchases_approvers');
    $settings_table = $db->prefixTable('purchases_settings');

    $tables = array($approvals_table, $approvers_table, $settings_table);
    foreach ($tables as $table_name) {
        $exists = $db->query("SHOW TABLES LIKE '" . $table_name . "'")->getResult();
        if (!$exists) {
            // tables are created by install.sql; skip if missing
        }
    }

    $settings_defaults = array(
        "small_purchase_financial_optional" => "1",
        "buyer_small_limit" => "0",
        "requester_small_limit" => "0"
    );

    foreach ($settings_defaults as $key => $value) {
        $row = $db->query("SELECT id FROM $settings_table WHERE setting_key=" . $db->escape($key) . " AND deleted=0")->getRow();
        if (!$row) {
            $statement = "INSERT INTO $settings_table (company_id, setting_key, setting_value, deleted)
                VALUES (0, " . $db->escape($key) . ", " . $db->escape($value) . ", 0)";
            $ok = $db->query($statement);
            $result["executed"][] = $statement;
            if (!$ok) {
                $result["success"] = false;
                $result["errors"][] = "Failed: " . $statement;
            }
        }
    }
} catch (\Throwable $e) {
    $result["success"] = false;
    $result["errors"][] = $e->getMessage();
}

return $result;
