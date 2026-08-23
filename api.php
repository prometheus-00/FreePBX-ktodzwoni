<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$config = require '/var/www/html/telefon/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('active' => false, 'error' => 'Błąd bazy danych'), JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_number($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 4) === '0048' && strlen($number) === 13) $number = substr($number, 4);
    if (substr($number, 0, 2) === '48' && strlen($number) === 11) $number = substr($number, 2);
    return $number;
}

$stmt = $pdo->query("SELECT * FROM live_calls ORDER BY started_at DESC LIMIT 1");
$live = $stmt->fetch();

if (!$live) {
    echo json_encode(array('active' => false), JSON_UNESCAPED_UNICODE);
    exit;
}

$number = normalize_number($live['number']);

$stmt = $pdo->prepare("
    SELECT display_name, note, sentiment
    FROM call_history
    WHERE number = ?
    LIMIT 1
");
$stmt->execute(array($number));
$history = $stmt->fetch();

if (!$history) {
    $history = array(
        'display_name' => null,
        'note' => null,
        'sentiment' => null
    );
}

$stmt = $pdo->prepare("
    SELECT rating, main_category, positive, negative, neutral,
           total, categories, has_data, checked_at
    FROM odebractelefon_cache
    WHERE number = ?
    LIMIT 1
");
$stmt->execute(array($number));
$data = $stmt->fetch();

if (!$data) {
    $data = array(
        'rating' => null,
        'main_category' => null,
        'positive' => 0,
        'negative' => 0,
        'neutral' => 0,
        'total' => 0,
        'categories' => '',
        'has_data' => 0,
        'checked_at' => null
    );
}

echo json_encode(array(
    'active' => true,
    'uniqueid' => $live['uniqueid'],
    'channel' => $live['channel'],
    'number' => $number,
    'display_name' => $history['display_name'],
    'callerid' => $live['callerid'],
    'started_at' => $live['started_at'],
    'note' => $history['note'],
    'sentiment' => $history['sentiment'],
    'rating' => $data['rating'],
    'main_category' => $data['main_category'],
    'positive' => (int)$data['positive'],
    'negative' => (int)$data['negative'],
    'neutral' => (int)$data['neutral'],
    'total' => (int)$data['total'],
    'categories' => $data['categories'],
    'has_data' => (int)$data['has_data'],
    'checked_at' => $data['checked_at']
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
