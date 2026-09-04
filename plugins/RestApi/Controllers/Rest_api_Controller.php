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
	protected $token_error_message = 'Token not found';

	public function __construct() {
		$this->api_settings_model = model('RestApi\Models\Api_settings_model');
		helper('jwt');
		$request = service('request');
		$request_path = strtolower(trim((string) $request->getUri()->getPath(), '/'));

		// The original RestApi controllers read request data through getPost().
		// Mobile clients and external integrations commonly send application/json,
		// which makes getPost() empty in CodeIgniter. Normalize JSON objects into
		// the POST global so existing controllers keep working without changing
		// every endpoint individually.
		$method = strtolower((string) $request->getMethod());
		if (in_array($method, ['post', 'put', 'patch'], true) && empty($request->getPost())) {
			try {
				$json_data = $request->getJSON(true);
				if (is_array($json_data) && !empty($json_data)) {
					$request->setGlobal('post', $json_data);
					$_POST = array_merge($_POST ?? [], $json_data);
				}
			} catch (\Throwable $e) {
				// Keep the legacy form-data/x-www-form-urlencoded behavior unchanged
				// when the body isn't valid JSON.
			}
		}

		// Some legacy create handlers access optional fields directly instead of
		// using null-coalescing. Supply safe defaults only for the affected routes.
		if ($method === 'post') {
			$post_data = $request->getPost();
			if ($request_path === 'api/clients' && !array_key_exists('group_ids', $post_data)) {
				$post_data['group_ids'] = '';
				$request->setGlobal('post', $post_data);
				$_POST['group_ids'] = '';
			}

			if ($request_path === 'api/projects') {
				$changed = false;
				foreach (['labels' => '', 'price' => 0] as $key => $default) {
					if (!array_key_exists($key, $post_data)) {
						$post_data[$key] = $default;
						$_POST[$key] = $default;
						$changed = true;
					}
				}
				if ($changed) {
					$request->setGlobal('post', $post_data);
				}
			}
		}

		$token          = get_token();
		$check_token    = $this->api_settings_model->check_token($token);
		if ($check_token === false) {
			$check_token = $this->resolveTokenFromJwt($token);
		}

		if ($check_token === false) {
			if ($this->isPontoRhRequest($request_path)) {
				$this->logPontoRhAuthFailure($this->token_error_message, array(
					'path' => $request_path,
					'token' => $token !== 'Token is not defined.' ? $token : null,
				));
			}

			$message = [
				'status'  => false,
				'message' => $this->token_error_message
			];
			$this->response = service('response');
			$this->response->setStatusCode(401);
			$this->response->setJSON($message);
			echo $this->response->getBody();
			die;
		}

		$this->api_user = $check_token;
	}

	protected function resolveTokenFromJwt(string $token) {
		$validated = validateToken();
		if (($validated['status'] ?? false) !== true) {
			$this->token_error_message = (string) ($validated['message'] ?? 'Token not found');
			return false;
		}

		$decoded = $validated['data'] ?? null;
		if (!$decoded || !is_object($decoded)) {
			return false;
		}

		$email = strtolower(trim((string) ($decoded->email ?? '')));
		if ($email === '') {
			$this->token_error_message = 'Token not found';
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
			$this->token_error_message = 'Token not found';
			return false;
		}

		$api_user = $this->api_settings_model->get_data_by_user($email);
		$normalized_token = trim((string) $token);

        if (!empty($api_user->id)) {
            $current_token = trim((string) $api_user->token);
            if ($current_token !== '' && hash_equals($current_token, $normalized_token)) {
                return $api_user;
            }

            // O JWT já foi validado e pertence a um usuário ativo. Se houver
            // um token antigo salvo para esse usuário, sincronize-o em vez de
            // rejeitar o login feito na instalação local.
            $expires_at = date('Y-m-d H:i:s', time() + (new \RestApi\Config\JWT())->token_expire_time);
            $stored = $this->api_settings_model->store_login_token([
                'user' => $email,
                'name' => trim((string) ($decoded->name ?? ($staff_user->first_name . ' ' . $staff_user->last_name))),
                'token' => $normalized_token,
                'expiration_date' => $expires_at,
            ]);

            if (!$stored) {
                $this->token_error_message = 'Token not found';
                return false;
            }

            return $this->api_settings_model->get_data_by_token($normalized_token);
        }

		$expires_at = date('Y-m-d H:i:s', time() + (new \RestApi\Config\JWT())->token_expire_time);
		$stored = $this->api_settings_model->store_login_token([
			'user' => $email,
			'name' => trim((string) ($decoded->name ?? ($staff_user->first_name . ' ' . $staff_user->last_name))),
			'token' => $normalized_token,
			'expiration_date' => $expires_at,
		]);

		if (!$stored) {
			$this->token_error_message = 'Token not found';
			return false;
		}

		return $this->api_settings_model->get_data_by_token($normalized_token);
	}

	protected function isPontoRhRequest(string $request_path): bool {
		return str_starts_with($request_path, 'api/pontorh');
	}

	protected function logPontoRhAuthFailure(string $message, array $payload = array()): void {
		try {
			$service = new \PontoRH\Libraries\PontoRh_api_service();
			$service->logAuthAttempt('auth_failure', $message, $payload, 'invalid');
		} catch (\Throwable $e) {
			log_message('error', '[PontoRH] Unable to log auth failure: {message}', ['message' => $e->getMessage()]);
		}
	}
}

/* End of file Rest_api_Controller.php */
/* Location: ./plugins/RestAPI/controllers/Rest_api_Controller.php */
