<?php

namespace App\Support;

class CredencialPublicUrl
{
    public static function origin(): ?string
    {
        $origin = config('credenciales.public_url');
        if (!$origin && !app()->runningUnitTests()) {
            $file = storage_path('app/private/credencial-tunnel/active.json');
            $state = is_file($file) ? json_decode(file_get_contents($file), true) : null;
            $origin = $state['url'] ?? null;
        }

        if (!is_string($origin) || !filter_var($origin, FILTER_VALIDATE_URL)
            || !in_array(parse_url($origin, PHP_URL_SCHEME), ['http', 'https'], true)
            || parse_url($origin, PHP_URL_USER) || parse_url($origin, PHP_URL_PASS)
            || parse_url($origin, PHP_URL_QUERY) || parse_url($origin, PHP_URL_FRAGMENT)
            || !in_array(parse_url($origin, PHP_URL_PATH), [null, '', '/'], true)) {
            return null;
        }

        return rtrim($origin, '/');
    }
}
