#!/usr/bin/php
<?php

$db_host = 'localhost';
$db_name = 'asterisk';
$db_user = 'freepbxuser';
$db_pass = 'CHANGE_ME';

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

function agi_read()
{
    $data = array();

    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') break;

        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $data[trim($key)] = trim($value);
        }
    }

    return $data;
}

function agi_command($command)
{
    echo $command . PHP_EOL;
    $result = fgets(STDIN);
    return $result === false ? '' : trim($result);
}

$agi = agi_read();

$channel = $agi['agi_channel'] ?? '';
$uniqueid = $agi['agi_uniqueid'] ?? '';
$linkedid = $agi['agi_linkedid'] ?? '';

$number_result = agi_command('GET VARIABLE CALLERID(num)');
$name_result = agi_command('GET VARIABLE CALLERID(name)');

$number = '';
if (preg_match('/^200 result=1 \((.*)\)$/', $number_result, $match)) {
    $number = $match[1];
}

$callerid = '';
if (preg_match('/^200 result=1 \((.*)\)$/', $name_result, $match)) {
    $callerid = $match[1];
}

$number = preg_replace('/[^0-9]/', '', $number);

if (substr($number, 0, 4) === '0048' && strlen($number) === 13) {
    $number = substr($number, 4);
}

if (substr($number, 0, 2) === '48' && strlen($number) === 11) {
    $number = substr($number, 2);
}

if ($uniqueid === '') {
    live_log('BLAD: brak agi_uniqueid');
    exit;
}

if ($number === '') {
    live_log('BLAD: brak numeru CallerID');
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
} catch (PDOException $e) {
    live_log('BLAD BAZY: ' . $e->getMessage());
    exit;
}

try {
    $stmt = $pdo->prepare("
        REPLACE INTO live_calls
        (uniqueid, linkedid, channel, number, callerid, started_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute(array(
        $uniqueid,
        $linkedid,
        $channel,
        $number,
        $callerid
    ));

} catch (PDOException $e) {
    live_log('BLAD INSERT: ' . $e->getMessage());
    exit;
}

live_log(
    'NOWE POLACZENIE: ' .
    'uniqueid=' . $uniqueid .
    ' channel=' . $channel .
    ' number=' . $number .
    ' callerid=' . $callerid
);

exit(0);
