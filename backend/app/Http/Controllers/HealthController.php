<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'game-backend'
        ]);
    }

    public function full()
    {
        $dbStatus = $this->checkDatabase();
        $redisStatus = $this->checkRedis();

        return response()->json([
            'status' => $dbStatus && $redisStatus ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $dbStatus,
                'redis' => $redisStatus,
            ]
        ]);
    }

    public function dbCheck()
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['success' => true, 'message' => 'Database connected']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dbHealth()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $size = DB::select('SELECT ROUND(SUM(data_length + index_length) / 1024, 2) as size_kb FROM information_schema.tables WHERE table_schema = DATABASE()');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'tables_count' => count($tables),
                    'size_kb' => $size[0]->size_kb ?? 0,
                    'connection' => 'ok'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function redisCheck()
    {
        try {
            Redis::connection()->ping();
            return response()->json(['success' => true, 'message' => 'Redis connected']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function socketCheck()
    {
        return response()->json([
            'success' => true,
            'message' => 'WebSocket service available at :3001',
            'endpoint' => '/socket.io/'
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
