<?php

namespace PontoRH\Libraries;

class Solides_monitor_service
{
    private $db;
    private $settings;

    public function __construct()
    {
        $this->db = db_connect();
        $this->settings = model('PontoRH\\Models\\PontoRh_settings_model');
    }

    public function configured(): bool
    {
        return trim($this->settings->get_setting('solides_api_token', '')) !== '';
    }

    public function sync(string $startDate = '', string $endDate = ''): array
    {
        if (!$this->configured()) {
            return array('success' => false, 'message' => 'Configure o token da API Solides antes de sincronizar.');
        }
        $startDate = $startDate ?: date('Y-m-d', strtotime('-7 days'));
        $endDate = $endDate ?: date('Y-m-d');
        $base = rtrim($this->settings->get_setting('solides_api_base_url', 'https://api.tangerino.com.br'), '/');
        $endpoint = $this->settings->get_setting('solides_punches_endpoint', '/api/v1/punch');
        $url = $base . $endpoint . '?' . http_build_query(array('startDate' => $startDate, 'endDate' => $endDate));
        try {
            $client = \Config\Services::curlrequest(array('timeout' => 30, 'http_errors' => false));
            $response = $client->get($url, array('headers' => array('Authorization' => 'Bearer ' . $this->settings->get_setting('solides_api_token'), 'Accept' => 'application/json')));
            if ($response->getStatusCode() >= 300) {
                return array('success' => false, 'message' => 'Solides respondeu HTTP ' . $response->getStatusCode() . '. Confira URL, endpoint e token.');
            }
            $payload = json_decode((string) $response->getBody(), true);
            $items = $this->extractItems($payload);
            $saved = 0;
            foreach ($items as $item) {
                if ($this->storePunch($item)) { $saved++; }
            }
            $this->rebuildAlerts($startDate, $endDate);
            $this->settings->save_setting('solides_last_sync_at', get_current_utc_time());
            return array('success' => true, 'message' => 'Sincronizacao concluida.', 'received' => count($items), 'saved' => $saved);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH/Solides] ' . $e->getMessage());
            return array('success' => false, 'message' => 'Falha ao consultar a Solides: ' . $e->getMessage());
        }
    }

    private function extractItems($payload): array
    {
        if (!is_array($payload)) { return array(); }
        foreach (array('data','content','items','records','punches') as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) { return $payload[$key]; }
        }
        return array_is_list($payload) ? $payload : array();
    }

    private function value(array $item, array $keys, $default = '')
    {
        foreach ($keys as $key) { if (isset($item[$key]) && $item[$key] !== '') { return $item[$key]; } }
        return $default;
    }

    private function storePunch(array $item): bool
    {
        $employeeId = (string) $this->value($item, array('employeeId','employee_id','employee.id','userId'));
        $recordId = (string) $this->value($item, array('id','recordId','punchId'));
        $time = (string) $this->value($item, array('dateTime','datetime','punchTime','date','time'));
        if (!$employeeId || !$recordId || !$time) { return false; }
        try { $punchTime = (new \DateTime($time))->format('Y-m-d H:i:s'); } catch (\Throwable $e) { return false; }
        $map = $this->db->table($this->db->prefixTable('pontorh_solides_employees'))->where('solides_employee_id', $employeeId)->get()->getRow();
        $data = array('solides_record_id'=>$recordId,'solides_employee_id'=>$employeeId,'team_member_id'=>$map->team_member_id ?? null,'punch_time'=>$punchTime,'raw_payload'=>json_encode($item, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'synced_at'=>get_current_utc_time());
        $table = $this->db->table($this->db->prefixTable('pontorh_solides_punches'));
        $exists = $table->where('solides_record_id', $recordId)->get()->getRow();
        return $exists ? (bool) $table->where('id', $exists->id)->update($data) : (bool) $table->insert($data);
    }

    public function rebuildAlerts(string $startDate, string $endDate): void
    {
        $expected = max(1, (int) $this->settings->get_setting('solides_expected_daily_punches', '4'));
        $sql = "SELECT solides_employee_id, team_member_id, DATE(punch_time) work_date, COUNT(*) punch_count FROM " . $this->db->prefixTable('pontorh_solides_punches') . " WHERE DATE(punch_time) BETWEEN ? AND ? GROUP BY solides_employee_id, team_member_id, DATE(punch_time)";
        foreach ($this->db->query($sql, array($startDate, $endDate))->getResult() as $row) {
            $status = ((int)$row->punch_count === $expected) ? 'resolved' : 'pending';
            $table = $this->db->table($this->db->prefixTable('pontorh_solides_alerts'));
            $existing = $table->where('solides_employee_id',$row->solides_employee_id)->where('work_date',$row->work_date)->get()->getRow();
            $data = array('team_member_id'=>$row->team_member_id,'punch_count'=>(int)$row->punch_count,'expected_count'=>$expected,'status'=>$status,'updated_at'=>get_current_utc_time());
            if ($status === 'resolved') { $data['resolved_at'] = get_current_utc_time(); }
            if ($existing) { $table->where('id',$existing->id)->update($data); }
            else { $data += array('solides_employee_id'=>$row->solides_employee_id,'work_date'=>$row->work_date,'created_at'=>get_current_utc_time()); $table->insert($data); }
        }
    }

    public function alerts(string $status = 'pending'): array
    {
        $table = $this->db->prefixTable('pontorh_solides_alerts');
        $users = $this->db->prefixTable('users');
        return $this->db->query("SELECT a.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) employee_name FROM {$table} a LEFT JOIN {$users} u ON u.id=a.team_member_id WHERE a.status=? ORDER BY a.work_date DESC, employee_name", array($status))->getResult();
    }

    public function employeeStatus(int $teamMemberId): array
    {
        $punches = $this->db->table($this->db->prefixTable('pontorh_solides_punches'))->where('team_member_id',$teamMemberId)->where('punch_time >=', date('Y-m-d 00:00:00', strtotime('-1 day')))->orderBy('punch_time','ASC')->get()->getResult();
        $days = array(); foreach ($punches as $p) { $d=substr($p->punch_time,0,10); $days[$d][] = substr($p->punch_time,11,5); }
        return array('today'=>$days[date('Y-m-d')] ?? array(),'yesterday'=>$days[date('Y-m-d',strtotime('-1 day'))] ?? array(),'expected'=>(int)$this->settings->get_setting('solides_expected_daily_punches','4'));
    }
}
