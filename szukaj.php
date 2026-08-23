<?php
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
} catch (PDOException $e) {
    http_response_code(500);
    exit('Błąd połączenia z bazą danych.');
}

function h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function normalize_number($number){
    $number=preg_replace('/[^0-9]/','',$number);
    if(substr($number,0,4)==='0048' && strlen($number)===13)$number=substr($number,4);
    if(substr($number,0,2)==='48' && strlen($number)===11)$number=substr($number,2);
    return $number;
}
function format_number($number){
    $number=normalize_number($number);
    if(strlen($number)===9)return '+48 '.substr($number,0,3).' '.substr($number,3,3).' '.substr($number,6);
    return $number;
}
function sentiment_label($sentiment){
    if($sentiment==='positive')return '🟢 POZYTYWNY';
    if($sentiment==='negative')return '🔴 NEGATYWNY';
    if($sentiment==='neutral')return '🟡 NEUTRALNY';
    return '—';
}

$number=normalize_number($_GET['number'] ?? $_POST['number'] ?? '');
$message='';
$error='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save'])){
    $display_name=trim($_POST['display_name'] ?? '');
    $note=trim($_POST['note'] ?? '');
    $sentiment=$_POST['sentiment'] ?? '';

    if(!in_array($sentiment,array('','positive','neutral','negative'),true))$sentiment='';

    if(strlen($number)!==9){
        $error='Nieprawidłowy numer telefonu.';
    }else{
        $stmt=$pdo->prepare("SELECT id FROM call_history WHERE number=? LIMIT 1");
        $stmt->execute(array($number));
        $existing=$stmt->fetch();

        if($existing){
            $stmt=$pdo->prepare("
                UPDATE call_history
                SET display_name=?, note=?, sentiment=?
                WHERE id=?
            ");
            $stmt->execute(array(
                $display_name!==''?$display_name:null,
                $note!==''?$note:null,
                $sentiment!==''?$sentiment:null,
                $existing['id']
            ));
        }else{
            $stmt=$pdo->prepare("
                INSERT INTO call_history
                (uniqueid,number,callerid,display_name,first_called_at,last_called_at,call_count,note,sentiment)
                VALUES(?,?,?,?,NOW(),NOW(),0,?,?)
            ");
            $stmt->execute(array(
                'manual-'.$number.'-'.time(),
                $number,
                $number,
                $display_name!==''?$display_name:null,
                $note!==''?$note:null,
                $sentiment!==''?$sentiment:null
            ));
        }
        $message='Informacje zapisane.';
    }
}

$history=null;
$phone_data=null;

if($number!==''){
    $stmt=$pdo->prepare("SELECT * FROM call_history WHERE number=? LIMIT 1");
    $stmt->execute(array($number));
    $history=$stmt->fetch();

    $stmt=$pdo->prepare("SELECT * FROM odebractelefon_cache WHERE number=? LIMIT 1");
    $stmt->execute(array($number));
    $phone_data=$stmt->fetch();
}

$stmt=$pdo->query("
    SELECT number,callerid,display_name,last_called_at,call_count,note,sentiment
    FROM call_history
    WHERE call_count>0
    ORDER BY last_called_at DESC
    LIMIT 30
");
$history_list=$stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Szukaj — Kto dzwoni?</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#111;color:white;font-family:Arial,Helvetica,sans-serif}
.navbar{height:70px;background:#1b1b1b;display:flex;align-items:center;padding:0 30px;gap:10px}
.logo{font-size:24px;font-weight:bold;margin-right:40px}
.navbar a{color:#ccc;text-decoration:none;padding:12px 20px;border-radius:8px}
.navbar a:hover,.navbar a.active{background:#333;color:white}
.page{max-width:1400px;margin:auto;padding:30px;display:grid;grid-template-columns:320px 1fr;gap:25px}
.box{background:#202020;border-radius:15px;padding:25px;box-shadow:0 8px 30px rgba(0,0,0,.5)}
.history a{display:block;color:white;text-decoration:none;background:#151515;padding:12px;border-radius:8px;margin:8px 0}
.history a:hover{background:#292929}
.history-name{font-size:18px;font-weight:bold;margin-bottom:4px}
.history-number{font-weight:bold;color:#ddd}
.history small{color:#999}
.history-note{color:#aaa;margin-top:8px;font-size:14px}
.search{display:flex;gap:10px}
.search input{flex:1;padding:14px;background:#111;color:white;border:1px solid #444;border-radius:8px;font-size:18px}
.button{padding:14px 20px;border:0;border-radius:8px;background:#1769aa;color:white;font-weight:bold;cursor:pointer}
.main-name{margin-top:30px;font-size:42px;font-weight:bold}
.main-number{font-size:28px;color:#ccc;margin-top:5px}
.edit{background:#151515;border:1px solid #333;border-radius:10px;padding:20px;margin-top:25px}
.edit input[type=text],.edit textarea{width:100%;padding:13px;background:#202020;color:white;border:1px solid #444;border-radius:8px;font-size:17px}
.edit textarea{min-height:100px;resize:vertical}
.options{display:flex;gap:15px;flex-wrap:wrap;margin:15px 0}
.flag{display:inline-block;padding:7px 12px;border-radius:15px;font-weight:bold;margin-top:15px}
.flag.positive{background:#123b25;color:#00e676}.flag.negative{background:#451919;color:#ff5252}.flag.neutral{background:#453b16;color:#ffd740}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#333;margin-top:25px}
.stat{background:#151515;text-align:center;padding:20px}.stat strong{display:block;font-size:30px;margin-bottom:5px}
.success{background:#123b25;color:#00e676;padding:12px;border-radius:8px;margin-top:20px}
.error{background:#451919;color:#ff5252;padding:12px;border-radius:8px;margin-top:20px}
.empty{text-align:center;color:#aaa;padding:50px}
@media(max-width:900px){.page{grid-template-columns:1fr}}
@media(max-width:600px){.page{padding:15px}.search{flex-direction:column}.stats{grid-template-columns:repeat(2,1fr)}.main-name{font-size:32px}}
</style>
</head>
<body>
<nav class="navbar">
<div class="logo">☎ KTO DZWONI?</div>
<a href="index.php">Monitor</a>
<a href="szukaj.php" class="active">Szukaj</a>
</nav>

<div class="page">
<aside class="box history">
<h2>Ostatnie połączenia</h2>
<?php if($history_list): ?>
<?php foreach($history_list as $item): ?>
<a href="?number=<?=urlencode($item['number'])?>">
<?php if(!empty($item['display_name'])): ?>
<div class="history-name"><?=h($item['display_name'])?></div>
<?php endif; ?>
<div class="history-number"><?=h(format_number($item['number']))?></div>
<?php if(!empty($item['callerid'])): ?><small><?=h($item['callerid'])?></small><?php endif; ?>
<br>
<small><?=h($item['last_called_at'])?> · <?=h($item['call_count'])?> połączeń</small>
<?php if(!empty($item['sentiment'])): ?><br><small><?=h(sentiment_label($item['sentiment']))?></small><?php endif; ?>
<?php if(!empty($item['note'])): ?><div class="history-note"><?=h($item['note'])?></div><?php endif; ?>
</a>
<?php endforeach; ?>
<?php else: ?>
<div class="empty">Brak historii połączeń.</div>
<?php endif; ?>
</aside>

<main class="box">
<form class="search" method="get">
<input type="text" name="number" value="<?=h($number)?>" placeholder="+48 668 190 504" autocomplete="off">
<button class="button" type="submit">SZUKAJ</button>
</form>

<?php if($message): ?><div class="success"><?=h($message)?></div><?php endif; ?>
<?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>

<?php if(!$number): ?>
<div class="empty">Wpisz numer telefonu.</div>
<?php elseif($history || $phone_data): ?>

<?php if($history && !empty($history['display_name'])): ?>
<div class="main-name"><?=h($history['display_name'])?></div>
<?php endif; ?>

<div class="main-number"><?=h(format_number($number))?></div>

<?php if($history && !empty($history['sentiment'])): ?>
<div class="flag <?=h($history['sentiment'])?>"><?=h(sentiment_label($history['sentiment']))?></div>
<?php endif; ?>

<div class="edit">
<h2>Informacje o numerze</h2>
<form method="post">
<input type="hidden" name="number" value="<?=h($number)?>">

<p><strong>Nazwa numeru</strong></p>
<input type="text" name="display_name" value="<?=h($history['display_name'] ?? '')?>" placeholder="np. Jan Kowalski, Firma ABC, Kurier">

<p><strong>Własna informacja</strong></p>
<textarea name="note" placeholder="np. klient, firma, telemarketing..."><?=h($history['note'] ?? '')?></textarea>

<p><strong>Klasyfikacja numeru</strong></p>
<div class="options">
<label><input type="radio" name="sentiment" value="" <?=(!$history || empty($history['sentiment']))?'checked':''?>> Brak</label>
<label><input type="radio" name="sentiment" value="positive" <?=($history && $history['sentiment']==='positive')?'checked':''?>> 🟢 Pozytywny</label>
<label><input type="radio" name="sentiment" value="neutral" <?=($history && $history['sentiment']==='neutral')?'checked':''?>> 🟡 Neutralny</label>
<label><input type="radio" name="sentiment" value="negative" <?=($history && $history['sentiment']==='negative')?'checked':''?>> 🔴 Negatywny</label>
</div>

<button class="button" type="submit" name="save" value="1">ZAPISZ INFORMACJE</button>
</form>
</div>

<?php if($phone_data): ?>
<div class="stats">
<div class="stat"><strong><?=h($phone_data['positive'])?></strong>Pozytywne</div>
<div class="stat"><strong><?=h($phone_data['negative'])?></strong>Negatywne</div>
<div class="stat"><strong><?=h($phone_data['neutral'])?></strong>Neutralne</div>
<div class="stat"><strong><?=h($phone_data['total'])?></strong>Wszystkie</div>
</div>
<?php else: ?>
<div class="empty">Brak danych w OdebracTelefon.pl</div>
<?php endif; ?>

<?php else: ?>
<div class="empty">Numer nie został znaleziony.</div>
<?php endif; ?>
</main>
</div>
</body>
</html>
