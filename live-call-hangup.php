#!/usr/bin/php
<?php

$db_host = 'localhost';
$db_name = 'asterisk';
$db_user = 'freepbxuser';
$db_pass = 'CHANGE_ME';

$log_file = '/var/log/asterisk/live-call.log';

$uniqueid = $argv[1] ?? '';

function live_log($message)
{
    global $log_file;
    file_put_contents(
        $log_file,
        date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

if ($uniqueid === '') {
    live_log('BLAD HANGUP: brak uniqueid');
    exit;
}

try {

    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );

    $stmt = $pdo->prepare(
        "DELETE FROM live_calls WHERE uniqueid = ?"
    );

    $stmt->execute(array($uniqueid));

    live_log(
        'KONIEC POLACZENIA: ' .
        $uniqueid .
        ' usunieto=' .
        $stmt->rowCount()
    );

} catch (PDOException $e) {

    live_log(
        'BLAD HANGUP BAZY: ' .
        $e->getMessage()
    );
}

exit(0);
