<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kto dzwoni?</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#111;color:white;font-family:Arial,Helvetica,sans-serif}
.navbar{height:70px;background:#1b1b1b;display:flex;align-items:center;padding:0 30px;gap:10px;box-shadow:0 2px 10px rgba(0,0,0,.4)}
.logo{font-size:24px;font-weight:bold;margin-right:40px}
.navbar a{color:#ccc;text-decoration:none;padding:12px 20px;border-radius:8px}
.navbar a:hover,.navbar a.active{background:#333;color:white}
#app{min-height:calc(100vh - 70px);display:flex;justify-content:center;align-items:center;padding:30px}
.panel{width:100%;max-width:900px;background:#202020;border-radius:20px;padding:40px;box-shadow:0 10px 40px rgba(0,0,0,.6)}
.bell{text-align:center;font-size:80px;color:#00e676;animation:bellRing .8s infinite}
.ring{text-align:center;color:#00e676;font-weight:bold;font-size:24px;letter-spacing:2px;margin-bottom:25px}
@keyframes bellRing{0%{transform:rotate(0)}25%{transform:rotate(15deg)}50%{transform:rotate(-15deg)}75%{transform:rotate(8deg)}100%{transform:rotate(0)}}
.name{text-align:center;font-size:42px;font-weight:bold;color:white;margin-top:20px;margin-bottom:8px}
.number{text-align:center;font-size:32px;font-weight:bold;color:#ddd}
.callerid{text-align:center;font-size:20px;color:#aaa;margin-top:8px;margin-bottom:25px}
.status{text-align:center;font-size:28px;font-weight:bold}.status.idle{color:#888}
.flag{display:table;margin:0 auto 25px;padding:9px 18px;border-radius:20px;font-weight:bold;border:1px solid}
.flag.positive{background:#123b25;color:#00e676;border-color:#00e676}
.flag.negative{background:#451919;color:#ff5252;border-color:#ff5252}
.flag.neutral{background:#453b16;color:#ffd740;border-color:#ffd740}
.note{max-width:700px;margin:20px auto;padding:18px;background:#151515;border-left:4px solid #00e676;border-radius:8px;line-height:1.5}
.stats{display:flex;justify-content:center;gap:60px;text-align:center;margin-top:30px}
.stat{font-size:18px}.stat strong{display:block;font-size:40px;margin-bottom:5px}
.time{text-align:center;color:#aaa;margin-top:30px;font-size:20px}
.no-data{text-align:center;color:#aaa;padding:50px;font-size:22px}
@media(max-width:700px){.navbar{padding:0 15px}.logo{font-size:19px;margin-right:10px}.navbar a{padding:10px}.panel{padding:25px}.name{font-size:32px}.number{font-size:26px}.stats{gap:20px}.stat strong{font-size:30px}}
</style>
</head>
<body>
<nav class="navbar">
<div class="logo">☎ KTO DZWONI?</div>
<a href="index.php" class="active">Monitor</a>
<a href="szukaj.php">Szukaj</a>
</nav>

<div id="app">
<div class="panel">
<div id="content">
<div class="status idle">BRAK AKTYWNEGO POŁĄCZENIA</div>
<div class="no-data">Oczekiwanie na połączenie...</div>
</div>
</div>
</div>

<script>
let currentUniqueid=null;
let startTime=null;

function escapeHtml(value){
    return String(value ?? '').replace(/[&<>"']/g,function(c){
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
    });
}

function formatNumber(number){
    number=String(number||'');
    if(number.length===9){
        return '+48 '+number.substring(0,3)+' '+number.substring(3,6)+' '+number.substring(6,9);
    }
    return number;
}

function getFlag(sentiment){
    if(sentiment==='positive') return '<div class="flag positive">🟢 POZYTYWNY</div>';
    if(sentiment==='negative') return '<div class="flag negative">🔴 NEGATYWNY</div>';
    if(sentiment==='neutral') return '<div class="flag neutral">🟡 NEUTRALNY</div>';
    return '';
}

function updateTimer(){
    if(!startTime)return;
    const seconds=Math.max(0,Math.floor((Date.now()-startTime)/1000));
    const min=Math.floor(seconds/60).toString().padStart(2,'0');
    const sec=(seconds%60).toString().padStart(2,'0');
    const timer=document.getElementById('timer');
    if(timer)timer.textContent=min+':'+sec;
}

function showCall(data){
    if(currentUniqueid!==data.uniqueid){
        currentUniqueid=data.uniqueid;
        startTime=new Date(data.started_at.replace(' ','T'));
    }

    let html='<div class="bell">🔔</div><div class="ring">POŁĄCZENIE PRZYCHODZĄCE</div>';

    if(data.display_name){
        html+='<div class="name">'+escapeHtml(data.display_name)+'</div>';
    }

    html+='<div class="number">'+escapeHtml(formatNumber(data.number))+'</div>';
    html+='<div class="callerid">'+escapeHtml(data.callerid||'')+'</div>';
    html+=getFlag(data.sentiment);

    if(data.note){
        html+='<div class="note"><strong>WŁASNA INFORMACJA</strong><br>'+escapeHtml(data.note)+'</div>';
    }

    if(Number(data.has_data)===1){
        html+='<div class="stats">';
        html+='<div class="stat"><strong>'+escapeHtml(data.positive)+'</strong>pozytywnych</div>';
        html+='<div class="stat"><strong>'+escapeHtml(data.negative)+'</strong>negatywnych</div>';
        html+='<div class="stat"><strong>'+escapeHtml(data.neutral)+'</strong>neutralnych</div>';
        html+='<div class="stat"><strong>'+escapeHtml(data.total)+'</strong>opinii</div>';
        html+='</div>';
    }else{
        html+='<div class="no-data">Brak danych w bazie OdebracTelefon.pl</div>';
    }

    html+='<div class="time">Czas połączenia: <strong id="timer">00:00</strong></div>';
    document.getElementById('content').innerHTML=html;
    updateTimer();
}

function showIdle(){
    currentUniqueid=null;
    startTime=null;
    document.getElementById('content').innerHTML=
        '<div class="status idle">BRAK AKTYWNEGO POŁĄCZENIA</div>'+
        '<div class="no-data">Oczekiwanie na połączenie...</div>';
}

async function checkCall(){
    try{
        const response=await fetch('api.php?t='+Date.now(),{cache:'no-store'});
        const data=await response.json();
        if(data.active)showCall(data);else showIdle();
    }catch(error){console.error('API error:',error);}
}

setInterval(checkCall,1000);
setInterval(updateTimer,1000);
checkCall();
</script>
</body>
</html>
