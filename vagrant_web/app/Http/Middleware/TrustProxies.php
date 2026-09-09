<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use LogicException;

class TrustProxies extends Middleware
{
    /**
     * Only headers that the documented reverse proxy is required to set.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Return the explicitly configured reverse proxies.
     *
     * Production must never trust forwarded headers from arbitrary clients.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies(): array|string|null
    {
        $proxies = config('trustedproxy.proxies');

        if (is_string($proxies)) {
            $configuredProxies = array_map('trim', explode(',', $proxies));

            if (in_array('*', $configuredProxies, true) || in_array('**', $configuredProxies, true)) {
                throw new LogicException('TRUSTED_PROXIES must contain explicit IP addresses or CIDR ranges.');
            }
        }

        return $proxies;
    }
}
