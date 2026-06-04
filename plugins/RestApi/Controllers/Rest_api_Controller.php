<?php

namespace RestApi\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;

#[\AllowDynamicProperties]
class Rest_api_Controller extends ResourceController {
	use ResponseTrait;
	protected $format = 'json';
	protected $api_settings_model;
	protected $api_user;

	public function __construct() {
		$this->api_settings_model = model('RestApi\Models\Api_settings_model');
		helper('jwt');

		$token          = get_token();
		$check_token    = $this->api_settings_model->check_token($token);
		if ($check_token === false) {
			$check_token = $this->resolveTokenFromJwt($token);
		}

		if ($check_token === false) {
			$message = [
				'status'  => false,
				'message' => "Token not found"
			];
			$this->response = service('response');
			echo $this->format($message);
			die;
		}

		$this->api_user = $check_token;
	}

	protected function resolveTokenFromJwt(string $token) {
		$validated = validateToken();
		if (($validated['status'] ?? false) !== true) {
			return false;
		}

		$decoded = $validated['data'] ?? null;
		if (!$decoded || !is_object($decoded)) {
			return false;
		}

		$email = strtolower(trim((string) ($decoded->email ?? '')));
		if ($email === '') {
			return false;
		}

		$users_model = model('App\Models\Users_model');
		$staff_user = $users_model->get_one_where([
			'email' => $email,
			'deleted' => 0,
			'status' => 'active',
			'disable_login' => 0,
			'user_type' => 'staff',
		]);

		if (empty($staff_user->id)) {
			return false;
		}

		$api_user = $this->api_settings_model->get_data_by_user($email);
		$normalized_token = trim((string) $token);

		if (!empty($api_user->id)) {
			$current_token = trim((string) $api_user->token);
			if ($current_token !== '' && hash_equals($current_token, $normalized_token)) {
				return $api_user;
			}

			return false;
		}

		$expires_at = date('Y-m-d H:i:s', time() + (new \RestApi\Config\JWT())->token_expire_time);
		$stored = $this->api_settings_model->store_login_token([
			'user' => $email,
			'name' => trim((string) ($decoded->name ?? ($staff_user->first_name . ' ' . $staff_user->last_name))),
			'token' => $normalized_token,
			'expiration_date' => $expires_at,
		]);

		if (!$stored) {
			return false;
		}

		return $this->api_settings_model->get_data_by_token($normalized_token);
	}
}

/* End of file Rest_api_Controller.php */
/* Location: ./plugins/RestAPI/controllers/Rest_api_Controller.php */
