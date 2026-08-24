<?php
$config=require '/var/www/html/telefon/config.php';
$pdo=new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",$config['db_user'],$config['db_pass'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC));
$out='/var/www/html/telefon/cisco/directory.xml';
$rows=$pdo->query("SELECT number,display_name,callerid FROM call_history WHERE cisco_directory=1 ORDER BY display_name,number")->fetchAll();
$xml=new DOMDocument('1.0','UTF-8');$xml->formatOutput=true;$root=$xml->createElement('CiscoIPPhoneDirectory');$xml->appendChild($root);
foreach($rows as $r){$name=trim($r['display_name']??'');if($name==='')$name=trim($r['callerid']??'');if($name==='')$name=$r['number'];$n=preg_replace('/[^0-9+]/','',$r['number']);$e=$xml->createElement('DirectoryEntry');$a=$xml->createElement('Name');$a->appendChild($xml->createTextNode($name));$e->appendChild($a);$b=$xml->createElement('Telephone');$b->appendChild($xml->createTextNode($n));$e->appendChild($b);$root->appendChild($e);}
if(!$xml->save($out)){http_response_code(500);exit('Nie można zapisać XML');}
echo 'OK - wpisów: '.count($rows).PHP_EOL;
