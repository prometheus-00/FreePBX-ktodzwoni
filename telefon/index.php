<!DOCTYPE html>
<html lang="pl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Kto Dzwoni?</title>


<style>

/* ============================================================
 * RESET
 * ============================================================ */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background: #111;

    color: white;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


/* ============================================================
 * MENU
 * ============================================================ */

.navbar {

    height: 70px;

    background: #1b1b1b;

    border-bottom:
        1px solid #333;

    display: flex;

    align-items: center;

    padding: 0 30px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.4);

}


.logo {

    font-size: 24px;

    font-weight: bold;

    margin-right: 40px;

}


.menu {

    display: flex;

    gap: 10px;

}


.menu a {

    color: #ccc;

    text-decoration: none;

    padding: 12px 20px;

    border-radius: 8px;

}


.menu a:hover {

    background: #333;

    color: white;

}


.menu a.active {

    background: #333;

    color: white;

}


/* ============================================================
 * PANEL
 * ============================================================ */

#app {

    min-height:
        calc(100vh - 70px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px;

}


.panel {

    width: 100%;

    max-width: 950px;

    background: #202020;

    border-radius: 20px;

    padding: 40px;

    box-shadow:
        0 10px 40px
        rgba(0,0,0,.6);

}


/* ============================================================
 * DZWONEK
 * ============================================================ */

.bell {

    text-align: center;

    font-size: 90px;

    margin-bottom: 10px;

    animation:
        bellRing .8s
        infinite;

}


@keyframes bellRing {

    0% {
        transform: rotate(0deg);
    }

    25% {
        transform: rotate(15deg);
    }

    50% {
        transform: rotate(-15deg);
    }

    75% {
        transform: rotate(8deg);
    }

    100% {
        transform: rotate(0deg);
    }

}


/* ============================================================
 * STATUS
 * ============================================================ */

.status {

    text-align: center;

    font-size: 28px;

    font-weight: bold;

    margin-bottom: 30px;

}


.status.ringing {

    color: #00e676;

}


.status.idle {

    color: #888;

}


/* ============================================================
 * WŁASNA NAZWA
 * ============================================================ */

.display-name {

    text-align: center;

    font-size: 46px;

    font-weight: bold;

    margin-top: 10px;

    margin-bottom: 10px;

}


/* ============================================================
 * NUMER
 * ============================================================ */

.number {

    text-align: center;

    font-size: 34px;

    font-weight: bold;

    color: #ddd;

    margin-bottom: 8px;

}


.callerid {

    text-align: center;

    font-size: 20px;

    color: #999;

    margin-bottom: 25px;

}


/* ============================================================
 * WŁASNA FLAGA
 * ============================================================ */

.flag {

    display: table;

    margin:
        0 auto 25px;

    padding:
        9px 18px;

    border-radius: 20px;

    font-size: 18px;

    font-weight: bold;

    border:
        1px solid;

}


.flag.positive {

    background: #123b25;

    color: #00e676;

    border-color: #00e676;

}


.flag.negative {

    background: #451919;

    color: #ff5252;

    border-color: #ff5252;

}


.flag.neutral {

    background: #453b16;

    color: #ffd740;

    border-color: #ffd740;

}


/* ============================================================
 * WŁASNA INFORMACJA
 * ============================================================ */

.own-info {

    max-width: 750px;

    margin:
        0 auto 30px;

    padding: 20px;

    background: #151515;

    border-left:
        4px solid #1769aa;

    border-radius: 8px;

    line-height: 1.6;

}


.own-info-title {

    font-weight: bold;

    font-size: 15px;

    color: #aaa;

    margin-bottom: 8px;

}


/* ============================================================
 * ODEBRACTELEFON
 * ============================================================ */

.external {

    border-top:
        1px solid #333;

    padding-top: 25px;

    margin-top: 20px;

}


.external-title {

    text-align: center;

    font-size: 22px;

    font-weight: bold;

    margin-bottom: 20px;

    color: #aaa;

}


.rating {

    text-align: center;

    font-size: 28px;

    font-weight: bold;

    margin-bottom: 15px;

}


.category {

    text-align: center;

    font-size: 20px;

    color: #ccc;

    margin-bottom: 25px;

}


/* ============================================================
 * STATYSTYKI
 * ============================================================ */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 1px;

    background: #333;

    margin-bottom: 25px;

}


.stat {

    background: #151515;

    padding: 20px;

    text-align: center;

    font-size: 17px;

}


.stat strong {

    display: block;

    font-size: 38px;

    margin-bottom: 5px;

}


/* ============================================================
 * KATEGORIE
 * ============================================================ */

.categories {

    background: #151515;

    border-radius: 10px;

    padding: 20px;

    color: #ccc;

    line-height: 1.6;

}


.categories-title {

    font-weight: bold;

    color: white;

    margin-bottom: 8px;

}


/* ============================================================
 * CZAS
 * ============================================================ */

.time {

    text-align: center;

    margin-top: 30px;

    font-size: 20px;

    color: #999;

}


.time strong {

    color: white;

}


/* ============================================================
 * BRAK DANYCH
 * ============================================================ */

.no-data {

    text-align: center;

    font-size: 22px;

    color: #888;

    padding: 50px 0;

}


/* ============================================================
 * MOBILE
 * ============================================================ */

@media(max-width:700px) {

    .navbar {

        padding:
            0 15px;

    }


    .logo {

        font-size: 19px;

        margin-right: 15px;

    }


    .menu a {

        padding: 10px;

    }


    .panel {

        padding: 25px;

    }


    .display-name {

        font-size: 32px;

    }


    .number {

        font-size: 26px;

    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .stat strong {

        font-size: 30px;

    }

}

</style>

</head>


<body>


<!-- ==========================================================
     MENU
     ========================================================== -->

<nav class="navbar">

    <div class="logo">

        ☎ KTO DZWONI?

    </div>


    <div class="menu">

        <a
            href="index.php"
            class="active"
        >

            Monitor

        </a>


        <a
            href="szukaj.php"
        >

            Szukaj

        </a>

    </div>

</nav>



<!-- ==========================================================
     MONITOR
     ========================================================== -->

<div id="app">

    <div class="panel">

        <div id="content">


            <div class="status idle">

                BRAK AKTYWNEGO POŁĄCZENIA

            </div>


            <div class="no-data">

                Oczekiwanie na połączenie...

            </div>


        </div>

    </div>

</div>



<script>


/* ============================================================
 * ZMIENNE
 * ============================================================ */

let currentUniqueid = null;

let startTime = null;


/* ============================================================
 * BEZPIECZNE HTML
 * ============================================================ */

function escapeHtml(value)
{

    return String(
        value ?? ''
    ).replace(
        /[&<>"']/g,
        function(character)
        {

            return {

                '&': '&amp;',

                '<': '&lt;',

                '>': '&gt;',

                '"': '&quot;',

                "'": '&#039;'

            }[character];

        }
    );

}


/* ============================================================
 * FORMAT NUMERU
 * ============================================================ */

function formatNumber(number)
{

    number =
        String(number || '');


    if (number.length === 9) {

        return '+48 ' +

            number.substring(0,3) +

            ' ' +

            number.substring(3,6) +

            ' ' +

            number.substring(6,9);

    }


    return number;

}


/* ============================================================
 * FLAGA
 * ============================================================ */

function getFlag(sentiment)
{

    if (
        sentiment ===
        'positive'
    ) {

        return `

            <div class="flag positive">

                🟢 POZYTYWNY

            </div>

        `;

    }


    if (
        sentiment ===
        'negative'
    ) {

        return `

            <div class="flag negative">

                🔴 NEGATYWNY

            </div>

        `;

    }


    if (
        sentiment ===
        'neutral'
    ) {

        return `

            <div class="flag neutral">

                🟡 NEUTRALNY

            </div>

        `;

    }


    return '';

}


/* ============================================================
 * TIMER
 * ============================================================ */

function updateTimer()
{

    if (!startTime) {

        return;

    }


    const seconds =
        Math.max(
            0,
            Math.floor(
                (
                    Date.now() -
                    startTime
                ) / 1000
            )
        );


    const minutes =
        Math.floor(
            seconds / 60
        )
        .toString()
        .padStart(2,'0');


    const sec =
        (
            seconds % 60
        )
        .toString()
        .padStart(2,'0');


    const timer =
        document.getElementById(
            'timer'
        );


    if (timer) {

        timer.textContent =
            minutes +
            ':' +
            sec;

    }

}


/* ============================================================
 * POKAŻ POŁĄCZENIE
 * ============================================================ */

function showCall(data)
{

    /*
     * Nowe połączenie
     */

    if (
        currentUniqueid !==
        data.uniqueid
    ) {

        currentUniqueid =
            data.uniqueid;


        startTime =
            new Date(
                data.started_at
                    .replace(
                        ' ',
                        'T'
                    )
            );

    }


    let html = '';


    /* ========================================================
     * DZWONEK
     * ======================================================== */

    html += `

        <div class="bell">

            🔔

        </div>


        <div class="status ringing">

            POŁĄCZENIE PRZYCHODZĄCE

        </div>

    `;


    /* ========================================================
     * WŁASNA NAZWA
     * ======================================================== */

    if (
        data.display_name
    ) {

        html += `

            <div class="display-name">

                ${escapeHtml(
                    data.display_name
                )}

            </div>

        `;

    }


    /* ========================================================
     * NUMER
     * ======================================================== */

    html += `

        <div class="number">

            ${escapeHtml(
                formatNumber(
                    data.number
                )
            )}

        </div>

    `;


    /* ========================================================
     * CALLER ID
     * ======================================================== */

    if (
        data.callerid
    ) {

        html += `

            <div class="callerid">

                ${escapeHtml(
                    data.callerid
                )}

            </div>

        `;

    }


    /* ========================================================
     * WŁASNA FLAGA
     * ======================================================== */

    html +=
        getFlag(
            data.sentiment
        );


    /* ========================================================
     * WŁASNA INFORMACJA
     * ======================================================== */

    if (
        data.note
    ) {

        html += `

            <div class="own-info">

                <div class="own-info-title">

                    WŁASNA INFORMACJA

                </div>


                ${escapeHtml(
                    data.note
                )}

            </div>

        `;

    }


    /* ========================================================
     * ODEBRACTELEFON
     * ======================================================== */

    if (
        Number(
            data.has_data
        ) === 1
    ) {


        html += `

            <div class="external">

                <div class="external-title">

                    DANE Z ODEBRACTELEFON.PL

                </div>

        `;


        /* Rating */

        if (
            data.rating
        ) {

            html += `

                <div class="rating">

                    ${escapeHtml(
                        data.rating
                    )}

                </div>

            `;

        }


        /* Kategoria */

        if (
            data.main_category
        ) {

            html += `

                <div class="category">

                    Kategoria:

                    <strong>

                        ${escapeHtml(
                            data.main_category
                        )}

                    </strong>

                </div>

            `;

        }


        /* Statystyki */

        html += `

            <div class="stats">


                <div class="stat">

                    <strong>

                        ${escapeHtml(
                            data.positive
                        )}

                    </strong>

                    pozytywnych

                </div>


                <div class="stat">

                    <strong>

                        ${escapeHtml(
                            data.negative
                        )}

                    </strong>

                    negatywnych

                </div>


                <div class="stat">

                    <strong>

                        ${escapeHtml(
                            data.neutral
                        )}

                    </strong>

                    neutralnych

                </div>


                <div class="stat">

                    <strong>

                        ${escapeHtml(
                            data.total
                        )}

                    </strong>

                    opinii

                </div>


            </div>

        `;


        /* Kategorie */

        if (
            data.categories
        ) {

            html += `

                <div class="categories">

                    <div class="categories-title">

                        Kategorie

                    </div>


                    ${escapeHtml(
                        data.categories
                    )}

                </div>

            `;

        }


        html += `

            </div>

        `;


    } else {


        html += `

            <div class="no-data">

                Brak danych o tym numerze
                w bazie OdebracTelefon.pl

            </div>

        `;

    }


    /* ========================================================
     * CZAS
     * ======================================================== */

    html += `

        <div class="time">

            Czas połączenia:

            <strong id="timer">

                00:00

            </strong>

        </div>

    `;


    document.getElementById(
        'content'
    ).innerHTML =
        html;


    updateTimer();

}


/* ============================================================
 * BRAK POŁĄCZENIA
 * ============================================================ */

function showIdle()
{

    currentUniqueid = null;

    startTime = null;


    document.getElementById(
        'content'
    ).innerHTML = `

        <div class="status idle">

            BRAK AKTYWNEGO POŁĄCZENIA

        </div>


        <div class="no-data">

            Oczekiwanie na połączenie...

        </div>

    `;

}


/* ============================================================
 * API
 * ============================================================ */

async function checkCall()
{

    try {

        const response =
            await fetch(
                'api.php?t=' +
                Date.now(),
                {
                    cache:
                        'no-store'
                }
            );


        const data =
            await response.json();


        if (
            data.active
        ) {

            showCall(
                data
            );

        } else {

            showIdle();

        }


    } catch (error) {

        console.error(
            'API error:',
            error
        );

    }

}


/* ============================================================
 * ODŚWIEŻANIE
 * ============================================================ */

setInterval(
    checkCall,
    1000
);


setInterval(
    updateTimer,
    1000
);


/* ============================================================
 * START
 * ============================================================ */

checkCall();

</script>


</body>

</html>
