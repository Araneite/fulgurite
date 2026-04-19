<?php

class PublicOutboundUrlValidator implements OutboundUrlValidator {
    /**
     * @return array<string,mixed>
     */
    public function validate(string $url): array {
        $parts = OutboundUrlTools::parseUrl($url);
        if ($parts['scheme'] !== 'https') {
            throw new InvalidArgumentException('Seules les URLs https:// are allowed.');
        }

        if ($parts['port'] !== 443) {
            throw new InvalidArgumentException('Seul le port HTTPS standard 443 est autorise.');
        }

        $host = $parts['host'];
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new InvalidArgumentException('La cible localhost est interdite.');
        }

        $resolvedIps = OutboundUrlTools::resolveHostIps($host);
        if ($resolvedIps === []) {
            throw new InvalidArgumentException('Impossible de resoudre le DNS de la cible.');
        }

        foreach ($resolvedIps as $ip) {
            OutboundUrlTools::assertPublicIp($ip);
        }

        $parts['resolved_ips'] = $resolvedIps;
        return $parts;
    }
}
