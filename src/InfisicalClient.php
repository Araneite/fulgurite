<?php
// =============================================================================
// InfisicalClient.php — Infisical API client for retrieving secrets
// Supports service tokens (st.xxx) and Universal Auth access tokens
// =============================================================================

class InfisicalClient {

    /**
     * Checks that Infisical is configured (enabled + URL + token filled in).
     */
    public static function isConfigured(): bool {
        $config = InfisicalConfigManager::currentConfig();
        return $config['infisical_enabled'] === '1'
            && $config['infisical_url'] !== ''
            && $config['infisical_token_value'] !== '';
    }

    /**
     * Retrieves the value of a secret from Infisical.
     * Returns null if Infisical is not configured or the secret is not found.
     *
     * API: GET /api/v3/secrets/raw/{secretName}?workspaceId=...&environment=...&secretPath=...
     */
    public static function getSecret(string $secretName): ?string {
        if (!self::isConfigured()) return null;
        if (empty(trim($secretName))) return null;

        $config = InfisicalConfigManager::currentConfig();
        $baseUrl = rtrim($config['infisical_url'], '/');
        $token = $config['infisical_token_value'];
        $projectId = $config['infisical_project_id'];
        $env = $config['infisical_environment'];
        $path = $config['infisical_secret_path'] ?: '/';

        $params = [
            'environment' => $env,
            'secretPath'  => $path,
        ];
        if ($projectId !== '') {
            $params['workspaceId'] = $projectId;
        }

        $url = $baseUrl . '/api/v3/secrets/raw/' . urlencode($secretName)
             . '?' . http_build_query($params);

        try {
            $response = self::request('GET', $url, [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ], 2);
        } catch (Throwable $e) {
            return null;
        }

        if (($response['status'] ?? 0) !== 200 || !is_string($response['body'])) return null;

        $data = json_decode($response['body'], true);
        // Infisical v0.x API returns { secret: { secretValue: "..." } }
        return $data['secret']['secretValue'] ?? null;
    }

    /**
     * Tests the Infisical connection by calling GET /api/status.
     */
    public static function testConnection(): array {
        return InfisicalConfigManager::testConfiguration(InfisicalConfigManager::currentConfig());
    }

    /**
     * @param string[] $headers
     * @return array<string,mixed>
     */
    private static function request(string $method, string $url, array $headers, int $maxRedirects = 2): array {
        $config = InfisicalConfigManager::currentConfig();
        $policy = InfisicalConfigManager::buildTrustedPolicy($config);
        $validator = new TrustedServiceEndpointValidator($policy);

        return OutboundHttpClient::request($method, $url, [
            'headers' => $headers,
            'timeout' => 5,
            'connect_timeout' => 3,
            'max_redirects' => $maxRedirects,
            'user_agent' => 'Fulgurite-Infisical/1.0',
        ], $validator);
    }
}
