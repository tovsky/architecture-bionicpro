<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Vary: Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

try {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s(.+)/', $authHeader, $matches)) {
        throw new Exception('Authorization header missing');
    }

    $token = $matches[1];

    $client = new Client(['base_uri' => 'http://keycloak:8080']);
    $response = $client->get('/realms/reports-realm/protocol/openid-connect/certs');


    $jwks = json_decode($response->getBody(), true);

    $kid = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $token)[0]))->kid;
    $key = null;

    foreach ($jwks['keys'] as $k) {
        if ($k['kid'] === $kid) {
            $key = $k;
            break;
        }
    }

    if (!$key) {
        throw new Exception('Key not found');
    }

    $pemKey = "-----BEGIN CERTIFICATE-----\n" .
        chunk_split($key['x5c'][0], 64, "\n") .
        "-----END CERTIFICATE-----";

    $decoded = JWT::decode($token, new Key($pemKey, 'RS256'));

    if (!in_array('prothetic_user', $decoded->realm_access->roles)) {
        throw new Exception('Missing required role');
    }

    echo json_encode([
        'status' => 'success',
        'user' => [
            'name' => $decoded->preferred_username,
            'roles' => $decoded->realm_access->roles,
        ],
        'report' => ['data' => 'Test report']
    ]);

} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}