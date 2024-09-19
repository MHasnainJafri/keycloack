<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class KeycloakService
{
    protected $baseUrl;
    protected $realm;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUrl = env('KEYCLOAK_SERVER_URL');
        $this->realm = env('KEYCLOAK_REALM');
        $this->clientId = env('KEYCLOAK_CLIENT_ID');
        $this->clientSecret = env('KEYCLOAK_CLIENT_SECRET');
    }

    public function getAccessToken()
    {
        $response = Http::asForm()->post("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

       $response = json_decode($response->getBody()->getContents());

        if (isset($response->access_token)) {
            return $response->access_token;
        } else {
            // Log or handle error appropriately
            dd($response); // Debug output
        }

    }

    public function registerUser($name,$username, $email, $password)
    {
        $token = $this->getAccessToken();

        // Send request to create user in Keycloak
        $response = Http::withToken($token)->post("{$this->baseUrl}/admin/realms/{$this->realm}/users", [
            'username' => $username,
            'email' => $email,
            'emailVerified' => true,
            'firstName' => $name,
            'lastName' => $name,
            'enabled' => true,
            'credentials' => [
                [
                    'type' => 'password',
                    'value' => $password,
                    'temporary' => false,
                ],
            ],
        ]);

        if ($response->status() === 201) {
            // Get Keycloak User ID from the 'Location' header
            $locationHeader = $response->header('Location');
            $userId = basename($locationHeader); // Extract user ID from the URL

            return ['id' => $userId];
        } else {
            // Handle error response
            return ['error' => $response->json()];
        }
    }

    public function authenticateUser($username, $password)
    {
        $response = Http::post("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token", [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'username' => $username,
            'password' => $password,
        ]);

        return $response->json();
    }
}
