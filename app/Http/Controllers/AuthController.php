<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\KeycloakService;

class AuthController extends Controller
{
    protected $keycloak;

    public function __construct(KeycloakService $keycloak)
    {
        $this->keycloak = $keycloak;
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $response = $this->keycloak->registerUser(
            $request->input('name'),

            $request->input('username'),
            $request->input('email'),
            $request->input('password')
        );



        if (isset($response['id'])) {
            $keycloakId = $response['id']; // Extract Keycloak User ID

            // Register user in the local database
            $user = User::create([
                'username' => $request->input('username'),
                'name'=>$request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')), // Store hashed password for reference (optional)
                'keycloak_id' => $keycloakId, // Store Keycloak ID
            ]);

            return response()->json(['message' => 'User registered successfully'], 201);
        } else {
            return response()->json(['error' => 'Failed to register user in Keycloak'], 400);
        }


          }

          public function login(Request $request)
          {
              $request->validate([
                  'username' => 'required|string',
                  'password' => 'required|string',
              ]);

              // Authenticate with Keycloak
              $response = $this->keycloak->authenticateUser(
                  $request->input('username'),
                  $request->input('password')
              );

              if ($response['access_token']) {
                  $keycloakId = $response['id_token_claims']['sub']; // Keycloak User ID (subject)

                  // Find the user in your local database by Keycloak ID
                  $user = User::where('keycloak_id', $keycloakId)->first();

                  if ($user) {
                      // Return local user data along with the Keycloak token
                      return response()->json([
                          'token' => $response['access_token'],
                          'user' => $user,
                      ]);
                  } else {
                      return response()->json(['error' => 'User not found in local database'], 404);
                  }
              } else {
                  return response()->json(['error' => 'Invalid credentials'], 401);
              }
          }

}
