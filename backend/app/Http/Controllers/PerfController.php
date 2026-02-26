<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PerfController extends Controller
{
    public function metrics()
    {
        $key = 'perf:metrics';
        $metrics = Redis::lrange($key, 0, 49);
        
        $result = array_map(function($item) {
            return json_decode($item, true);
        }, $metrics);

        return response()->json([
            'success' => true,
            'data' => [
                'recent' => $result,
                'count' => count($result)
            ]
        ]);
    }
}
