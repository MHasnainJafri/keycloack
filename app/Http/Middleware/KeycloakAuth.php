<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KeycloakAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $response = Http::withToken($token)->get(env('KEYCLOAK_SERVER_URL').'/realms/'.env('KEYCLOAK_REALM').'/protocol/openid-connect/userinfo');
// dd($response->getBody()->getContents());
        if ($response->status() !== 200) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
     $user = json_decode($response->getBody()->getContents());

     $user =  User::where('keycloak_id', $user->sub)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }else{
          \Auth::login($user);

        }

        return $next($request);
    }
}
