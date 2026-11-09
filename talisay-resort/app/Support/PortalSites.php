<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The guest website and the admin/staff website run on different local addresses.
 * Port 8000 is the client site. Port 8001 is the admin and staff site.
 * Automated tests use another port and keep the older portal field.
 */
class PortalSites
{
    public const CLIENT = 'http://127.0.0.1:8000';

    public const STAFF = 'http://127.0.0.1:8001';

    public static function current(?Request $request = null): ?string
    {
        $request ??= request();
        $host = strtolower($request->getHost());

        // A public tunnel arrives on port 443, so the address itself decides the site.
        if ($host !== '' && $host === self::configuredHost('STAFF_URL')) {
            return 'staff';
        }
        if ($host !== '' && $host === self::configuredHost('CLIENT_URL')) {
            return 'client';
        }

        return match ((int) $request->getPort()) {
            8001 => 'staff',
            8000, 443 => 'client',
            default => null,
        };
    }

    public static function clientBase(): string
    {
        return self::publicBase('CLIENT_URL', self::CLIENT);
    }

    public static function staffBase(): string
    {
        return self::publicBase('STAFF_URL', self::STAFF);
    }

    public static function clientUrl(string $uri = '/'): string
    {
        return self::join(self::clientBase(), $uri);
    }

    public static function staffUrl(string $uri = '/'): string
    {
        return self::join(self::staffBase(), $uri);
    }

    private static function configuredHost(string $key): string
    {
        $public = rtrim((string) env($key, ''), '/');
        if ($public === '') {
            return '';
        }

        return strtolower((string) parse_url($public, PHP_URL_HOST));
    }

    private static function publicBase(string $key, string $local): string
    {
        $host = request()?->getHost();
        $public = rtrim((string) env($key, ''), '/');
        if ($public !== '' && $host && ! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return $public;
        }

        return $local;
    }

    private static function join(string $base, string $uri): string
    {
        if ($uri === '' || $uri === '/') {
            return $base.'/';
        }

        return $base.'/'.ltrim($uri, '/');
    }
}
