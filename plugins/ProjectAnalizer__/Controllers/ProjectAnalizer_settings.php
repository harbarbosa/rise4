<?php

namespace ProjectAnalizer\Controllers;

use App\Controllers\Security_Controller;
use ProjectAnalizer\Models\Labor_profiles_model;

class ProjectAnalizer_settings extends Security_Controller
{
    protected $labor_profiles_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->labor_profiles_model = new Labor_profiles_model();
    }

    public function index()
    {
        $this->_sync_labor_profile_custom_field();
        return $this->template->rander("ProjectAnalizer\\Views\\settings\\index");
    }

    public function logs()
    {
        $logs_dir = WRITEPATH . "logs";
        $files = [];
        if (is_dir($logs_dir)) {
            foreach (glob($logs_dir . DIRECTORY_SEPARATOR . "*.log") as $path) {
                $files[] = [
                    "name" => basename($path),
                    "path" => $path,
                    "mtime" => filemtime($path) ?: 0,
                    "size" => filesize($path) ?: 0
                ];
            }
        }

        usort($files, function ($a, $b) {
            return $b["mtime"] <=> $a["mtime"];
        });

        $selected = $this->request->getGet("file");
        if ($selected) {
            $selected = basename($selected);
        }
        if (!$selected && !empty($files)) {
            $selected = $files[0]["name"];
        }

        $content = "";
        if ($selected) {
            $path = $logs_dir . DIRECTORY_SEPARATOR . $selected;
            if (is_file($path)) {
                $content = $this->_tail_file($path, 500);
            }
        }

        return $this->template->rander("ProjectAnalizer\\Views\\settings\\logs", [
            "files" => $files,
            "selected" => $selected,
            "content" => $content
        ]);
    }

    public function labor_profile_modal_form()
    {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = (int)$this->request->getPost("id");
        $view_data = array();
        $view_data["model_info"] = $id ? $this->labor_profiles_model->get_one($id) : null;

        return $this->template->view("ProjectAnalizer\\Views\\settings\\labor_profile_modal_form", $view_data);
    }

    public function save_labor_profile()
    {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "hourly_cost" => "required"
        ));

        $id = (int)$this->request->getPost("id");
        $data = array(
            "name" => $this->request->getPost("name"),
            "hourly_cost" => $this->request->getPost("hourly_cost"),
            "default_hours_per_day" => $this->request->getPost("default_hours_per_day"),
            "active" => $this->request->getPost("active") ? 1 : 0
        );

        $saved = $id ? $this->labor_profiles_model->update_profile($id, $data) : $this->labor_profiles_model->create_profile($data);
        if ($saved) {
            $this->_sync_labor_profile_custom_field();
            echo json_encode(array("success" => true, "message" => app_lang("record_saved")));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang("error_occurred")));
        }
    }

    public function delete_labor_profile()
    {
        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = (int)$this->request->getPost("id");
        if ($this->labor_profiles_model->delete_profile($id)) {
            $this->_sync_labor_profile_custom_field();
            echo json_encode(array("success" => true, "message" => app_lang("record_deleted")));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang("error_occurred")));
        }
    }

    public function labor_profiles_list_data()
    {
        $query = $this->labor_profiles_model->get_details();
        $list_data = $query ? $query->getResult() : array();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_labor_profile_row($data);
        }

        echo json_encode(array("data" => $result));
    }

    private function _make_labor_profile_row($data)
    {
        $status_label = $data->active ? "<span class='badge bg-success'>" . app_lang("active") . "</span>" : "<span class='badge bg-secondary'>" . app_lang("inactive") . "</span>";
        $options = modal_anchor(get_uri("projectanalizer_settings/labor_profile_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang("edit"), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array("title" => app_lang("delete"), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projectanalizer_settings/delete_labor_profile"), "data-action" => "delete"));

        return array(
            $data->name,
            to_decimal_format($data->hourly_cost),
            to_decimal_format($data->default_hours_per_day),
            $status_label,
            $options
        );
    }

    private function _sync_labor_profile_custom_field()
    {
        $db = db_connect("default");
        $table = $db->prefixTable("custom_fields");

        $row = $db->query("SELECT id FROM $table WHERE related_to='team_members' AND title_language_key='labor_profile' AND deleted=0 ORDER BY id DESC LIMIT 1")->getRow();
        $field_id = $row && $row->id ? (int)$row->id : 0;

        if (!$field_id) {
            $max_sort = $db->query("SELECT MAX(sort) AS max_sort FROM $table WHERE related_to='team_members' AND deleted=0")->getRow();
            $sort = $max_sort && $max_sort->max_sort ? ((int)$max_sort->max_sort + 1) : 1;
            $insert_data = array(
                "title" => app_lang("labor_profile"),
                "title_language_key" => "labor_profile",
                "placeholder_language_key" => "labor_profile",
                "show_in_embedded_form" => 0,
                "placeholder" => "",
                "template_variable_name" => "LABOR_PROFILE",
                "options" => "",
                "field_type" => "select",
                "related_to" => "team_members",
                "sort" => $sort,
                "required" => 0,
                "add_filter" => 0,
                "show_in_table" => 0,
                "show_in_invoice" => 0,
                "show_in_estimate" => 0,
                "show_in_contract" => 0,
                "show_in_order" => 0,
                "show_in_proposal" => 0,
                "visible_to_admins_only" => 0,
                "hide_from_clients" => 0,
                "disable_editing_by_clients" => 0,
                "show_on_kanban_card" => 0,
                "deleted" => 0,
                "show_in_subscription" => 0
            );
            $db->table($table)->insert($insert_data);
            $field_id = $db->insertID();
        }

        if ($field_id) {
            $profiles_table = $db->prefixTable("pa_labor_profiles");
            $profiles = $db->query("SELECT name FROM $profiles_table WHERE active=1 ORDER BY name ASC")->getResult();
            $options = array();
            foreach ($profiles as $profile) {
                if ($profile->name) {
                    $options[] = $profile->name;
                }
            }
            $options_str = implode(",", $options);
            $db->table($table)->where("id", $field_id)->update(array("options" => $options_str));
        }
    }

    private function _tail_file($path, $lines = 300)
    {
        $handle = @fopen($path, "rb");
        if (!$handle) {
            return "";
        }

        $buffer = "";
        $chunk_size = 4096;
        fseek($handle, 0, SEEK_END);
        $pos = ftell($handle);
        $line_count = 0;

        while ($pos > 0 && $line_count <= $lines) {
            $read_size = ($pos - $chunk_size) > 0 ? $chunk_size : $pos;
            $pos -= $read_size;
            fseek($handle, $pos, SEEK_SET);
            $chunk = fread($handle, $read_size);
            $buffer = $chunk . $buffer;
            $line_count = substr_count($buffer, "\n");
        }

        fclose($handle);
        $parts = explode("\n", $buffer);
        $parts = array_slice($parts, -$lines);
        return implode("\n", $parts);
    }
}
