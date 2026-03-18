<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('gestoraInvitationSecret')) {
    function gestoraInvitationSecret()
    {
        $secretSource = (string) ($_ENV['SERVICE_APIKEY'] ?? $_ENV['DATABASE_APIKEY'] ?? '');

        if ($secretSource === '') {
            $secretSource = 'the-nomadapp-gestora';
        }

        return hash('sha256', $secretSource);
    }

    function createGestoraInvitationToken(array $claims, $ttlSeconds = 604800)
    {
        $now = time();

        $payload = array_merge([
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int) $ttlSeconds,
        ], $claims);

        return JWT::encode($payload, gestoraInvitationSecret(), 'HS256');
    }

    function decodeGestoraInvitationToken($token)
    {
        $decoded = JWT::decode($token, new Key(gestoraInvitationSecret(), 'HS256'));

        return json_decode(json_encode($decoded), true);
    }
}