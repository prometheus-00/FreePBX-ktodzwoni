#!/usr/bin/php
<?php

/*
 * FreePBX / Asterisk
 * live-call.php
 *
 * Zapisuje:
 *
 * 1. aktualne połączenie do live_calls
 * 2. historię połączeń do call_history
 *
 * Uruchamianie:
 * AGI(/var/www/html/telefon/live-call.php)
 */


/* ============================================================
 * KONFIGURACJA BAZY
 * ============================================================ */

$db_host = 'localhost';
$db_name = 'asterisk';
$db_user = 'freepbxuser';
$db_pass ='xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';


/* ============================================================
 * LOG
 * ============================================================ */

$log_file = '/var/log/asterisk/live-call.log';


function live_log($message)
{
    global $log_file;

    $line =
        date('Y-m-d H:i:s') .
        ' ' .
        $message .
        PHP_EOL;

    file_put_contents(
        $log_file,
        $line,
        FILE_APPEND
    );
}


/* ============================================================
 * AGI - ODCZYT ZMIENNYCH
 * ============================================================ */

function agi_read()
{
    $data = array();

    while (($line = fgets(STDIN)) !== false) {

        $line = trim($line);

        if ($line === '') {
            break;
        }

        if (strpos($line, ':') !== false) {

            list($key, $value) =
                explode(':', $line, 2);

            $data[trim($key)] = trim($value);
        }
    }

    return $data;
}


/* ============================================================
 * AGI COMMAND
 * ============================================================ */

function agi_command($command)
{
    echo $command . PHP_EOL;

    $result = fgets(STDIN);

    if ($result === false) {
        return '';
    }

    return trim($result);
}


/* ============================================================
 * ODCZYT AGI
 * ============================================================ */

$agi = agi_read();


/* ============================================================
 * INFORMACJE O KANALE
 * ============================================================ */

$channel = isset($agi['agi_channel'])
    ? $agi['agi_channel']
    : '';

$uniqueid = isset($agi['agi_uniqueid'])
    ? $agi['agi_uniqueid']
    : '';

$linkedid = isset($agi['agi_linkedid'])
    ? $agi['agi_linkedid']
    : '';


/* ============================================================
 * CALLER ID
 * ============================================================ */

$number_result = agi_command(
    'GET VARIABLE CALLERID(num)'
);

$name_result = agi_command(
    'GET VARIABLE CALLERID(name)'
);


$number = '';

if (
    preg_match(
        '/^200 result=1 \((.*)\)$/',
        $number_result,
        $match
    )
) {
    $number = $match[1];
}


$callerid = '';

if (
    preg_match(
        '/^200 result=1 \((.*)\)$/',
        $name_result,
        $match
    )
) {
    $callerid = $match[1];
}


/* ============================================================
 * NORMALIZACJA NUMERU
 * ============================================================ */

$number = preg_replace(
    '/[^0-9]/',
    '',
    $number
);


/*
 * Polska numeracja:
 *
 * 0048XXXXXXXXX -> XXXXXXXXX
 * 48XXXXXXXXX   -> XXXXXXXXX
 */

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


/* ============================================================
 * WALIDACJA
 * ============================================================ */

if ($uniqueid === '') {

    live_log(
        'BLAD: brak agi_uniqueid'
    );

    exit;
}


if ($number === '') {

    live_log(
        'BLAD: brak numeru CallerID'
    );

    exit;
}


/* ============================================================
 * MYSQL
 * ============================================================ */

try {

    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC
        )
    );

} catch (PDOException $e) {

    live_log(
        'BLAD BAZY: ' .
        $e->getMessage()
    );

    exit;
}


/* ============================================================
 * TRANSAKCJA
 * ============================================================ */

try {

    $pdo->beginTransaction();


    /* ========================================================
     * 1. AKTYWNE POŁĄCZENIE
     * ======================================================== */

    $sql = "
        REPLACE INTO live_calls
        (
            uniqueid,
            linkedid,
            channel,
            number,
            callerid,
            started_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW()
        )
    ";


    $stmt = $pdo->prepare($sql);


    $stmt->execute(array(
        $uniqueid,
        $linkedid,
        $channel,
        $number,
        $callerid
    ));


    /* ========================================================
     * 2. HISTORIA POŁĄCZEŃ
     * ======================================================== */

 $sql = "
    INSERT INTO call_history
    (
        uniqueid,
        number,
        callerid,
        first_called_at,
        last_called_at,
        call_count
    )
    VALUES
    (
        ?,
        ?,
        ?,
        NOW(),
        NOW(),
        1
    )
    ON DUPLICATE KEY UPDATE
        uniqueid = VALUES(uniqueid),
        callerid = VALUES(callerid),
        last_called_at = NOW(),
        call_count = call_count + 1
 ";


    $stmt = $pdo->prepare($sql);


    $stmt->execute(array(
        $uniqueid,
        $number,
        $callerid
    ));


    $pdo->commit();


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    live_log(
        'BLAD ZAPISU: ' .
        $e->getMessage()
    );


    exit;
}


/* ============================================================
 * LOG
 * ============================================================ */

live_log(
    'NOWE POLACZENIE: ' .
    'uniqueid=' . $uniqueid .
    ' channel=' . $channel .
    ' number=' . $number .
    ' callerid=' . $callerid
);


/*
 * Informacja dodatkowa do logu.
 */

live_log(
    'HISTORIA: zapisano numer=' .
    $number
);


/* ============================================================
 * KONIEC
 * ============================================================ */

exit(0);
