<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$config = require '/var/www/html/telefon/config.php';


/* ============================================================
 * BAZA
 * ============================================================ */

try {

    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        array(
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC
        )
    );

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode(
        array(
            'active' => false,
            'error' => 'Błąd połączenia z bazą danych'
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* ============================================================
 * NORMALIZACJA NUMERU
 * ============================================================ */

function normalize_number($number)
{
    $number = preg_replace(
        '/[^0-9]/',
        '',
        $number
    );


    if (
        substr($number, 0, 4) === '0048' &&
        strlen($number) === 13
    ) {

        $number = substr(
            $number,
            4
        );

    }


    if (
        substr($number, 0, 2) === '48' &&
        strlen($number) === 11
    ) {

        $number = substr(
            $number,
            2
        );

    }


    return $number;
}


/* ============================================================
 * BRAK AKTYWNEGO POŁĄCZENIA
 * ============================================================ */

$stmt = $pdo->query("
    SELECT *
    FROM live_calls
    ORDER BY started_at DESC
    LIMIT 1
");


$live = $stmt->fetch();


if (!$live) {

    echo json_encode(
        array(
            'active' => false
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* ============================================================
 * NUMER
 * ============================================================ */

$number = normalize_number(
    $live['number']
);


/* ============================================================
 * WŁASNE INFORMACJE O NUMERZE
 *
 * Najważniejsze:
 * numer może zostać dodany ręcznie,
 * zanim kiedykolwiek zadzwoni.
 * ============================================================ */

$stmt = $pdo->prepare("
    SELECT
        id,
        number,
        callerid,
        display_name,
        note,
        sentiment,
        call_count,
        first_called_at,
        last_called_at

    FROM call_history

    WHERE number = ?

    ORDER BY
        call_count DESC,
        last_called_at DESC

    LIMIT 1
");


$stmt->execute(
    array($number)
);


$history =
    $stmt->fetch();


/*
 * Jeżeli numer nie ma jeszcze własnych informacji.
 */

if (!$history) {

    $history = array(
        'id' => null,
        'number' => $number,
        'callerid' => null,
        'display_name' => null,
        'note' => null,
        'sentiment' => null,
        'call_count' => 0,
        'first_called_at' => null,
        'last_called_at' => null
    );

}


/* ============================================================
 * DANE ODEBRACTELEFON
 * ============================================================ */

$stmt = $pdo->prepare("
    SELECT
        number,
        callerid,
        rating,
        main_category,
        positive,
        negative,
        neutral,
        total,
        categories,
        has_data,
        checked_at

    FROM odebractelefon_cache

    WHERE number = ?

    LIMIT 1
");


$stmt->execute(
    array($number)
);


$phone_data =
    $stmt->fetch();


/*
 * Brak danych OdebracTelefon
 */

if (!$phone_data) {

    $phone_data = array(
        'number' => $number,
        'callerid' => null,
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


/* ============================================================
 * ODPOWIEDŹ
 * ============================================================ */

$response = array(

    /*
     * Aktywne połączenie
     */

    'active' => true,

    'uniqueid' =>
        $live['uniqueid'],

    'linkedid' =>
        $live['linkedid'] ?? '',

    'channel' =>
        $live['channel'],

    'started_at' =>
        $live['started_at'],


    /*
     * Numer
     */

    'number' =>
        $number,

    'callerid' =>
        $live['callerid'],


    /*
     * WŁASNE DANE
     */

    'display_name' =>
        $history['display_name'],

    'note' =>
        $history['note'],

    'sentiment' =>
        $history['sentiment'],


    /*
     * Historia
     */

    'call_count' =>
        (int)$history['call_count'],


    /*
     * ODEBRACTELEFON
     */

    'rating' =>
        $phone_data['rating'],

    'main_category' =>
        $phone_data['main_category'],

    'positive' =>
        (int)$phone_data['positive'],

    'negative' =>
        (int)$phone_data['negative'],

    'neutral' =>
        (int)$phone_data['neutral'],

    'total' =>
        (int)$phone_data['total'],

    'categories' =>
        $phone_data['categories'],

    'has_data' =>
        (int)$phone_data['has_data'],

    'checked_at' =>
        $phone_data['checked_at']

);


/* ============================================================
 * JSON
 * ============================================================ */

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);
