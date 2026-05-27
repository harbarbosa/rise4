<?php

use Firebase\JWT\JWT;
use \Firebase\JWT\Key;

/**
 * [Decode JWT token and get original data]
 * @param string $encodedToken   [token]
 * @param string $jwt_secret_key [purchase_code]
 * @return  array [decoded information]
 */
function DecodeJWTtoken(string $encodedToken, string $jwt_secret_key) {
	$decodedToken = JWT::decode($encodedToken, new Key($jwt_secret_key, 'HS512'));
	return $decodedToken;
}

/**
 * [generate JWT token]
 * @param array  $data           [payload data]
 */
function EncodeJWTtoken($data = null) {
	$jwt_config = new \RestApi\Config\JWT();

	if ($data and is_array($data)) {
		// add api time key in user array()
		$data['API_TIME'] = time();

		try {
			return JWT::encode($data, $jwt_config->jwt_key, $jwt_config->jwt_algorithm);
		} catch (Exception $e) {
			return 'Message: ' .$e->getMessage();
		}
	} else {
		return "Token Data Undefined!";
	}
}

function get_token() {
	$request = \Config\Services::request();
	$jwt_config = new \RestApi\Config\JWT();
	$token = trim((string) $request->getHeaderLine($jwt_config->token_header));
	if ($token !== '') {
		return $token;
	}

	$authorization = trim((string) $request->getHeaderLine('Authorization'));
	if ($authorization !== '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
		return trim($matches[1]);
	}

	return 'Token is not defined.';
}

function validateToken() {
	$jwt_config = new \RestApi\Config\JWT();
	/**
	 * Request All Headers
	 */
	$request = \Config\Services::request();
	$headers = $request->headers();

	/**
	 * Authorization Header Exists
	 */
	$token_data = tokenIsExist($headers);
	if ($token_data['status'] === true) {
		try {
			/**
			 * Token Decode
			 */
			try {
				$token_decode = JWT::decode($token_data['token'], new Key($jwt_config->jwt_key, $jwt_config->jwt_algorithm));
			} catch (Exception $e) {
				return ['status' => false, 'message' => $e->getMessage()];
			}

			if (!empty($token_decode) and is_object($token_decode)) {
				// Check Token API Time [API_TIME]
				if (empty($token_decode->API_TIME or !is_numeric($token_decode->API_TIME))) {
					return ['status' => false, 'message' => 'Token Time Not Define!'];
				}


				/**
				 * Check Token Time Valid
				 */
				$time_difference = strtotime('now') - $token_decode->API_TIME;
				if ($time_difference >= $jwt_config->token_expire_time) {
					return ['status' => false, 'message' => 'Token Time Expire.'];
				}

				/**
				 * All Validation False Return Data
				 */
				return ['status' => true, 'data' => $token_decode];
			}
			return ['status' => false, 'message' => 'Forbidden'];
		} catch (Exception $e) {
			return ['status' => false, 'message' => $e->getMessage()];
		}
	} else {
		// Authorization Header Not Found!
		return ['status' => false, 'message' => $token_data['message'] ];
	}
}

/**
 * Token Header Check
 * @param: request headers
 */
function tokenIsExist($headers) {
	$jwt_config = new \RestApi\Config\JWT();
	$request = \Config\Services::request();
	$token = trim((string) $request->getHeaderLine($jwt_config->token_header));
	if ($token !== '') {
		return ['status' => true, 'token' => $token];
	}

	$authorization = trim((string) $request->getHeaderLine('Authorization'));
	if ($authorization !== '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
		return ['status' => true, 'token' => trim($matches[1])];
	}

	return ['status' => false, 'message' => 'Token is not defined.'];
}


function token($headers) {
	$jwt_config = new \RestApi\Config\JWT();
	$request = \Config\Services::request();
	$token = trim((string) $request->getHeaderLine($jwt_config->token_header));
	if ($token !== '') {
		return $token;
	}

	$authorization = trim((string) $request->getHeaderLine('Authorization'));
	if ($authorization !== '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
		return trim($matches[1]);
	}

	return 'Token is not defined.';
}
