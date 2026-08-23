<?php

namespace OrdemServico\Libraries;

use App\Models\Settings_model;

/**
 * Settings protected from the browser. Secrets are encrypted with the
 * RISE encrypter and are never returned to views or logs.
 */
class EuGestorSettings
{
    private const PREFIX = 'ordemservico.eugestor.';
    private Settings_model $settings;

    public function __construct(?Settings_model $settings = null)
    {
        $this->settings = $settings ?: new Settings_model();
    }

    public function isEnabled(): bool
    {
        return get_setting(self::PREFIX . 'enabled') === '1';
    }

    public function getUsername(): string
    {
        return trim((string)get_setting(self::PREFIX . 'username'));
    }

    public function getDomain(): string
    {
        return trim((string)get_setting(self::PREFIX . 'domain')) ?: 'portal';
    }

    public function getPassword(): string
    {
        return $this->decrypt((string)get_setting(self::PREFIX . 'password_enc'));
    }

    public function getAccessToken(): string
    {
        return $this->decrypt((string)get_setting(self::PREFIX . 'access_token_enc'));
    }

    public function getRefreshToken(): string
    {
        return $this->decrypt((string)get_setting(self::PREFIX . 'refresh_token_enc'));
    }

    public function getTokenExpiresAt(): int
    {
        return (int)get_setting(self::PREFIX . 'token_expires_at');
    }

    public function getLastSyncAt(): string
    {
        return (string)get_setting(self::PREFIX . 'last_sync_at');
    }

    public function getLastSyncResult(): string
    {
        return (string)get_setting(self::PREFIX . 'last_sync_result');
    }

    public function hasPassword(): bool
    {
        return $this->getPassword() !== '';
    }

    public function saveCredentials(string $username, ?string $password, bool $enabled, string $domain = ''): void
    {
        $this->save('enabled', $enabled ? '1' : '0');
        $this->save('username', trim($username));
        $this->save('domain', trim($domain) !== '' ? trim($domain) : $this->getDomain());
        if ($password !== null && $password !== '') {
            $this->save('password_enc', $this->encrypt($password));
        }
    }

    public function saveSession(string $accessToken, int $expiresAt, string $refreshToken = ''): void
    {
        $this->save('access_token_enc', $this->encrypt($accessToken));
        $this->save('token_expires_at', (string)$expiresAt);
        if ($refreshToken !== '') {
            $this->save('refresh_token_enc', $this->encrypt($refreshToken));
        }
    }

    public function clearSession(): void
    {
        $this->save('access_token_enc', '');
        $this->save('refresh_token_enc', '');
        $this->save('token_expires_at', '0');
    }

    public function saveSyncResult(string $at, array $result): void
    {
        $this->save('last_sync_at', $at);
        $this->save('last_sync_result', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function save(string $key, string $value): void
    {
        $this->settings->save_setting(self::PREFIX . $key, $value);
    }

    private function encrypt(string $value): string
    {
        if ($value === '') { return ''; }
        $encrypter = get_encrypter();
        return 'enc:' . bin2hex($encrypter->encrypt($value));
    }

    private function decrypt(string $value): string
    {
        if ($value === '') { return ''; }
        if (strpos($value, 'enc:') !== 0) { return ''; }
        try {
            return (string)get_encrypter()->decrypt(hex2bin(substr($value, 4)));
        } catch (\Throwable $e) {
            return '';
        }
    }
}
