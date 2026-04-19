<?php

class OutboundHttpClient {
    /**
     * @param array{
     * headers?:string[],
     * body?:string|null,
     * timeout?:int,
     * connect_timeout?:int,
     * max_redirects?:int,
     * user_agent?:string
     * } $options
     * @return array{
     * success:bool,
     * status:int,
     * body:?string,
     * headers:array<string,string[]>,
     * error:?string,
     * final_url:string,
     * redirect_count:int,
     * primary_ip?:?string,
     * validated?:array<string,mixed>
     * }
     */
    public static function request(string $method, string $url, array $options, OutboundUrlValidator $validator): array {
        $method = strtoupper(trim($method));
        $headers = array_values(array_filter((array) ($options['headers'] ?? []), 'is_string'));
        $body = array_key_exists('body', $options) ? (string) ($options['body'] ?? '') : null;
        $timeout = max(1, (int) ($options['timeout'] ?? 5));
        $connectTimeout = max(1, (int) ($options['connect_timeout'] ?? 3));
        $maxRedirects = max(0, (int) ($options['max_redirects'] ?? 0));
        $userAgent = (string) ($options['user_agent'] ?? 'Fulgurite/1.0');

        $currentUrl = $url;
        $redirectCount = 0;
        $lastValidation = [];

        while (true) {
            $lastValidation = $validator->validate($currentUrl);
            $response = self::performSingleRequest(
                $method,
                $currentUrl,
                $headers,
                $body,
                $timeout,
                $connectTimeout,
                $userAgent,
                $lastValidation
            );
            $response['validated'] = $lastValidation;

            if ($response['error'] !== null) {
                return $response;
            }

            if ($response['status'] >= 300 && $response['status'] < 400) {
                $locations = $response['headers']['location'] ?? [];
                $location = $locations[0] ?? '';
                if ($location === '') {
                    $response['success'] = false;
                    $response['error'] = 'Redirection HTTP sans en-tete Location.';
                    return $response;
                }
                if ($redirectCount >= $maxRedirects) {
                    $response['success'] = false;
                    $response['error'] = 'Trop de redirections HTTP.';
                    return $response;
                }

                $nextUrl = OutboundUrlTools::resolveRedirectUrl($currentUrl, trim($location));
                if ($nextUrl === null) {
                    $response['success'] = false;
                    $response['error'] = 'URL de redirection invalide.';
                    return $response;
                }

                $currentUrl = $nextUrl;
                $redirectCount++;
                continue;
            }

            $response['success'] = $response['status'] >= 200 && $response['status'] < 300;
            $response['redirect_count'] = $redirectCount;
            return $response;
        }
    }

    /**
     * @return array{
     * success:bool,
     * status:int,
     * body:?string,
     * headers:array<string,string[]>,
     * error:?string,
     * final_url:string,
     * redirect_count:int,
     * primary_ip:?string
     * }
     */
    private static function performSingleRequest(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout,
        int $connectTimeout,
        string $userAgent,
        array $validated
    ): array {
        $connectionIps = self::connectionIpsForRequest($validated);
        if ($connectionIps === []) {
            return [
                'success' => false,
                'status' => 0,
                'body' => null,
                'headers' => [],
                'error' => 'No validated outbound IP available for connection.',
                'final_url' => $url,
                'redirect_count' => 0,
                'primary_ip' => null,
            ];
        }

        $lastFailure = null;
        foreach ($connectionIps as $connectionIp) {
            $response = self::executeCurlRequest(
                $method,
                $url,
                $headers,
                $body,
                $timeout,
                $connectTimeout,
                $userAgent,
                $validated,
                $connectionIp
            );

            if ($response['error'] === null) {
                return $response;
            }

            $lastFailure = $response;
        }

        return $lastFailure ?? [
            'success' => false,
            'status' => 0,
            'body' => null,
            'headers' => [],
            'error' => 'HTTP request failed',
            'final_url' => $url,
            'redirect_count' => 0,
            'primary_ip' => null,
        ];
    }

    /**
     * @return string[]
     */
    private static function connectionIpsForRequest(array $validated): array {
        $host = OutboundUrlTools::normalizeHost((string) ($validated['host'] ?? ''));
        if ($host === '') {
            return [];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $resolvedIps = array_map(
            static fn(string $ip): string => OutboundUrlTools::normalizeIp($ip),
            array_values(array_filter((array) ($validated['resolved_ips'] ?? []), 'is_string'))
        );

        return array_values(array_unique(array_filter(
            $resolvedIps,
            static fn(string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false
        )));
    }

    /**
     * @return array{
     * success:bool,
     * status:int,
     * body:?string,
     * headers:array<string,string[]>,
     * error:?string,
     * final_url:string,
     * redirect_count:int,
     * primary_ip:?string
     * }
     */
    private static function executeCurlRequest(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout,
        int $connectTimeout,
        string $userAgent,
        array $validated,
        string $connectionIp
    ): array {
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'success' => false,
                'status' => 0,
                'body' => null,
                'headers' => [],
                'error' => 'curl_init failed',
                'final_url' => $url,
                'redirect_count' => 0,
                'primary_ip' => null,
            ];
        }

        $protocolMask = str_starts_with(strtolower($url), 'https://')
            ? (defined('CURLPROTO_HTTPS') ? CURLPROTO_HTTPS : 2)
            : ((defined('CURLPROTO_HTTP') ? CURLPROTO_HTTP : 1) | (defined('CURLPROTO_HTTPS') ? CURLPROTO_HTTPS : 2));

        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_NOSIGNAL => true,
        ];

        if ($body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $body;
        }
        if (defined('CURLOPT_PROTOCOLS')) {
            $curlOptions[CURLOPT_PROTOCOLS] = $protocolMask;
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $curlOptions[CURLOPT_REDIR_PROTOCOLS] = $protocolMask;
        }
        $resolveEntry = self::buildResolveEntry($validated, $connectionIp);
        if ($resolveEntry !== null && !defined('CURLOPT_RESOLVE')) {
            curl_close($ch);
            return [
                'success' => false,
                'status' => 0,
                'body' => null,
                'headers' => [],
                'error' => 'Pinned outbound DNS resolution is unavailable on this PHP cURL build.',
                'final_url' => $url,
                'redirect_count' => 0,
                'primary_ip' => null,
            ];
        }
        if ($resolveEntry !== null) {
            $curlOptions[CURLOPT_RESOLVE] = [$resolveEntry];
        }

        curl_setopt_array($ch, $curlOptions);
        $rawResponse = curl_exec($ch);
        $info = curl_getinfo($ch);
        $status = (int) ($info['http_code'] ?? 0);
        $headerSize = (int) ($info['header_size'] ?? 0);
        $finalUrl = (string) ($info['url'] ?? $url);
        $primaryIp = isset($info['primary_ip']) && is_string($info['primary_ip']) && $info['primary_ip'] !== ''
            ? $info['primary_ip']
            : null;
        $error = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return [
                'success' => false,
                'status' => $status,
                'body' => null,
                'headers' => [],
                'error' => $error !== '' ? $error : 'HTTP request failed',
                'final_url' => $finalUrl !== '' ? $finalUrl : $url,
                'redirect_count' => 0,
                'primary_ip' => $primaryIp,
            ];
        }

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $bodyContent = substr($rawResponse, $headerSize);

        return [
            'success' => false,
            'status' => $status,
            'body' => $bodyContent === false ? null : $bodyContent,
            'headers' => self::parseHeaders((string) $rawHeaders),
            'error' => $error !== '' ? $error : null,
            'final_url' => $finalUrl !== '' ? $finalUrl : $url,
            'redirect_count' => 0,
            'primary_ip' => $primaryIp,
        ];
    }

    private static function buildResolveEntry(array $validated, string $connectionIp): ?string {
        $host = OutboundUrlTools::normalizeHost((string) ($validated['host'] ?? ''));
        $port = (int) ($validated['port'] ?? 0);
        $connectionIp = OutboundUrlTools::normalizeIp($connectionIp);

        if ($host === '' || $port <= 0 || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }
        if (!filter_var($connectionIp, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (filter_var($connectionIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $connectionIp = '[' . $connectionIp . ']';
        }

        return $host . ':' . $port . ':' . $connectionIp;
    }

    /**
     * @return array<string,string[]>
     */
    private static function parseHeaders(string $rawHeaders): array {
        $blocks = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($rawHeaders)) ?: [];
        $lastBlock = trim((string) end($blocks));
        $lines = preg_split("/\r\n|\n|\r/", $lastBlock) ?: [];
        $headers = [];

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $name = strtolower(trim($name));
            if ($name === '') {
                continue;
            }
            $headers[$name] ??= [];
            $headers[$name][] = trim($value);
        }

        return $headers;
    }
}
