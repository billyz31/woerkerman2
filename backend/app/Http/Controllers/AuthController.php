<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private string $jwtSecret;
    private int $jwtTtl;

    public function __construct()
    {
        $this->jwtSecret = env('JWT_SECRET', 'default-secret-change-me');
        $this->jwtTtl = (int) env('JWT_TTL', 3600);
    }

    public function login(Request $request)
    {
        $playerId = $request->input('playerId');
        $secret = $request->input('secret');

        if (!$playerId || !$secret) {
            return response()->json([
                'success' => false,
                'message' => 'Missing playerId or secret'
            ], 400);
        }

        $player = Player::where('player_id', $playerId)->first();

        if (!$player) {
            $player = Player::create([
                'player_id' => $playerId,
                'role' => 'player',
                'balance' => 10000
            ]);
        }

        $payload = [
            'playerId' => $player->player_id,
            'role' => $player->role,
            'iat' => time(),
            'exp' => time() + $this->jwtTtl
        ];

        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'playerId' => $player->player_id,
                'role' => $player->role
            ]
        ]);
    }

    public function me(Request $request)
    {
        $playerId = $request->get('player_id');
        
        $player = Player::where('player_id', $playerId)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'playerId' => $player->player_id,
                'role' => $player->role,
                'serverTime' => now()->toIso8601String()
            ]
        ]);
    }
}
