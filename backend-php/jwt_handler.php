<?php
// =============================================================
//  jwt_handler.php — Pure PHP JWT Implementation (no Composer)
// =============================================================

class JWTHandler {
    private static $algo = 'HS256';

    /**
     * Create a JWT token.
     */
    public static function encode(array $payload, ?string $secret = null): string {
        $secret = $secret ?: JWT_SECRET;
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => self::$algo]));
        $payload_encoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload_encoded", $secret, true)
        );
        return "$header.$payload_encoded.$signature";
    }

    /**
     * Decode and verify a JWT token.
     */
    public static function decode(string $token, ?string $secret = null): ?array {
        $secret = $secret ?: JWT_SECRET;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expected_sig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        );
        if (!hash_equals($expected_sig, $signature)) return null;

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data) return null;

        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) return null;

        return $data;
    }

    /**
     * Create an access token.
     */
    public static function createAccessToken(string $identity, array $claims = []): string {
        $payload = array_merge($claims, [
            'sub' => $identity,
            'iat' => time(),
            'exp' => time() + JWT_ACCESS_EXPIRY,
            'type' => 'access',
        ]);
        return self::encode($payload);
    }

    /**
     * Create a refresh token.
     */
    public static function createRefreshToken(string $identity, array $claims = []): string {
        $payload = array_merge($claims, [
            'sub' => $identity,
            'iat' => time(),
            'exp' => time() + JWT_REFRESH_EXPIRY,
            'type' => 'refresh',
        ]);
        return self::encode($payload);
    }

    /**
     * Extract token from Authorization header or GET parameter.
     */
    public static function getTokenFromHeader(): ?string {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return $m[1];
        }
        if (!empty($_GET['token'])) {
            return $_GET['token'];
        }
        return null;
    }

    /**
     * Verify JWT and return claims or null.
     */
    public static function verifyRequest(string $type = 'access'): ?array {
        $token = self::getTokenFromHeader();
        if (!$token) return null;
        $claims = self::decode($token);
        if (!$claims) return null;
        if (($claims['type'] ?? '') !== $type) return null;
        return $claims;
    }

    /**
     * Get current user identity from JWT.
     */
    public static function getIdentity(): ?string {
        $claims = self::verifyRequest();
        return $claims['sub'] ?? null;
    }

    /**
     * Get all JWT claims.
     */
    public static function getClaims(): ?array {
        return self::verifyRequest();
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
