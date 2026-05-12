<?php

namespace GED\Models;

use App\Models\Crud_model;

abstract class GedBaseModel extends Crud_model
{
    protected $useDeletedAt = false;

    public function __construct($table = null, $db = null)
    {
        parent::__construct($table, $db);
        $this->useDeletedAt = in_array('deleted_at', $this->db->getFieldNames($this->table), true);
    }

    public function get_one($id = 0)
    {
        if ($this->useDeletedAt) {
            return $this->get_one_where(array('id' => $id, 'deleted_at' => null));
        }

        return parent::get_one($id);
    }

    public function delete($id = 0, $undo = false)
    {
        validate_numeric_value($id);

        if (!$this->useDeletedAt) {
            $this->db_builder->where('id', $id);
            $success = $this->db_builder->delete();

            try {
                app_hooks()->do_action("app_hook_data_delete", array(
                    "id" => $id,
                    "table" => $this->table,
                    "table_without_prefix" => $this->table_without_prefix,
                ));
            } catch (\Exception $ex) {
                log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
            }

            return $success;
        }

        $data = array('deleted_at' => $undo ? null : get_current_utc_time());
        $this->db_builder->where('id', $id);
        $success = $this->db_builder->update($data);

        try {
            app_hooks()->do_action("app_hook_data_delete", array(
                "id" => $id,
                "table" => $this->table,
                "table_without_prefix" => $this->table_without_prefix,
            ));
        } catch (\Exception $ex) {
            log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
        }

        return $success;
    }
}
