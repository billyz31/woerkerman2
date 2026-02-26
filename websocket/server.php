<?php

require_once __DIR__ . '/vendor/autoload.php';

$backendUrl = getenv('BACKEND_URL') ?: 'http://backend:8080';

$worker = new \Workerman\Worker('websocket://0.0.0.0:3001');

$worker->onMessage = function($connection, $data) use ($backendUrl) {
    $message = json_decode($data, true);
    
    if (!isset($message['event'])) {
        $connection->send(json_encode(['error' => 'No event specified']));
        return;
    }

    switch ($message['event']) {
        case 'game_spin':
            $bet = $message['bet'] ?? 10;
            $token = $message['token'] ?? '';
            
            $ch = curl_init($backendUrl . '/api/slot/spin');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['bet' => $bet]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            $connection->send(json_encode([
                'event' => 'game_spin_result',
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? null,
                'message' => $result['message'] ?? ''
            ]));
            break;
            
        default:
            $connection->send(json_encode(['error' => 'Unknown event: ' . $message['event']]));
    }
};

$worker->onConnect = function($connection) {
    echo "New connection: {$connection->id}\n";
};

$worker->onClose = function($connection) {
    echo "Connection closed: {$connection->id}\n";
};

echo "WebSocket server starting on port 3001...\n";
\Workerman\Worker::runAll();
