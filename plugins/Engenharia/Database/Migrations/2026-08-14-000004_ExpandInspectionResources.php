<?php
namespace Engenharia\Database\Migrations;
use CodeIgniter\Database\Migration;
class ExpandInspectionResources extends Migration
{
    public function up(){
        $this->add('eng_measurements',array('measurement_type'=>array('type'=>'VARCHAR','constraint'=>80,'null'=>true),'point_identifier'=>array('type'=>'VARCHAR','constraint'=>120,'null'=>true),'result_classification'=>array('type'=>'VARCHAR','constraint'=>120,'null'=>true),'checklist_item_id'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true)));
        $this->add('eng_instruments',array('calibration_certificate'=>array('type'=>'VARCHAR','constraint'=>190,'null'=>true),'calibration_valid_until'=>array('type'=>'DATE','null'=>true),'certificate_file_id'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true)));
        $this->add('eng_photos',array('title'=>array('type'=>'VARCHAR','constraint'=>190,'null'=>true),'captured_at'=>array('type'=>'DATETIME','null'=>true),'optimized_file_name'=>array('type'=>'VARCHAR','constraint'=>255,'null'=>true),'optimized_storage_path'=>array('type'=>'TEXT','null'=>true),'nc_id'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true)));
        $this->add('eng_nonconformities',array('code'=>array('type'=>'VARCHAR','constraint'=>80,'null'=>true),'reference'=>array('type'=>'VARCHAR','constraint'=>190,'null'=>true),'evidence'=>array('type'=>'TEXT','null'=>true),'review_observation'=>array('type'=>'TEXT','null'=>true)));
    }
    public function down(){}
    private function add(string $table,array $columns){$full=$this->db->prefixTable($table);if(!$this->db->tableExists($full))return;$fields=$this->db->getFieldNames($full);foreach($columns as $name=>$definition){if(in_array($name,$fields,true))continue;try{$this->forge->addColumn($table,array($name=>$definition));}catch(\Throwable $e){if(stripos($e->getMessage(),'duplicate column')===false)throw $e;}}}
}
