<?php
namespace Engenharia\Models;
class Checklist_items_model extends EngenhariaBaseModel {
    protected $table = 'eng_checklist_items'; public function __construct(){ parent::__construct($this->table); }
    public function forChecklist(int $checklist_id){ return $this->db->table($this->db->prefixTable($this->table))->where('checklist_id',$checklist_id)->where('deleted',0)->orderBy('sort','ASC')->get(); }
    public function save_record(array $data, int $id = 0): int { $table=$this->db->prefixTable($this->table); if($id){$data['updated_at']=date('Y-m-d H:i:s');$this->db->table($table)->where('id',$id)->update($data);return $id;} $data+=array('sort'=>0,'required'=>0,'allow_observation'=>1,'requires_photo'=>0,'requires_measurement'=>0,'is_active'=>1,'deleted'=>0,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'));$this->db->table($table)->insert($data);return (int)$this->db->insertID(); }
    public function changeSort(int $id, int $delta): bool { $row=$this->get_one($id); if(!$row)return false; return (bool)$this->db->table($this->db->prefixTable($this->table))->where('id',$id)->update(array('sort'=>max(0,(int)$row->sort+$delta),'updated_at'=>date('Y-m-d H:i:s'))); }
}
