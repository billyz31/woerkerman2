<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = (int) env('WALLET_CACHE_TTL', 30);
    }

    public function balance(Request $request)
    {
        $playerId = $request->get('player_id');

        $cacheKey = "wallet:balance:{$playerId}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'playerId' => $playerId,
                    'balance' => (int) $cached,
                    'source' => 'cache'
                ]
            ]);
        }

        $player = Player::where('player_id', $playerId)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        Redis::setex($cacheKey, $this->cacheTtl, $player->balance);

        return response()->json([
            'success' => true,
            'data' => [
                'playerId' => $playerId,
                'balance' => $player->balance,
                'source' => 'database'
            ]
        ]);
    }

    public function credit(Request $request)
    {
        return $this->updateBalance($request, 'credit');
    }

    public function debit(Request $request)
    {
        return $this->updateBalance($request, 'debit');
    }

    private function updateBalance(Request $request, string $type)
    {
        $playerId = $request->get('player_id');
        $amount = (int) $request->input('amount', 0);
        $ref = $request->input('ref', Str::uuid()->toString());

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid amount'
            ], 400);
        }

        $player = Player::where('player_id', $playerId)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        if ($type === 'debit' && $player->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        if ($type === 'credit') {
            $player->balance += $amount;
        } else {
            $player->balance -= $amount;
        }

        $player->save();

        $cacheKey = "wallet:balance:{$playerId}";
        Redis::del($cacheKey);

        return response()->json([
            'success' => true,
            'data' => [
                'playerId' => $playerId,
                'balance' => $player->balance,
                'delta' => $type === 'credit' ? $amount : -$amount,
                'ref' => $ref,
                'txId' => Str::uuid()->toString()
            ]
        ]);
    }
}
