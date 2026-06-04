<?php
namespace RestApi\Models;

use App\Models\Crud_model; //access main app's models

class Api_settings_model extends Crud_model {
	protected $table = null;

	public function __construct() {
		$this->table = 'rise_api_users';
		parent::__construct($this->table);
	}

	public function get_api_users() {
		return $this->get_all('deleted')->getResult();
	}

	public function add($data) {
		$data['expiration_date'] = date("Y-m-d", strtotime($data['expiration_date']));
		$payload = [
			'user' => $data['user'],
			'name' => $data['name'],
		];
		// generate a token
		helper('jwt');
		$data['token'] = EncodeJWTtoken($payload);

		if ($this->ci_save($data)) {
			return true;
		}
		return false;
	}

	public function update_data($data, $where) {
		if ($this->update_where($data, $where)) {
			return true;
		}
		return false;
	}

	public function get_data_by_id($id) {
		return $this->get_one($id);
	}

	public function check_token($token) {
		$token = $this->normalize_token($token);
		$user = $this->get_data_by_token($token);
		if (!empty($user->id)) {
			$expiration_date = trim((string) $user->expiration_date);
			if ($expiration_date !== '' && !preg_match('/^1970-01-01(?:\s+00:00:00)?$/', $expiration_date)) {
				$expires_at = strtotime($expiration_date);
				if ($expires_at !== false && $expires_at > 0 && $expires_at < time()) {
					return false;
				}
			}

			return $user;
		}

		return false;
	}

	public function get_data_by_token($token) {
		$token = $this->normalize_token($token);
		return $this->get_one_where(['token' => $token]);
	}

	public function get_data_by_user($user) {
		$user = $this->normalize_user($user);
		return $this->get_one_where(['user' => $user]);
	}

	public function store_login_token(array $data) {
		$user = $this->normalize_user(get_array_value($data, 'user'));
		$name = trim((string) get_array_value($data, 'name'));
		$token = $this->normalize_token(get_array_value($data, 'token'));
		$expiration_date = trim((string) get_array_value($data, 'expiration_date'));

		if ($user === '' || $name === '' || $token === '' || $expiration_date === '') {
			return false;
		}

		$payload = [
			'user' => $user,
			'name' => $name,
			'token' => $token,
			'expiration_date' => $expiration_date,
		];

		$existing_user = $this->get_data_by_user($user);
		$builder = $this->db->table($this->table);

		if (!empty($existing_user->id)) {
			return (bool) $builder->where('id', (int) $existing_user->id)->update($payload);
		}

		return (bool) $builder->insert($payload);
	}

	private function normalize_token($token) {
		$token = (string) $token;
		$token = trim($token);
		$token = trim($token, "\"'");
		$token = preg_replace('/^\xEF\xBB\xBF/', '', $token);
		$token = preg_replace('/[\x00-\x1F\x7F]/u', '', $token);
		return $token;
	}

	private function normalize_user($user) {
		$user = strtolower(trim((string) $user));
		return $user;
	}

	public function delete_data($id) {
		$builder = $this->db->table($this->table);
		if ($builder->where(['id' => $id])->delete()) {
			return true;
		}
		return false;
	}
}
