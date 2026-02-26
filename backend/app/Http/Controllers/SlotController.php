<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SlotController extends Controller
{
    private int $minBet;
    private int $maxBet;

    public function __construct()
    {
        $this->minBet = (int) env('SLOT_MIN_BET', 1);
        $this->maxBet = (int) env('SLOT_MAX_BET', 1000);
    }

    public function config()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'minBet' => $this->minBet,
                'maxBet' => $this->maxBet,
                'symbols' => ['🍒', '🍋', '🍇', '💎', '7️⃣'],
                'paylines' => 5,
                'reels' => 3
            ]
        ]);
    }

    public function spin(Request $request)
    {
        $playerId = $request->get('player_id');
        $bet = (int) $request->input('bet', $this->minBet);

        if ($bet < $this->minBet || $bet > $this->maxBet) {
            return response()->json([
                'success' => false,
                'message' => "Bet must be between {$this->minBet} and {$this->maxBet}"
            ], 400);
        }

        $player = Player::where('player_id', $playerId)->first();

        if (!$player || $player->balance < $bet) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        $player->balance -= $bet;
        $player->save();

        $symbols = ['🍒', '🍋', '🍇', '💎', '7️⃣'];
        $reels = [
            $symbols[array_rand($symbols)],
            $symbols[array_rand($symbols)],
            $symbols[array_rand($symbols)]
        ];

        $win = 0;
        if ($reels[0] === $reels[1] && $reels[1] === $reels[2]) {
            $multipliers = [
                '🍒' => 10,
                '🍋' => 15,
                '🍇' => 20,
                '💎' => 50,
                '7️⃣' => 100
            ];
            $win = $bet * ($multipliers[$reels[0]] ?? 10);
            $player->balance += $win;
            $player->save();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reels' => $reels,
                'bet' => $bet,
                'win' => $win,
                'balance' => $player->balance,
                'roundId' => Str::uuid()->toString()
            ]
        ]);
    }
}
