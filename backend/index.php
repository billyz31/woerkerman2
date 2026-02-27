<?php
header('Content-Type: application/json');

$host = getenv('DB_HOST') ?: 'mysql';
$dbname = getenv('DB_DATABASE') ?: 'game';
$user = getenv('DB_USERNAME') ?: 'game';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/health') {
    echo json_encode(['status' => 'ok', 'time' => date('c')]);
    exit;
}

if ($uri === '/api/ping') {
    echo json_encode(['success' => true, 'message' => 'pong', 'time' => date('c')]);
    exit;
}

if ($uri === '/api/db-check') {
    try {
        $pdo->query("SELECT 1");
        echo json_encode(['success' => true, 'message' => 'Database connected']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($uri === '/api/db-health') {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'data' => ['tables_count' => count($tables), 'connection' => 'ok']]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($uri === '/api/redis-check') {
    echo json_encode(['success' => true, 'message' => 'Redis check skipped']);
    exit;
}

if ($uri === '/api/socket-check') {
    echo json_encode(['success' => true, 'message' => 'WebSocket not available']);
    exit;
}

if ($uri === '/api/perf/metrics') {
    echo json_encode(['success' => true, 'data' => ['recent' => [], 'count' => 0]]);
    exit;
}

if ($uri === '/api/slot/config') {
    echo json_encode([
        'success' => true,
        'data' => [
            'minBet' => 1,
            'maxBet' => 1000,
            'symbols' => ['🍒', '🍋', '🍇', '💎', '7️⃣'],
            'paylines' => 5,
            'reels' => 3
        ]
    ]);
    exit;
}

if ($method === 'POST' && $uri === '/api/login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $playerId = $input['playerId'] ?? $_POST['playerId'] ?? 'player-001';
    $secret = $input['secret'] ?? $_POST['secret'] ?? 'dev-secret';
    
    $stmt = $pdo->prepare("SELECT * FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        $stmt = $pdo->prepare("INSERT INTO players (player_id, role, balance) VALUES (?, 'player', 10000)");
        $stmt->execute([$playerId]);
        $player = ['player_id' => $playerId, 'role' => 'player', 'balance' => 10000];
    }
    
    $token = base64_encode(json_encode(['playerId' => $playerId, 'exp' => time() + 3600]));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'token' => $token,
            'playerId' => $player['player_id'],
            'role' => $player['role']
        ]
    ]);
    exit;
}

if ($method === 'GET' && $uri === '/api/wallet/balance') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    $payload = json_decode(base64_decode($token), true);
    $playerId = $payload['playerId'] ?? 'player-001';
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($player) {
        echo json_encode(['success' => true, 'data' => ['playerId' => $playerId, 'balance' => (int)$player['balance'], 'source' => 'database']]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Player not found']);
    }
    exit;
}

if ($method === 'POST' && $uri === '/api/wallet/credit') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    $payload = json_decode(base64_decode($token), true);
    $playerId = $payload['playerId'] ?? 'player-001';
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = (int)($input['amount'] ?? $_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE players SET balance = balance + ? WHERE player_id = ?");
    $stmt->execute([$amount, $playerId]);
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'playerId' => $playerId,
            'balance' => (int)$player['balance'],
            'delta' => $amount,
            'ref' => uniqid(),
            'txId' => uniqid()
        ]
    ]);
    exit;
}

if ($method === 'POST' && $uri === '/api/wallet/debit') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    $payload = json_decode(base64_decode($token), true);
    $playerId = $payload['playerId'] ?? 'player-001';
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = (int)($input['amount'] ?? $_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player || $player['balance'] < $amount) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE players SET balance = balance - ? WHERE player_id = ?");
    $stmt->execute([$amount, $playerId]);
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'playerId' => $playerId,
            'balance' => (int)$player['balance'],
            'delta' => -$amount,
            'ref' => uniqid(),
            'txId' => uniqid()
        ]
    ]);
    exit;
}

if ($method === 'POST' && $uri === '/api/slot/spin') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);
    $payload = json_decode(base64_decode($token), true);
    $playerId = $payload['playerId'] ?? 'player-001';
    $input = json_decode(file_get_contents('php://input'), true);
    $bet = (int)($input['bet'] ?? $_POST['bet'] ?? 10);
    
    if ($bet < 1 || $bet > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid bet']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player || $player['balance'] < $bet) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE players SET balance = balance - ? WHERE player_id = ?");
    $stmt->execute([$bet, $playerId]);
    
    $symbols = ['🍒', '🍋', '🍇', '💎', '7️⃣'];
    $reels = [
        $symbols[array_rand($symbols)],
        $symbols[array_rand($symbols)],
        $symbols[array_rand($symbols)]
    ];
    
    $win = 0;
    if ($reels[0] === $reels[1] && $reels[1] === $reels[2]) {
        $multipliers = ['🍒' => 10, '🍋' => 15, '🍇' => 20, '💎' => 50, '7️⃣' => 100];
        $win = $bet * ($multipliers[$reels[0]] ?? 10);
        
        $stmt = $pdo->prepare("UPDATE players SET balance = balance + ? WHERE player_id = ?");
        $stmt->execute([$win, $playerId]);
    }
    
    $stmt = $pdo->prepare("SELECT balance FROM players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'reels' => $reels,
            'bet' => $bet,
            'win' => $win,
            'balance' => (int)$player['balance'],
            'roundId' => uniqid()
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
