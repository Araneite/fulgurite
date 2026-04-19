<?php

class TrustedServiceEndpointValidator implements OutboundUrlValidator {
    /** @var string[] */
    private array $allowedHosts;
    /** @var string[] */
    private array $allowedPatterns;
    /** @var string[] */
    private array $allowedCidrs;
    /** @var string[] */
    private array $allowedSchemes;
    private int $expectedPort;

    /**
     * @param array{
     * allowed_hosts?:string[],
     * allowed_patterns?:string[],
     * allowed_cidrs?:string[],
     * allow_http?:bool,
     * expected_port?:int
     * } $policy
     */
    public function __construct(array $policy) {
        $this->allowedHosts = array_values(array_unique(array_filter(array_map(
            static fn(string $host): string => OutboundUrlTools::normalizeHost($host),
            (array) ($policy['allowed_hosts'] ?? [])
        ))));
        $this->allowedPatterns = array_values(array_unique(array_filter(array_map(
            static fn(string $pattern): string => strtolower(trim($pattern)),
            (array) ($policy['allowed_patterns'] ?? [])
        ))));
        $this->allowedCidrs = array_values(array_unique(array_filter(array_map(
            static fn(string $cidr): string => trim($cidr),
            (array) ($policy['allowed_cidrs'] ?? [])
        ))));
        $this->allowedSchemes = !empty($policy['allow_http']) ? ['https', 'http'] : ['https'];
        $this->expectedPort = max(1, (int) ($policy['expected_port'] ?? 443));

        if ($this->allowedHosts === [] && $this->allowedPatterns === []) {
            throw new InvalidArgumentException('Aucune cible de confiance Infisical n est definie.');
        }
        if ($this->allowedCidrs === []) {
            throw new InvalidArgumentException('Aucun perimetre IP de confiance Infisical n est defini.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function validate(string $url): array {
        $parts = OutboundUrlTools::parseUrl($url);
        if (!in_array($parts['scheme'], $this->allowedSchemes, true)) {
            throw new InvalidArgumentException('Schema Infisical interdit par la politique de confiance.');
        }

        if ($parts['port'] !== $this->expectedPort) {
            throw new InvalidArgumentException('Port Infisical interdit par la politique de confiance.');
        }

        $host = $parts['host'];
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new InvalidArgumentException('La cible localhost est interdite pour Infisical.');
        }

        $hostAllowed = in_array($host, $this->allowedHosts, true);
        if (!$hostAllowed) {
            foreach ($this->allowedPatterns as $pattern) {
                if (OutboundUrlTools::hostMatchesPattern($host, $pattern)) {
                    $hostAllowed = true;
                    break;
                }
            }
        }
        if (!$hostAllowed) {
            throw new InvalidArgumentException('Hote Infisical hors de la liste de confiance.');
        }

        $resolvedIps = OutboundUrlTools::resolveHostIps($host);
        if ($resolvedIps === []) {
            throw new InvalidArgumentException('Impossible de resoudre le DNS de la cible Infisical.');
        }

        foreach ($resolvedIps as $ip) {
            if (OutboundUrlTools::isLocalOnlyIp($ip)) {
                throw new InvalidArgumentException('Adresse Infisical interdite par la politique reseau.');
            }

            $matched = false;
            foreach ($this->allowedCidrs as $cidr) {
                if (OutboundUrlTools::cidrContainsIp($cidr, $ip)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw new InvalidArgumentException('Adresse Infisical hors du perimetre IP de confiance.');
            }

            if (!OutboundUrlTools::isPrivateOrUlaIp($ip)
                && !filter_var(OutboundUrlTools::normalizeIp($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Adresse Infisical reservee ou non routable.');
            }
        }

        $parts['resolved_ips'] = $resolvedIps;
        return $parts;
    }
}
