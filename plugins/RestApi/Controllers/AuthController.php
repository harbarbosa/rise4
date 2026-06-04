<?php

namespace RestApi\Controllers;

use App\Models\Users_model;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use RestApi\Models\Api_settings_model;

#[\AllowDynamicProperties]
class AuthController extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';
    protected Users_model $usersModel;
    protected Api_settings_model $apiSettingsModel;

    public function __construct()
    {
        $this->usersModel = model(Users_model::class);
        $this->apiSettingsModel = model(Api_settings_model::class);
        helper(['jwt', 'date_time']);
    }

    public function login()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return $this->failValidationErrors('Email and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors('A valid email is required.');
        }

        $user = $this->usersModel->get_one_where([
            'email' => $email,
            'deleted' => 0,
            'status' => 'active',
            'disable_login' => 0,
            'user_type' => 'staff',
        ]);

        if (!$this->isValidPassword($user, $password)) {
            return $this->failUnauthorized('Authentication failed.');
        }

        $fullName = trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? ''));
        $token = $this->issueToken($user);
        $jwtConfig = new \RestApi\Config\JWT();
        $expiresAt = date('Y-m-d H:i:s', time() + (int) $jwtConfig->token_expire_time);

        $apiUser = $this->apiSettingsModel->get_one_where(['user' => $email]);
        $payload = [
            'user' => $email,
            'name' => $fullName !== '' ? $fullName : $email,
            'token' => $token,
            'expiration_date' => $expiresAt,
            'deleted' => 0,
        ];

        if (!empty($apiUser->id)) {
            $this->apiSettingsModel->ci_save($payload, (int) $apiUser->id);
        } else {
            $this->apiSettingsModel->ci_save($payload);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => $this->sanitizeUser($user),
        ]);
    }

    public function logout()
    {
        $token = get_token();
        if ($token === '' || $token === 'Token is not defined.') {
            return $this->respond([
                'status' => true,
                'message' => 'Logged out successfully.',
            ]);
        }

        $apiUser = $this->apiSettingsModel->get_one_where(['token' => $token]);
        if (!empty($apiUser->id)) {
            $this->apiSettingsModel->ci_save([
                'token' => '',
                'expiration_date' => date('Y-m-d H:i:s', time() - 60),
            ], (int) $apiUser->id);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    protected function isValidPassword(object $user, string $password): bool
    {
        if (empty($user->id) || empty($user->password)) {
            return false;
        }

        if (strlen((string) $user->password) === 60 && password_verify($password, (string) $user->password)) {
            return true;
        }

        return hash_equals((string) $user->password, md5($password));
    }

    protected function issueToken(object $user): string
    {
        $payload = [
            'id' => (int) ($user->id ?? 0),
            'email' => (string) ($user->email ?? ''),
            'user_type' => (string) ($user->user_type ?? ''),
            'name' => trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? '')),
        ];

        return EncodeJWTtoken($payload);
    }

    protected function sanitizeUser(object $user): array
    {
        return [
            'id' => (int) ($user->id ?? 0),
            'first_name' => (string) ($user->first_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'image' => $user->image ?? null,
            'user_type' => (string) ($user->user_type ?? ''),
            'client_id' => (int) ($user->client_id ?? 0),
            'job_title' => (string) ($user->job_title ?? ''),
            'role_id' => (int) ($user->role_id ?? 0),
            'status' => (string) ($user->status ?? ''),
            'is_admin' => (int) ($user->is_admin ?? 0),
        ];
    }
}
