#!/usr/bin/php
<?php
$config=require '/var/www/html/telefon/config.php';$u=$argv[1]??'';if($u==='')exit(1);$pdo=new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",$config['db_user'],$config['db_pass'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));$s=$pdo->prepare('DELETE FROM live_calls WHERE uniqueid=?');$s->execute([$u]);file_put_contents('/var/log/asterisk/live-call.log',date('Y-m-d H:i:s').' KONIEC POLACZENIA: '.$u.' usunieto='.$s->rowCount().PHP_EOL,FILE_APPEND);
