<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : TrustProxies.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request;

final class TrustProxies extends Middleware
{
    protected $proxies;

    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_PREFIX;

    public function __construct()
    {
        $this->proxies = $this->resoudreProxies();
        $this->headers = $this->resoudreEnTetes();
    }

    /**
     * @return array<int, string>|string|null
     */
    private function resoudreProxies(): array|string|null
    {
        $configuration = trim((string) config('trustedproxy.proxies'));

        if ($configuration === '') {
            return null;
        }

        if ($configuration === '*') {
            return '*';
        }

        $proxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', $configuration)
        )));

        return $proxies === [] ? null : $proxies;
    }

    private function resoudreEnTetes(): int
    {
        return match (trim((string) config('trustedproxy.headers', 'forwarded'))) {
            'aws_elb' => Request::HEADER_X_FORWARDED_AWS_ELB,
            default => Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        };
    }
}
