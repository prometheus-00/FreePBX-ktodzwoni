#!/usr/bin/php
<?php

$db_host = 'localhost';
$db_name = 'asterisk';
$db_user = 'freepbxuser';
$db_pass = 'eEmtb2fid6eQ';





$log_file = '/var/log/asterisk/live-call.log';

function live_log($message)
{
    global $log_file;

    file_put_contents(
        $log_file,
        date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL,
        FILE_APPEND
    );
}


/*
 * UNIQUEID przekazujemy jako pierwszy argument AGI.
 */
$uniqueid = isset($argv[1]) ? trim($argv[1]) : '';

if ($uniqueid === '') {

    live_log('HANGUP: brak UNIQUEID');

    exit(1);
}


try {

    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );

    $stmt = $pdo->prepare(
        "DELETE FROM live_calls WHERE uniqueid = ?"
    );

    $stmt->execute(array($uniqueid));

    live_log(
        'KONIEC POLACZENIA: uniqueid=' .
        $uniqueid .
        ' usunieto=' .
        $stmt->rowCount()
    );

} catch (PDOException $e) {

    live_log(
        'HANGUP BLAD BAZY: ' .
        $e->getMessage()
    );

    exit(1);
}

exit(0);
