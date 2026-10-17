<?php

namespace App\Support;

class SchoolSubdomain
{
    public static function fromHost(string $host): ?string
    {
        $host = strtolower(trim($host, '.'));
        $rootDomain = strtolower(trim((string) config('edlink.root_domain'), '.'));

        if ($rootDomain === '' || $host === $rootDomain) {
            return null;
        }

        $suffix = '.'.$rootDomain;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        return $subdomain !== '' && ! str_contains($subdomain, '.') ? $subdomain : null;
    }

    public static function url(?string $subdomain): ?string
    {
        if (! $subdomain) {
            return null;
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme.'://'.$subdomain.'.'.config('edlink.root_domain');
    }
}
