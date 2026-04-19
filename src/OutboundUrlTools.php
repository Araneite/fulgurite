<?php

class OutboundUrlTools {
    public static function normalizeHost(string $host): string {
        $host = strtolower(rtrim(trim($host), '.'));
        if ($host === '') {
            return '';
        }

        if ($host[0] === '[' && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if (!filter_var($host, FILTER_VALIDATE_IP) && function_exists('idn_to_ascii')) {
            $asciiHost = idn_to_ascii($host);
            if (is_string($asciiHost) && $asciiHost !== '') {
                $host = strtolower($asciiHost);
            }
        }

        return $host;
    }

    /**
     * @return array{scheme:string,host:string,port:int,path:string,query?:string,fragment?:string}
     */
    public static function parseUrl(string $url): array {
        $parts = @parse_url(trim($url));
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidArgumentException('URL mal formee.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Les URLs avec identifiants integres sont interdites.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = self::normalizeHost((string) $parts['host']);
        if ($host === '') {
            throw new InvalidArgumentException('URL mal formee.');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : self::defaultPortForScheme($scheme);
        if ($port <= 0 || $port > 65535) {
            throw new InvalidArgumentException('Port invalide.');
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'path' => (string) ($parts['path'] ?? '/'),
            'query' => isset($parts['query']) ? (string) $parts['query'] : null,
            'fragment' => isset($parts['fragment']) ? (string) $parts['fragment'] : null,
        ];
    }

    public static function defaultPortForScheme(string $scheme): int {
        return match (strtolower($scheme)) {
            'https' => 443,
            'http' => 80,
            default => 0,
        };
    }

    /**
     * @return string[]
     */
    public static function resolveHostIps(string $host): array {
        $host = self::normalizeHost($host);
        if ($host === '') {
            return [];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];
        $ipv4Records = @gethostbynamel($host);
        if (is_array($ipv4Records)) {
            $ips = array_merge($ips, $ipv4Records);
        }

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (!empty($record['ip'])) {
                        $ips[] = (string) $record['ip'];
                    }
                    if (!empty($record['ipv6'])) {
                        $ips[] = (string) $record['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique(array_filter(
            $ips,
            static fn(string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false
        )));
    }

    public static function normalizeIp(string $ip): string {
        $ip = strtolower(trim($ip));
        if (str_starts_with($ip, '::ffff:')) {
            $mappedIpv4 = substr($ip, 7);
            if (filter_var($mappedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $mappedIpv4;
            }
        }

        return $ip;
    }

    public static function assertPublicIp(string $ip): void {
        $ip = self::normalizeIp($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Adresse IP distante invalide.');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('Cible distante interdite par la politique reseau.');
        }
    }

    public static function isPrivateOrUlaIp(string $ip): bool {
        $ip = self::normalizeIp($ip);
        return self::cidrContainsIp('10.0.0.0/8', $ip)
            || self::cidrContainsIp('172.16.0.0/12', $ip)
            || self::cidrContainsIp('192.168.0.0/16', $ip)
            || self::cidrContainsIp('fc00::/7', $ip);
    }

    public static function isLocalOnlyIp(string $ip): bool {
        $ip = self::normalizeIp($ip);
        $blockedCidrs = [
            '127.0.0.0/8',
            '169.254.0.0/16',
            '0.0.0.0/8',
            '224.0.0.0/4',
            '240.0.0.0/4',
            '::1/128',
            'fe80::/10',
            '::/128',
            'ff00::/8',
        ];

        foreach ($blockedCidrs as $cidr) {
            if (self::cidrContainsIp($cidr, $ip)) {
                return true;
            }
        }

        return false;
    }

    public static function cidrContainsIp(string $cidr, string $ip): bool {
        $ip = self::normalizeIp($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!str_contains($cidr, '/')) {
            $cidr .= filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '/128' : '/32';
        }

        [$network, $prefixLength] = explode('/', $cidr, 2) + ['', ''];
        $network = self::normalizeIp($network);
        if (!filter_var($network, FILTER_VALIDATE_IP) || !ctype_digit((string) $prefixLength)) {
            return false;
        }

        $networkBinary = @inet_pton($network);
        $ipBinary = @inet_pton($ip);
        if ($networkBinary === false || $ipBinary === false || strlen($networkBinary) !== strlen($ipBinary)) {
            return false;
        }

        $maxBits = strlen($networkBinary) * 8;
        $prefixLength = (int) $prefixLength;
        if ($prefixLength < 0 || $prefixLength > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        if ($fullBytes > 0 && substr($networkBinary, 0, $fullBytes) !== substr($ipBinary, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $prefixLength % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($networkBinary[$fullBytes]) & $mask) === (ord($ipBinary[$fullBytes]) & $mask);
    }

    public static function hostMatchesPattern(string $host, string $pattern): bool {
        $host = self::normalizeHost($host);
        $pattern = strtolower(trim($pattern));
        if ($host === '' || $pattern === '') {
            return false;
        }

        if ($pattern[0] === '.' && strlen($pattern) > 1) {
            $pattern = '*' . $pattern;
        }

        if (!str_contains($pattern, '*')) {
            return $host === self::normalizeHost($pattern);
        }

        $quoted = preg_quote($pattern, '#');
        $quoted = str_replace('\*', '[^.]+', $quoted);
        return preg_match('#^' . $quoted . '$#i', $host) === 1;
    }

    public static function ipToHostCidr(string $ip): string {
        $ip = self::normalizeIp($ip);
        return $ip . (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '/128' : '/32');
    }

    public static function resolveRedirectUrl(string $baseUrl, string $location): ?string {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $location)) {
            return $location;
        }

        $base = @parse_url($baseUrl);
        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        if (str_starts_with($location, '//')) {
            return strtolower((string) $base['scheme']) . ':' . $location;
        }

        $scheme = strtolower((string) $base['scheme']);
        $authority = self::buildUrlAuthority($base);
        if ($authority === '') {
            return null;
        }

        $basePath = (string) ($base['path'] ?? '/');
        if (str_starts_with($location, '?')) {
            $path = $basePath . $location;
        } elseif (str_starts_with($location, '#')) {
            $query = isset($base['query']) ? '?' . (string) $base['query'] : '';
            $path = $basePath . $query . $location;
        } elseif (str_starts_with($location, '/')) {
            $path = $location;
        } else {
            $baseDir = preg_replace('#/[^/]*$#', '/', $basePath);
            $path = ($baseDir ?: '/') . $location;
        }

        return $scheme . '://'. $authority. self::removeDotSegments($path);
    }

    private static function buildUrlAuthority(array $parts): string {
        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            return '';
        }

        if (str_contains($host, ':') && $host[0] !== '[') {
            $host = '[' . $host . ']';
        }

        if (isset($parts['port'])) {
            $host .= ':' . (int) $parts['port'];
        }

        return $host;
    }

    private static function removeDotSegments(string $path): string {
        $query = '';
        $fragment = '';

        $fragmentPos = strpos($path, '#');
        if ($fragmentPos !== false) {
            $fragment = substr($path, $fragmentPos);
            $path = substr($path, 0, $fragmentPos);
        }

        $queryPos = strpos($path, '?');
        if ($queryPos !== false) {
            $query = substr($path, $queryPos);
            $path = substr($path, 0, $queryPos);
        }

        $absolute = str_starts_with($path, '/');
        $trailingSlash = str_ends_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        $normalized = ($absolute ? '/' : '') . implode('/', $segments);
        if ($trailingSlash && $normalized !== '/') {
            $normalized .= '/';
        }
        if ($normalized === '') {
            $normalized = $absolute ? '/' : '';
        }

        return $normalized . $query . $fragment;
    }
}
