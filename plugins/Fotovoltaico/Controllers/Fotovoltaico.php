<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Services\FvCalculationService;
use Fotovoltaico\Services\FvIrradiationService;

/**
 * Controller principal do plugin Fotovoltaico.
 * Responsável pelo CRUD de projetos e pelo wizard inicial.
 */
class Fotovoltaico extends Security_Controller
{
    /** @var \Fotovoltaico\Models\Fv_projects_model */
    private $projects_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->projects_model = model('Fotovoltaico\\Models\\Fv_projects_model');
    }

    /**
     * Lista principal de projetos FV.
     */
    public function index()
    {
        return $this->template->rander('Fotovoltaico\\Views\\projects\\index');
    }

    /**
     * Retorna dados dos projetos em formato JSON para appTable.
     */
    public function list_data()
    {
        $db = db_connect('default');
        $projects_table = $db->prefixTable('fv_projects');
        $clients_table = $db->prefixTable('clients');

        if (!$db->tableExists($projects_table)) {
            return $this->response->setJSON(array(
                'data' => array(),
                'message' => app_lang('error_occurred')
            ));
        }

        $rows = $db->table($projects_table)
            ->select("$projects_table.id, $projects_table.title, $projects_table.status, $projects_table.city, $projects_table.state, $projects_table.created_at, $clients_table.company_name")
            ->join($clients_table, "$clients_table.id = $projects_table.client_id", 'left')
            ->orderBy("$projects_table.id", 'DESC')
            ->get()
            ->getResult();

        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_make_project_row($row);
        }

        return $this->response->setJSON(array('data' => $data));
    }

    /**
     * Modal de criação/edição de projeto FV.
     */
    public function modal_form()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $project = $id ? $this->projects_model->get_one($id) : null;

        $clients = $this->_get_clients_dropdown();

        $view_data = array(
            'project' => $project,
            'clients' => $clients
        );

        return $this->template->view('Fotovoltaico\\Views\\projects\\modal_form', $view_data);
    }

    /**
     * Salva projeto FV (criação ou edição).
     */
    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        $id = (int)$this->request->getPost('id');

        $db = db_connect('default');
        $projects_table = $db->prefixTable('fv_projects');
        if (!$db->tableExists($projects_table)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('error_occurred')
            ));
        }

        $data = array(
            'client_id' => (int)$this->request->getPost('client_id'),
            'title' => trim((string)$this->request->getPost('title')),
            'status' => trim((string)$this->request->getPost('status')) ?: 'draft',
            'city' => trim((string)$this->request->getPost('city')),
            'state' => trim((string)$this->request->getPost('state')),
            'lat' => $this->_parse_decimal($this->request->getPost('lat')),
            'lon' => $this->_parse_decimal($this->request->getPost('lon')),
            'created_by' => $this->login_user->id
        );

        $save_id = $this->projects_model->ci_save($data, $id);
        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $row_id = $id ? $id : $save_id;
        $row = $this->projects_model->get_one($row_id);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_make_project_row($row),
            'id' => $row_id,
            'message' => app_lang('record_saved')
        ));
    }

    /**
     * Remove um projeto FV.
     */
    public function delete()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $db = db_connect('default');
        $projects_table = $db->prefixTable('fv_projects');
        $deleted = $db->table($projects_table)->delete(array('id' => $id));

        return $this->response->setJSON(array(
            'success' => $deleted ? true : false,
            'message' => app_lang('record_deleted')
        ));
    }

    /**
     * Visualização do projeto FV com acesso ao wizard.
     */
    public function view($id = 0)
    {
        $id = (int)$id;
        $project = $this->projects_model->get_one($id);
        if (!$project || !$project->id) {
            show_404();
        }

        $db = db_connect('default');
        $fin_table = $db->prefixTable('fv_financial_results');
        $energy_table = $db->prefixTable('fv_energy_results_12m');
        $financial = null;
        $annual_generation = 0;
        if ($db->tableExists($fin_table)) {
            $financial = $db->table($fin_table)->where('project_version_id', $id)->get()->getRow();
        }
        if ($db->tableExists($energy_table)) {
            $sum = $db->table($energy_table)
                ->selectSum('energy_generated_kwh')
                ->where('project_version_id', $id)
                ->get()
                ->getRow();
            $annual_generation = $sum ? (float)$sum->energy_generated_kwh : 0;
        }
        $profiles = [];
        $profiles_table = $db->prefixTable('fv_regulatory_profiles');
        if ($db->tableExists($profiles_table)) {
            $profiles = $db->table($profiles_table)->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResult();
        }
        $utilities = [];
        $utilities_table = $db->prefixTable('fv_utilities');
        if ($db->tableExists($utilities_table)) {
            $utilities = $db->table($utilities_table)->orderBy('name', 'ASC')->get()->getResult();
        }
        $tariff_snapshot = $this->_get_latest_tariff_snapshot($id);
        $kits = [];
        $kits_table = $db->prefixTable('fv_kits');
        if ($db->tableExists($kits_table)) {
            $kits = $db->table($kits_table)->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResult();
        }
        $proposals = [];
        $proposals_table = $db->prefixTable('fv_proposals');
        if ($db->tableExists($proposals_table)) {
            $proposals = $db->table($proposals_table)->where('project_version_id', $id)->orderBy('id', 'DESC')->get()->getResult();
        }

        return $this->template->rander('Fotovoltaico\\Views\\projects\\view', array(
            'project' => $project,
            'financial' => $financial,
            'annual_generation' => $annual_generation,
            'reg_profiles' => $profiles,
            'utilities' => $utilities,
            'tariff_snapshot' => $tariff_snapshot,
            'kits' => $kits,
            'proposals' => $proposals
        ));
    }

    /**
     * Recalcula resultados energéticos e financeiros.
     */
    public function calculate($version_id = 0)
    {
        $version_id = (int)$version_id;
        $inputs = $this->request->getPost();

        $defaults = [
            'system_power_kwp' => 0,
            'losses_percent' => 14,
            'irradiation_monthly' => array_fill(0, 12, 0),
            'tariff_value' => 0,
            'tariff_mode' => 'total',
            'tariff_te' => 0,
            'tariff_tusd' => 0,
            'tariff_flags' => 0,
            'tariff_growth_percent_year' => 0,
            'degradation_percent_year' => 0.5,
            'investment_value' => 0,
            'opex_year' => 0,
            'discount_rate_percent' => 8,
            'offset_percent' => 100
        ];

        $inputs = array_merge($defaults, $inputs);
        if (is_string($inputs['irradiation_monthly'])) {
            $decoded = json_decode($inputs['irradiation_monthly'], true);
            if (is_array($decoded)) {
                $inputs['irradiation_monthly'] = $decoded;
            }
        }
        if (empty($inputs['irradiation_monthly']) || $this->_is_empty_irradiation($inputs['irradiation_monthly'])) {
            $snapshot = $this->_get_latest_irradiation_snapshot($version_id);
            if (!empty($snapshot['monthly'])) {
                $inputs['irradiation_monthly'] = $snapshot['monthly'];
            }
        }

        if (!empty($inputs['regulatory_snapshot']) && is_string($inputs['regulatory_snapshot'])) {
            $snap = json_decode($inputs['regulatory_snapshot'], true);
            if (is_array($snap)) {
                $inputs['regulatory_snapshot'] = $snap;
            }
        }

        if (empty($inputs['regulatory_snapshot'])) {
            $inputs['regulatory_snapshot'] = $this->_get_latest_regulatory_snapshot($version_id);
        }

        if (!empty($inputs['tariff_snapshot']) && is_string($inputs['tariff_snapshot'])) {
            $snap = json_decode($inputs['tariff_snapshot'], true);
            if (is_array($snap)) {
                $inputs['tariff_snapshot'] = $snap;
            }
        }

        if (!empty($inputs['tariff_snapshot'])) {
            $inputs = array_merge($inputs, $inputs['tariff_snapshot']);
        }
        if (empty($inputs['tariff_snapshot'])) {
            $inputs['tariff_snapshot'] = $this->_get_latest_tariff_snapshot($version_id);
            if (!empty($inputs['tariff_snapshot'])) {
                $inputs = array_merge($inputs, $inputs['tariff_snapshot']);
            }
        }

        $service = new FvCalculationService();
        $result = $service->runFullCalculation($version_id, $inputs);

        return $this->response->setJSON(array(
            'success' => true,
            'generation_annual' => $result['generation_annual'],
            'savings_year1' => $result['savings_year1'],
            'payback' => $result['payback'],
            'irr' => $result['irr'],
            'npv' => $result['npv']
        ));
    }

    public function regulatory_snapshot_save($version_id = 0)
    {
        $version_id = (int)$version_id;
        $profile_id = (int)$this->request->getPost('profile_id');
        $snapshot_json = trim((string)$this->request->getPost('snapshot_json'));

        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_regulatory_snapshots');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $db->table($table)->insert([
            'project_version_id' => $version_id,
            'profile_id' => $profile_id ?: null,
            'snapshot_json' => $snapshot_json ?: null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved')]);
    }

    public function tariff_snapshot_save($version_id = 0)
    {
        $version_id = (int)$version_id;
        $utility_id = (int)$this->request->getPost('utility_id');
        $tariff_id = (int)$this->request->getPost('tariff_id');
        $snapshot_json = trim((string)$this->request->getPost('snapshot_json'));

        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_tariff_snapshots');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $db->table($table)->insert([
            'project_version_id' => $version_id,
            'utility_id' => $utility_id ?: null,
            'tariff_id' => $tariff_id ?: null,
            'snapshot_json' => $snapshot_json ?: null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved')]);
    }

    public function irradiation_fetch()
    {
        $lat = $this->request->getPost('lat');
        $lon = $this->request->getPost('lon');
        $provider = $this->request->getPost('provider') ?: 'pvgis';

        if ($lat === null || $lon === null) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('fv_invalid_coordinates')]);
        }

        $service = new FvIrradiationService();
        $result = $service->getMonthlyIrradiation($lat, $lon, $provider);
        if (!$result) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('fv_irradiation_failed')]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'provider' => $result['provider'] ?? $provider,
                'monthly' => $result['monthly'],
                'annual' => $result['annual']
            ]
        ]);
    }

    public function irradiation_snapshot_save($version_id = 0)
    {
        $version_id = (int)$version_id;
        $provider = trim((string)$this->request->getPost('provider'));
        $lat = $this->request->getPost('lat');
        $lon = $this->request->getPost('lon');
        $monthly_json = $this->request->getPost('monthly_json');
        $annual_value = $this->request->getPost('annual_value');

        $decoded = is_string($monthly_json) ? json_decode($monthly_json, true) : null;
        if (!is_array($decoded) || count($decoded) !== 12) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('fv_irradiation_12')]);
        }

        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_irradiation_snapshots');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $db->table($table)->insert([
            'project_version_id' => $version_id,
            'provider' => $provider ?: null,
            'lat' => $lat !== '' ? $lat : null,
            'lon' => $lon !== '' ? $lon : null,
            'monthly_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
            'annual_value' => $annual_value !== '' ? $annual_value : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved')]);
    }

    public function proposal_generate($project_id = 0)
    {
        $project_id = (int)$project_id;
        $project = $this->projects_model->get_one($project_id);
        if (!$project || !$project->id) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $kit_id = (int)$this->request->getPost('kit_id');
        $result = $this->_generate_proposal_pdf($project, $kit_id);
        if (!$result['success']) {
            return $this->response->setJSON(['success' => false, 'message' => $result['message']]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => app_lang('record_saved'),
            'proposal_id' => $result['proposal_id']
        ]);
    }

    public function assistant()
    {
        $db = db_connect('default');
        $clients = [];
        $clients_table = $db->prefixTable('clients');
        if ($db->tableExists($clients_table)) {
            $clients = $db->table($clients_table)->select('id,company_name')->where('deleted', 0)->orderBy('company_name', 'ASC')->get()->getResult();
        }
        $kits = [];
        $kits_table = $db->prefixTable('fv_kits');
        if ($db->tableExists($kits_table)) {
            $kits = $db->table($kits_table)->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResult();
        }
        $profiles = [];
        $profiles_table = $db->prefixTable('fv_regulatory_profiles');
        if ($db->tableExists($profiles_table)) {
            $profiles = $db->table($profiles_table)->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResult();
        }

        return $this->template->rander('Fotovoltaico\\Views\\assistant\\index', [
            'clients' => $clients,
            'kits' => $kits,
            'profiles' => $profiles
        ]);
    }

    public function assistant_generate()
    {
        $client_id = (int)$this->request->getPost('client_id');
        $kit_id = (int)$this->request->getPost('kit_id');
        $profile_id = (int)$this->request->getPost('profile_id');
        $consumption = $this->request->getPost('consumption_kwh_month');
        $cep = trim((string)$this->request->getPost('cep'));
        $lat = $this->request->getPost('lat');
        $lon = $this->request->getPost('lon');
        $tariff_snapshot = $this->request->getPost('tariff_snapshot');

        if (!$client_id) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('fv_missing_client')]);
        }
        if (!$kit_id) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('fv_missing_kit')]);
        }

        $project_id = $this->_create_project_from_assistant($client_id);

        $this->_save_assistant_data($project_id, $cep, $consumption);

        if ($profile_id) {
            $this->_save_regulatory_from_profile($project_id, $profile_id);
        }

        if ($tariff_snapshot) {
            $this->_save_tariff_snapshot_json($project_id, $tariff_snapshot);
        }

        $irr_monthly = $this->request->getPost('irradiation_monthly');
        if (is_string($irr_monthly)) {
            $decoded = json_decode($irr_monthly, true);
            if (is_array($decoded)) {
                $irr_monthly = $decoded;
            }
        }

        if ((!is_array($irr_monthly) || count($irr_monthly) !== 12) && $lat !== '' && $lon !== '') {
            $service = new FvIrradiationService();
            $result = $service->getMonthlyIrradiation($lat, $lon, $this->request->getPost('irradiation_provider') ?: 'pvgis');
            if ($result && !empty($result['monthly'])) {
                $irr_monthly = $result['monthly'];
                $this->_save_irradiation_snapshot($project_id, $result['provider'] ?? null, $lat, $lon, $irr_monthly, $result['annual'] ?? null);
            }
        } elseif (is_array($irr_monthly) && count($irr_monthly) === 12) {
            $annual = array_sum($irr_monthly);
            $this->_save_irradiation_snapshot($project_id, $this->request->getPost('irradiation_provider'), $lat, $lon, $irr_monthly, $annual);
        }

        $inputs = [
            'system_power_kwp' => $this->request->getPost('system_power_kwp') ?? 0,
            'losses_percent' => $this->request->getPost('losses_percent') ?? 14,
            'irradiation_monthly' => is_array($irr_monthly) ? $irr_monthly : array_fill(0, 12, 0),
            'tariff_growth_percent_year' => $this->request->getPost('tariff_growth_percent_year') ?? 0,
            'degradation_percent_year' => $this->request->getPost('degradation_percent_year') ?? 0.5,
            'investment_value' => $this->request->getPost('investment_value') ?? 0,
            'opex_year' => $this->request->getPost('opex_year') ?? 0,
            'discount_rate_percent' => $this->request->getPost('discount_rate_percent') ?? 8,
            'offset_percent' => $this->request->getPost('offset_percent') ?? 100
        ];
        if ($tariff_snapshot) {
            $snap = json_decode($tariff_snapshot, true);
            if (is_array($snap)) {
                $inputs = array_merge($inputs, $snap);
            }
        }

        $service = new FvCalculationService();
        $service->runFullCalculation($project_id, $inputs);

        $proposal = $this->_generate_proposal_pdf($this->projects_model->get_one($project_id), $kit_id);
        if (!$proposal['success']) {
            return $this->response->setJSON(['success' => false, 'message' => $proposal['message']]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => app_lang('fv_assistant_done'),
            'project_id' => $project_id,
            'proposal_id' => $proposal['proposal_id']
        ]);
    }

    public function proposal_download($proposal_id = 0)
    {
        $proposal_id = (int)$proposal_id;
        $db = db_connect('default');
        $table = $db->prefixTable('fv_proposals');
        if (!$db->tableExists($table)) {
            show_404();
        }
        $row = $db->table($table)->where('id', $proposal_id)->get()->getRow();
        if (!$row || !$row->pdf_path) {
            show_404();
        }
        $path = $row->pdf_path;
        $dir = dirname($path) . DIRECTORY_SEPARATOR;
        $file_name = basename($path);
        $file_data = serialize([['file_name' => $file_name]]);
        return $this->download_app_files($dir, $file_data);
    }

    private function _get_latest_regulatory_snapshot($version_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_regulatory_snapshots');
        if (!$db->tableExists($table)) {
            return [];
        }
        $row = $db->table($table)->where('project_version_id', $version_id)->orderBy('id', 'DESC')->get()->getRow();
        if (!$row || !$row->snapshot_json) {
            return [];
        }
        $decoded = json_decode($row->snapshot_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function _get_latest_tariff_snapshot($version_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_tariff_snapshots');
        if (!$db->tableExists($table)) {
            return [];
        }
        $row = $db->table($table)->where('project_version_id', $version_id)->orderBy('id', 'DESC')->get()->getRow();
        if (!$row || !$row->snapshot_json) {
            return [];
        }
        $decoded = json_decode($row->snapshot_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function _get_latest_irradiation_snapshot($version_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_irradiation_snapshots');
        if (!$db->tableExists($table)) {
            return [];
        }
        $row = $db->table($table)->where('project_version_id', $version_id)->orderBy('id', 'DESC')->get()->getRow();
        if (!$row || !$row->monthly_json) {
            return [];
        }
        $decoded = json_decode($row->monthly_json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return [
            'provider' => $row->provider,
            'lat' => $row->lat,
            'lon' => $row->lon,
            'monthly' => $decoded,
            'annual' => (float)($row->annual_value ?? 0)
        ];
    }

    private function _is_empty_irradiation($monthly)
    {
        if (!is_array($monthly)) {
            return true;
        }
        $sum = 0;
        foreach ($monthly as $value) {
            $sum += (float)$value;
        }
        return $sum <= 0;
    }

    private function _create_project_from_assistant($client_id)
    {
        $title = 'Proposta FV - ' . date('d/m/Y H:i');
        $data = [
            'client_id' => $client_id,
            'title' => $title,
            'status' => 'draft',
            'created_by' => $this->login_user->id
        ];
        return $this->projects_model->ci_save($data);
    }

    private function _save_assistant_data($project_id, $cep, $consumption)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_assistant_data');
        if (!$db->tableExists($table)) {
            return;
        }
        $db->table($table)->insert([
            'project_version_id' => $project_id,
            'cep' => $cep ?: null,
            'consumption_kwh_month' => $consumption !== '' ? $consumption : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function _save_regulatory_from_profile($project_id, $profile_id)
    {
        $db = db_connect('default');
        $profiles_table = $db->prefixTable('fv_regulatory_profiles');
        $snap_table = $db->prefixTable('fv_project_regulatory_snapshots');
        if (!$db->tableExists($profiles_table) || !$db->tableExists($snap_table)) {
            return;
        }
        $profile = $db->table($profiles_table)->where('id', $profile_id)->get()->getRow();
        if (!$profile) {
            return;
        }
        $db->table($snap_table)->insert([
            'project_version_id' => $project_id,
            'profile_id' => $profile_id,
            'snapshot_json' => $profile->rules_json,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function _save_tariff_snapshot_json($project_id, $snapshot_json)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_tariff_snapshots');
        if (!$db->tableExists($table)) {
            return;
        }
        $db->table($table)->insert([
            'project_version_id' => $project_id,
            'snapshot_json' => $snapshot_json,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function _save_irradiation_snapshot($project_id, $provider, $lat, $lon, $monthly, $annual)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('fv_project_irradiation_snapshots');
        if (!$db->tableExists($table)) {
            return;
        }
        $db->table($table)->insert([
            'project_version_id' => $project_id,
            'provider' => $provider ?: null,
            'lat' => $lat !== '' ? $lat : null,
            'lon' => $lon !== '' ? $lon : null,
            'monthly_json' => json_encode($monthly, JSON_UNESCAPED_UNICODE),
            'annual_value' => $annual,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function _generate_proposal_pdf($project, $kit_id)
    {
        if (!$project || !$project->id) {
            return ['success' => false, 'message' => app_lang('error_occurred')];
        }

        $db = db_connect('default');
        $clients_table = $db->prefixTable('clients');
        $client = null;
        if ($db->tableExists($clients_table)) {
            $client = $db->table($clients_table)->where('id', $project->client_id)->get()->getRow();
        }

        $kit = null;
        $kit_items = [];
        if ($kit_id) {
            $kit_table = $db->prefixTable('fv_kits');
            if ($db->tableExists($kit_table)) {
                $kit = $db->table($kit_table)->where('id', $kit_id)->get()->getRow();
            }
            $kit_items_table = $db->prefixTable('fv_kit_items');
            $products_table = $db->prefixTable('fv_products');
            if ($db->tableExists($kit_items_table)) {
                $query = $db->table($kit_items_table . ' i')
                    ->select('i.*, p.brand, p.model, p.type, p.cost as product_cost, p.price as product_price, p.power_w as product_power')
                    ->join($products_table . ' p', 'p.id = i.product_id', 'left')
                    ->where('i.kit_id', $kit_id)
                    ->orderBy('i.sort_order', 'ASC')
                    ->get();
                $kit_items = $query ? $query->getResultArray() : [];
            }
        }

        $fin_table = $db->prefixTable('fv_financial_results');
        $financial = null;
        if ($db->tableExists($fin_table)) {
            $financial = $db->table($fin_table)->where('project_version_id', $project->id)->get()->getRow();
        }
        $energy_table = $db->prefixTable('fv_energy_results_12m');
        $monthly = [];
        if ($db->tableExists($energy_table)) {
            $monthly = $db->table($energy_table)->where('project_version_id', $project->id)->orderBy('month', 'ASC')->get()->getResultArray();
        }
        $annual_generation = 0;
        foreach ($monthly as $row) {
            $annual_generation += (float)$row['energy_generated_kwh'];
        }

        $data = [
            'project' => $project,
            'client' => $client,
            'kit' => $kit,
            'kit_items' => $kit_items,
            'financial' => $financial,
            'monthly' => $monthly,
            'annual_generation' => $annual_generation,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $html = view('Fotovoltaico\\Views\\proposals\\proposal_pdf', $data);
        $pdf = new \App\Libraries\Pdf('proposal');
        $file_name = "fv-proposta-{$project->id}";
        $temp_path = $pdf->PreparePDF($html, $file_name, "send_email");
        if (!$temp_path || !file_exists($temp_path)) {
            return ['success' => false, 'message' => app_lang('error_occurred')];
        }

        $proposals_table = $db->prefixTable('fv_proposals');
        $version = 1;
        if ($db->tableExists($proposals_table)) {
            $count = $db->table($proposals_table)->where('project_version_id', $project->id)->countAllResults();
            $version = $count + 1;
        }

        $dest_dir = WRITEPATH . "uploads/fotovoltaico/proposals/{$project->id}/";
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0775, true);
        }

        $dest_file = "v{$version}.pdf";
        $dest_path = $dest_dir . $dest_file;
        @rename($temp_path, $dest_path);

        if (!file_exists($dest_path)) {
            return ['success' => false, 'message' => app_lang('error_occurred')];
        }

        $total_value = null;
        if ($kit_items) {
            $sum = 0;
            foreach ($kit_items as $item) {
                $price = $item['item_type'] === 'custom' ? (float)$item['price'] : (float)$item['product_price'];
                $qty = (float)$item['qty'];
                $sum += $price * $qty;
            }
            $total_value = $sum;
        }

        if ($db->tableExists($proposals_table)) {
            $db->table($proposals_table)->insert([
                'project_version_id' => $project->id,
                'pdf_path' => $dest_path,
                'total_value' => $total_value,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $proposal_id = $db->insertID();
        } else {
            $proposal_id = 0;
        }

        return ['success' => true, 'proposal_id' => $proposal_id];
    }

    /**
     * Aba de propostas FV dentro do cliente.
     */
    public function client_projects($client_id = 0)
    {
        $client_id = (int)$client_id;
        $db = db_connect('default');
        $projects_table = $db->prefixTable('fv_projects');
        $rows = $db->table($projects_table)
            ->select('id,title,status,created_at')
            ->where('client_id', $client_id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        return $this->template->view('Fotovoltaico\\Views\\projects\\client_tab', array(
            'rows' => $rows
        ));
    }

    /**
     * Wizard de proposta FV.
     */
    public function wizard($project_id = 0, $step = 1)
    {
        $project_id = (int)$project_id;
        $step = (int)$step ?: 1;

        $project = $this->projects_model->get_one($project_id);
        if (!$project || !$project->id) {
            show_404();
        }

        $client = null;
        $db = db_connect('default');
        $clients_table = $db->prefixTable('clients');
        if ($db->tableExists($clients_table)) {
            $client = $db->table($clients_table)->where('id', $project->client_id)->get()->getRow();
        }

        $utilities = [];
        $utilities_table = $db->prefixTable('fv_utilities');
        if ($db->tableExists($utilities_table)) {
            $utilities = $db->table($utilities_table)->orderBy('name', 'ASC')->get()->getResult();
        }

        $view_data = array(
            'project' => $project,
            'step' => $step,
            'client' => $client,
            'utilities' => $utilities
        );

        return $this->template->rander('Fotovoltaico\\Views\\wizard\\step' . $step, $view_data);
    }

    /**
     * Wizard em modal.
     */
    public function wizard_modal($project_id = 0, $step = 1)
    {
        $project_id = (int)$project_id;
        $step = (int)$step ?: 1;

        $project = $this->projects_model->get_one($project_id);
        if (!$project || !$project->id) {
            show_404();
        }

        $client = null;
        $db = db_connect('default');
        $clients_table = $db->prefixTable('clients');
        if ($db->tableExists($clients_table)) {
            $client = $db->table($clients_table)->where('id', $project->client_id)->get()->getRow();
        }

        $utilities = [];
        $utilities_table = $db->prefixTable('fv_utilities');
        if ($db->tableExists($utilities_table)) {
            $utilities = $db->table($utilities_table)->orderBy('name', 'ASC')->get()->getResult();
        }

        $view_data = array(
            'project' => $project,
            'step' => $step,
            'client' => $client,
            'utilities' => $utilities
        );

        return $this->template->view('Fotovoltaico\\Views\\wizard\\step' . $step, $view_data);
    }

    /**
     * Monta linha de projeto para listagem.
     */
    private function _make_project_row($row)
    {
        $title = esc($row->title);
        $status = esc($row->status);
        $city = esc($row->city ?? '-');
        $state = esc($row->state ?? '-');
        $client = esc($row->company_name ?? '-');

        $actions = modal_anchor(get_uri('fotovoltaico/projects_modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
            'title' => app_lang('edit'),
            'data-post-id' => $row->id,
            'class' => 'btn btn-sm btn-outline-secondary'
        ));
        $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
            'title' => app_lang('delete'),
            'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $row->id,
            'data-action-url' => get_uri('fotovoltaico/projects_delete'),
            'data-action' => 'delete-confirmation'
        ));
        $actions .= ' ' . anchor(get_uri('fotovoltaico/projects_view/' . $row->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'title' => app_lang('view'),
            'class' => 'btn btn-sm btn-outline-primary'
        ));

        return array(
            $title,
            $client,
            $status,
            $city,
            $state,
            format_to_datetime($row->created_at),
            $actions
        );
    }

    /**
     * Busca clientes para dropdown.
     */
    private function _get_clients_dropdown()
    {
        $db = db_connect('default');
        $clients_table = $db->prefixTable('clients');
        $rows = $db->table($clients_table)
            ->select('id,company_name')
            ->where('deleted', 0)
            ->orderBy('company_name', 'ASC')
            ->get()
            ->getResult();

        $options = array('' => '-');
        foreach ($rows as $row) {
            $options[$row->id] = $row->company_name;
        }

        return $options;
    }

    /**
     * Converte texto para decimal.
     */
    private function _parse_decimal($value)
    {
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text);
        $last_comma = strrpos($text, ',');
        $last_dot = strrpos($text, '.');

        if ($last_comma !== false && $last_dot !== false) {
            if ($last_comma > $last_dot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($last_comma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }

        return (float)$text;
    }
}
