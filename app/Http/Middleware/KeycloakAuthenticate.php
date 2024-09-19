<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class KeycloakAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        // Fetch Keycloak public keys for the realm
        $keys = $this->getKeycloakPublicKeys();

        // Verify token using Firebase JWT and Keycloak public keys
        try {
            $decodedToken = JWT::decode($token, JWK::parseKeySet($keys), ['RS256']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Token is valid, allow the request to proceed
        return $next($request);
    }

    private function getKeycloakPublicKeys()
    {
        $response = Http::get('https://your-keycloak-server/auth/realms/{realm}/protocol/openid-connect/certs');
        return $response->json();
    }
}
