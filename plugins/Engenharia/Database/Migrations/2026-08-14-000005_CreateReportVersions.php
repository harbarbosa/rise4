<?php
namespace Engenharia\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateReportVersions extends Migration
{
    public function up(){if($this->db->tableExists($this->db->prefixTable('eng_report_versions')))return;$this->forge->addField(array('id'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true,'auto_increment'=>true),'laudo_id'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true),'template_code'=>array('type'=>'VARCHAR','constraint'=>50),'version'=>array('type'=>'INT','constraint'=>11,'default'=>1),'is_final'=>array('type'=>'TINYINT','constraint'=>1,'default'=>0),'file_name'=>array('type'=>'VARCHAR','constraint'=>255,'null'=>true),'storage_path'=>array('type'=>'TEXT','null'=>true),'generated_by'=>array('type'=>'INT','constraint'=>11,'unsigned'=>true),'created_at'=>array('type'=>'DATETIME','null'=>true)));$this->forge->addKey('id',true);$this->forge->addKey(array('laudo_id','version'));try{$this->forge->createTable('eng_report_versions',true,array('ENGINE'=>'InnoDB','DEFAULT CHARSET'=>'utf8mb4','COLLATE'=>'utf8mb4_unicode_ci'));}catch(\Throwable $e){if(stripos($e->getMessage(),'already exists')===false)throw $e;}}
    public function down(){$this->forge->dropTable('eng_report_versions',true);}
}
