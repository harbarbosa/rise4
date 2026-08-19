<?php
namespace Engenharia\Services;
use App\Libraries\Pdf;
use Engenharia\Models\Laudos_model;
use Engenharia\Models\Settings_model;
use Engenharia\Models\Measurements_model;
use Engenharia\Models\Nonconformities_model;
use Engenharia\Models\Photos_model;
use Engenharia\Models\Report_versions_model;
class LaudoReportService
{
    private $laudos,$settings,$measurements,$nonconformities,$photos,$versions;
    public function __construct(){ $this->laudos=model(Laudos_model::class);$this->settings=model(Settings_model::class);$this->measurements=model(Measurements_model::class);$this->nonconformities=model(Nonconformities_model::class);$this->photos=model(Photos_model::class);$this->versions=model(Report_versions_model::class); }
    public function generate(int $laudo_id,bool $final=false,int $user_id=0):array
    {
        $laudo=$this->laudos->get_details(array('id'=>$laudo_id))->getRow();if(!$laudo)throw new \RuntimeException('Laudo não encontrado.');if($final&&$laudo->status!=='finalized')throw new \RuntimeException('O PDF final somente pode ser gerado após a finalização.');
        $settings=$this->settings->get_all_settings();$type=(strpos(strtoupper((string)$laudo->type_code),'SPDA')!==false)?'spda':'eletrico';$version=$this->versions->nextVersion($laudo_id);$data=array('laudo'=>$laudo,'settings'=>$settings,'measurements'=>$this->measurements->forLaudo($laudo_id)->getResult(),'nonconformities'=>$this->nonconformities->forLaudo($laudo_id)->getResult(),'photos'=>$this->photos->forLaudo($laudo_id)->getResult(),'version'=>$version,'final'=>$final,'template_code'=>$type,'generated_at'=>function_exists('get_my_local_time')?get_my_local_time():date('Y-m-d H:i:s'),'show_conforming'=>($settings['report_show_conforming']??'1')==='1','photos_per_page'=>(int)($settings['report_photos_per_page']??4));
        $html=view('Engenharia\\Views\\pdf\\'.$type,$data);$pdf=new Pdf('engenharia');$pdf->SetCreator(PDF_CREATOR);$pdf->SetAuthor($settings['company_name']??get_setting('company_name'));$pdf->SetTitle('Laudo '.$laudo->number);$pdf->SetSubject($laudo->title);$pdf->SetMargins(12,14,12);$pdf->SetAutoPageBreak(true,16);$pdf->setPrintHeader(false);$pdf->setPrintFooter(false);$pdf->AddPage();$pdf->writeHTML($html,true,false,true,false,'');$file_name=get_hyphenated_string('engenharia-'.$laudo->number.'-v'.$version.($final?'-final':'-rascunho')).'.pdf';$content=$pdf->Output($file_name,'S');$path='';if($final){$dir=FCPATH.'files/engenharia/laudos/'.$laudo_id.'/';if(!is_dir($dir))@mkdir($dir,0775,true);$path=$dir.$file_name;if(is_writable($dir))file_put_contents($path,$content);}$id=$this->versions->add(array('laudo_id'=>$laudo_id,'template_code'=>$type,'version'=>$version,'is_final'=>$final?1:0,'file_name'=>$file_name,'storage_path'=>$path,'generated_by'=>$user_id));return array('id'=>$id,'file_name'=>$file_name,'content'=>$content,'html'=>$html,'path'=>$path,'version'=>$version,'final'=>$final);
    }
}
