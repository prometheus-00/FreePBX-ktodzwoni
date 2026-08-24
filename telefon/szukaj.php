<?php

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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );

} catch (PDOException $e) {

    http_response_code(500);

    exit('Błąd połączenia z bazą danych.');

}


/* ============================================================
 * FUNKCJE
 * ============================================================ */

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


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


function format_number($number)
{
    $number = normalize_number($number);


    if (strlen($number) === 9) {

        return '+48 ' .
            substr($number, 0, 3) . ' ' .
            substr($number, 3, 3) . ' ' .
            substr($number, 6, 3);

    }


    return $number;
}


function sentiment_label($sentiment)
{
    switch ($sentiment) {

        case 'positive':
            return '🟢 POZYTYWNY';

        case 'negative':
            return '🔴 NEGATYWNY';

        case 'neutral':
            return '🟡 NEUTRALNY';

        default:
            return '';

    }
}


/* ============================================================
 * GENEROWANIE KSIĄŻKI CISCO
 * ============================================================ */

function generate_cisco_directory($pdo)
{

    $output_file =
        '/var/www/html/telefon/cisco/directory.xml';


    $stmt = $pdo->query("

        SELECT
            number,
            display_name,
            callerid

        FROM call_history

        WHERE cisco_directory = 1

        ORDER BY
            CASE

                WHEN display_name IS NULL
                OR display_name = ''

                THEN number

                ELSE display_name

            END

    ");


    $contacts =
        $stmt->fetchAll();


    /*
     * DOMDocument
     */

    $xml = new DOMDocument(
        '1.0',
        'UTF-8'
    );


    $xml->formatOutput = true;


    /*
     * Root
     */

    $root =
        $xml->createElement(
            'CiscoIPPhoneDirectory'
        );


    $xml->appendChild(
        $root
    );


    /*
     * Kontakty
     */

    foreach (
        $contacts
        as $contact
    ) {


        $name =
            trim(
                $contact['display_name']
                ?? ''
            );


        /*
         * Jeżeli nie ma własnej nazwy,
         * używamy CallerID.
         */

        if ($name === '') {

            $name =
                trim(
                    $contact['callerid']
                    ?? ''
                );

        }


        /*
         * Ostatecznie numer.
         */

        if ($name === '') {

            $name =
                $contact['number'];

        }


        /*
         * Numer telefonu.
         */

        $number =
            preg_replace(
                '/[^0-9+]/',
                '',
                $contact['number']
            );


        /*
         * DirectoryEntry
         */

        $entry =
            $xml->createElement(
                'DirectoryEntry'
            );


        /*
         * Name
         */

        $name_element =
            $xml->createElement(
                'Name'
            );


        $name_element->appendChild(
            $xml->createTextNode(
                $name
            )
        );


        $entry->appendChild(
            $name_element
        );


        /*
         * Telephone
         */

        $telephone =
            $xml->createElement(
                'Telephone'
            );


        $telephone->appendChild(
            $xml->createTextNode(
                $number
            )
        );


        $entry->appendChild(
            $telephone
        );


        /*
         * Dodaj wpis
         */

        $root->appendChild(
            $entry
        );

    }


    /*
     * Zapis
     */

    if (
        $xml->save(
            $output_file
        ) === false
    ) {

        return false;

    }


    return true;

}


/* ============================================================
 * ZMIENNE
 * ============================================================ */

$number =
    normalize_number(
        $_GET['number']
        ??
        $_POST['number']
        ??
        ''
    );


$message = '';

$error = '';


/* ============================================================
 * OBSŁUGA CHECKBOXA CISCO
 * ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cisco_update'])
) {

    $cisco_id =
        (int)(
            $_POST['cisco_id']
            ?? 0
        );


    $cisco_value =
        isset(
            $_POST['cisco_directory']
        )
        ? 1
        : 0;


    if ($cisco_id > 0) {

        try {

            /*
             * Sprawdzenie rekordu.
             */

            $stmt = $pdo->prepare("

                SELECT
                    number

                FROM call_history

                WHERE id = ?

                LIMIT 1

            ");


            $stmt->execute(
                array(
                    $cisco_id
                )
            );


            $record =
                $stmt->fetch();


            if ($record) {

                /*
                 * Aktualizacja checkboxa.
                 */

                $stmt = $pdo->prepare("

                    UPDATE call_history

                    SET
                        cisco_directory = ?

                    WHERE id = ?

                    LIMIT 1

                ");


                $stmt->execute(
                    array(
                        $cisco_value,
                        $cisco_id
                    )
                );


                /*
                 * Regenerujemy XML.
                 */

                if (
                    !generate_cisco_directory(
                        $pdo
                    )
                ) {

                    $error =
                        'Zapisano ustawienie, ale nie udało się wygenerować directory.xml.';

                } else {

                    $message =
                        $cisco_value
                        ? 'Numer został dodany do książki Cisco.'
                        : 'Numer został usunięty z książki Cisco.';

                }


                /*
                 * Zachowujemy numer
                 * w formularzu.
                 */

                $number =
                    normalize_number(
                        $record['number']
                    );

            } else {

                $error =
                    'Nie znaleziono numeru.';

            }


        } catch (PDOException $e) {

            $error =
                'Błąd podczas aktualizacji książki Cisco.';

        }

    }

}


/* ============================================================
 * USUWANIE NUMERU
 * ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id'])
) {

    $delete_id =
        (int)$_POST['delete_id'];


    if ($delete_id > 0) {

        try {

            /*
             * Pobieramy numer.
             */

            $stmt = $pdo->prepare("

                SELECT
                    number

                FROM call_history

                WHERE id = ?

                LIMIT 1

            ");


            $stmt->execute(
                array(
                    $delete_id
                )
            );


            $deleted =
                $stmt->fetch();


            if ($deleted) {

                /*
                 * Usuwamy cały rekord.
                 */

                $stmt = $pdo->prepare("

                    DELETE FROM call_history

                    WHERE id = ?

                    LIMIT 1

                ");


                $stmt->execute(
                    array(
                        $delete_id
                    )
                );


                /*
                 * Aktualizacja książki Cisco.
                 */

                generate_cisco_directory(
                    $pdo
                );


                header(
                    'Location: szukaj.php?deleted=1'
                );

                exit;

            } else {

                $error =
                    'Numer nie istnieje w bazie.';

            }


        } catch (PDOException $e) {

            $error =
                'Błąd podczas usuwania numeru.';

        }

    }

}


/* ============================================================
 * KOMUNIKAT PO USUNIĘCIU
 * ============================================================ */

if (
    isset($_GET['deleted']) &&
    $_GET['deleted'] === '1'
) {

    $message =
        'Numer został usunięty z bazy.';

}


/* ============================================================
 * ZAPIS / EDYCJA NUMERU
 * ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save'])
) {

    $number =
        normalize_number(
            $_POST['number']
            ?? ''
        );


    $display_name =
        trim(
            $_POST['display_name']
            ?? ''
        );


    $note =
        trim(
            $_POST['note']
            ?? ''
        );


    $sentiment =
        $_POST['sentiment']
        ?? '';


    /*
     * Dozwolone wartości.
     */

    if (
        !in_array(
            $sentiment,
            array(
                '',
                'positive',
                'neutral',
                'negative'
            ),
            true
        )
    ) {

        $sentiment = '';

    }


    /*
     * Walidacja numeru.
     */

    if (
        strlen($number) !== 9
    ) {

        $error =
            'Nieprawidłowy polski numer telefonu.';

    } else {

        try {

            /*
             * Czy numer istnieje?
             */

            $stmt = $pdo->prepare("

                SELECT
                    id

                FROM call_history

                WHERE number = ?

                LIMIT 1

            ");


            $stmt->execute(
                array(
                    $number
                )
            );


            $existing =
                $stmt->fetch();


            /*
             * AKTUALIZACJA
             */

            if ($existing) {

                $stmt = $pdo->prepare("

                    UPDATE call_history

                    SET
                        display_name = ?,
                        note = ?,
                        sentiment = ?

                    WHERE id = ?

                ");


                $stmt->execute(
                    array(

                        $display_name !== ''
                            ? $display_name
                            : null,

                        $note !== ''
                            ? $note
                            : null,

                        $sentiment !== ''
                            ? $sentiment
                            : null,

                        $existing['id']

                    )
                );


                /*
                 * Jeżeli numer jest już
                 * w książce Cisco,
                 * odświeżamy XML,
                 * aby nowa nazwa była
                 * widoczna w telefonie.
                 */

                $stmt = $pdo->prepare("

                    SELECT
                        cisco_directory

                    FROM call_history

                    WHERE id = ?

                    LIMIT 1

                ");


                $stmt->execute(
                    array(
                        $existing['id']
                    )
                );


                $cisco =
                    $stmt->fetch();


                if (
                    $cisco &&
                    (int)$cisco[
                        'cisco_directory'
                    ] === 1
                ) {

                    generate_cisco_directory(
                        $pdo
                    );

                }


                $message =
                    'Informacje o numerze zostały zapisane.';


            /*
             * NOWY NUMER
             */

            } else {

                /*
                 * Techniczny uniqueid.
                 */

                $manual_uniqueid =
                    'manual-' .
                    $number .
                    '-' .
                    time();


                $stmt = $pdo->prepare("

                    INSERT INTO call_history

                    (
                        uniqueid,
                        number,
                        callerid,
                        display_name,
                        note,
                        sentiment,
                        first_called_at,
                        last_called_at,
                        call_count,
                        cisco_directory
                    )

                    VALUES

                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW(),
                        NOW(),
                        0,
                        0
                    )

                ");


                $stmt->execute(
                    array(

                        $manual_uniqueid,

                        $number,

                        $number,

                        $display_name !== ''
                            ? $display_name
                            : null,

                        $note !== ''
                            ? $note
                            : null,

                        $sentiment !== ''
                            ? $sentiment
                            : null

                    )
                );


                $message =
                    'Numer został dodany do bazy.';

            }


        } catch (PDOException $e) {

            $error =
                'Błąd podczas zapisu danych: ' .
                $e->getMessage();

        }

    }

}


/* ============================================================
 * POBRANIE WYBRANEGO NUMERU
 * ============================================================ */

$history = null;

$phone_data = null;


if ($number !== '') {


    /*
     * Własne dane.
     */

    $stmt = $pdo->prepare("

        SELECT *

        FROM call_history

        WHERE number = ?

        ORDER BY
            call_count DESC,
            last_called_at DESC

        LIMIT 1

    ");


    $stmt->execute(
        array(
            $number
        )
    );


    $history =
        $stmt->fetch();


    /*
     * Dane OdebracTelefon.
     */

    $stmt = $pdo->prepare("

        SELECT *

        FROM odebractelefon_cache

        WHERE number = ?

        LIMIT 1

    ");


    $stmt->execute(
        array(
            $number
        )
    );


    $phone_data =
        $stmt->fetch();

}


/* ============================================================
 * HISTORIA
 * ============================================================ */

$stmt = $pdo->query("

    SELECT

        id,
        number,
        callerid,
        display_name,
        last_called_at,
        call_count,
        note,
        sentiment,
        cisco_directory

    FROM call_history

    ORDER BY

        CASE

            WHEN call_count > 0
            THEN 0

            ELSE 1

        END,

        last_called_at DESC

    LIMIT 100

");


$history_list =
    $stmt->fetchAll();


?>
<!DOCTYPE html>

<html lang="pl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Kto Dzwoni? — Szukaj
</title>


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

    gap: 10px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.4);

}


.logo {

    font-size: 24px;

    font-weight: bold;

    margin-right: 40px;

}


.navbar a {

    color: #ccc;

    text-decoration: none;

    padding: 12px 20px;

    border-radius: 8px;

}


.navbar a:hover {

    background: #333;

    color: white;

}


.navbar a.active {

    background: #333;

    color: white;

}


/* ============================================================
 * UKŁAD
 * ============================================================ */

.page {

    max-width: 1400px;

    margin: auto;

    padding: 30px;

    display: grid;

    grid-template-columns:
        320px
        1fr;

    gap: 25px;

}


.box {

    background: #202020;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.5);

}


/* ============================================================
 * HISTORIA
 * ============================================================ */

.history h2 {

    margin-top: 0;

}


.history-item {

    background: #151515;

    padding: 14px;

    border-radius: 8px;

    margin: 8px 0;

    border:
        1px solid #292929;

}


.history-item:hover {

    border-color: #444;

}


.history-link {

    display: block;

    color: white;

    text-decoration: none;

}


.history-name {

    font-size: 18px;

    font-weight: bold;

    margin-bottom: 4px;

}


.history-number {

    font-size: 16px;

    font-weight: bold;

    color: #ddd;

}


.history small {

    color: #999;

}


.history-note {

    color: #aaa;

    margin-top: 8px;

    font-size: 14px;

    line-height: 1.4;

}


.history-flag {

    margin-top: 8px;

    font-size: 13px;

    font-weight: bold;

}


/* ============================================================
 * CISCO CHECKBOX
 * ============================================================ */

.cisco-box {

    margin-top: 13px;

    padding-top: 12px;

    border-top:
        1px solid #292929;

}


.cisco-label {

    display: flex;

    align-items: center;

    gap: 9px;

    cursor: pointer;

    color: #ddd;

    font-size: 14px;

    font-weight: bold;

}


.cisco-label:hover {

    color: white;

}


.cisco-label input {

    width: 18px;

    height: 18px;

    cursor: pointer;

    accent-color: #1769aa;

}


.cisco-status {

    margin-top: 7px;

    margin-left: 27px;

    font-size: 12px;

    color: #777;

}


.cisco-status.enabled {

    color: #00e676;

}


/* ============================================================
 * USUWANIE
 * ============================================================ */

.delete-form {

    margin-top: 12px;

}


.delete-button {

    width: 100%;

    padding: 9px 12px;

    border:
        1px solid #6b2020;

    border-radius: 7px;

    background: #351515;

    color: #ff6b6b;

    cursor: pointer;

    font-weight: bold;

    font-size: 13px;

}


.delete-button:hover {

    background: #5a1c1c;

    color: white;

}


/* ============================================================
 * WYSZUKIWANIE
 * ============================================================ */

.search {

    display: flex;

    gap: 10px;

}


.search input {

    flex: 1;

    padding: 14px;

    background: #111;

    color: white;

    border:
        1px solid #444;

    border-radius: 8px;

    font-size: 18px;

}


.button {

    padding:
        14px 20px;

    border: 0;

    border-radius: 8px;

    background: #1769aa;

    color: white;

    font-weight: bold;

    cursor: pointer;

}


.button:hover {

    background: #0d527f;

}


/* ============================================================
 * FORMULARZ
 * ============================================================ */

.add-number {

    margin-top: 25px;

    padding: 20px;

    background: #151515;

    border:
        1px solid #333;

    border-radius: 10px;

}


.add-number h2 {

    margin-top: 0;

}


.field {

    margin-bottom: 18px;

}


.field label {

    display: block;

    margin-bottom: 7px;

    color: #ccc;

    font-weight: bold;

}


.field input[type="text"],
.field textarea {

    width: 100%;

    padding: 13px;

    background: #202020;

    color: white;

    border:
        1px solid #444;

    border-radius: 8px;

    font-size: 17px;

}


.field textarea {

    min-height: 100px;

    resize: vertical;

}


.options {

    display: flex;

    gap: 18px;

    flex-wrap: wrap;

}


.options label {

    color: #ddd;

    font-weight: normal;

    cursor: pointer;

}


.save-button {

    margin-top: 5px;

}


/* ============================================================
 * INFORMACJE
 * ============================================================ */

.main-name {

    margin-top: 30px;

    font-size: 42px;

    font-weight: bold;

}


.main-number {

    margin-top: 5px;

    font-size: 28px;

    color: #ccc;

}


.flag {

    display: inline-block;

    margin-top: 15px;

    padding:
        8px 14px;

    border-radius: 20px;

    font-weight: bold;

}


.flag.positive {

    background: #123b25;

    color: #00e676;

}


.flag.negative {

    background: #451919;

    color: #ff5252;

}


.flag.neutral {

    background: #453b16;

    color: #ffd740;

}


.note {

    margin-top: 20px;

    padding: 18px;

    background: #151515;

    border-left:
        4px solid #1769aa;

    border-radius: 8px;

    line-height: 1.5;

}


.note-title {

    font-weight: bold;

    margin-bottom: 6px;

}


/* ============================================================
 * ODEBRACTELEFON
 * ============================================================ */

.external {

    margin-top: 30px;

    border-top:
        1px solid #333;

    padding-top: 25px;

}


.external h2 {

    margin-top: 0;

}


.external-rating {

    font-size: 24px;

    font-weight: bold;

    margin-bottom: 10px;

}


.external-category {

    font-size: 18px;

    color: #ccc;

    margin-bottom: 20px;

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

    margin-top: 20px;

}


.stat {

    background: #151515;

    text-align: center;

    padding: 20px;

}


.stat strong {

    display: block;

    font-size: 30px;

    margin-bottom: 5px;

}


/* ============================================================
 * KOMUNIKATY
 * ============================================================ */

.success {

    background: #123b25;

    color: #00e676;

    padding: 13px;

    border-radius: 8px;

    margin-top: 20px;

}


.error {

    background: #451919;

    color: #ff5252;

    padding: 13px;

    border-radius: 8px;

    margin-top: 20px;

}


.empty {

    text-align: center;

    color: #aaa;

    padding: 50px;

    font-size: 20px;

}


/* ============================================================
 * MOBILE
 * ============================================================ */

@media (max-width: 900px) {

    .page {

        grid-template-columns: 1fr;

    }

}


@media (max-width: 600px) {

    .page {

        padding: 15px;

    }


    .navbar {

        padding: 0 15px;

    }


    .logo {

        font-size: 19px;

        margin-right: 10px;

    }


    .navbar a {

        padding: 10px;

    }


    .search {

        flex-direction: column;

    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .main-name {

        font-size: 32px;

    }


    .main-number {

        font-size: 24px;

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


    <a href="index.php">

        Monitor

    </a>


    <a
        href="szukaj.php"
        class="active"
    >

        Szukaj

    </a>

</nav>



<div class="page">


<!-- ==========================================================
     LEWA KOLUMNA
     ========================================================== -->

<aside class="box history">

    <h2>

        Ostatnie numery

    </h2>


    <?php if ($history_list): ?>


        <?php foreach (
            $history_list
            as $item
        ): ?>


            <div class="history-item">


                <a
                    class="history-link"
                    href="?number=<?= urlencode(
                        $item['number']
                    ) ?>"
                >


                    <?php if (
                        !empty(
                            $item['display_name']
                        )
                    ): ?>

                        <div class="history-name">

                            <?= h(
                                $item['display_name']
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <div class="history-number">

                        <?= h(
                            format_number(
                                $item['number']
                            )
                        ) ?>

                    </div>


                    <?php if (
                        !empty(
                            $item['callerid']
                        )
                    ): ?>

                        <small>

                            <?= h(
                                $item['callerid']
                            ) ?>

                        </small>

                    <?php endif; ?>


                    <br>


                    <small>

                        <?php

                        if (
                            (int)$item[
                                'call_count'
                            ] > 0
                        ) {

                            echo h(
                                $item[
                                    'last_called_at'
                                ]
                            );

                            echo ' · ';

                            echo h(
                                $item[
                                    'call_count'
                                ]
                            );

                            echo ' połączeń';

                        } else {

                            echo 'Dodany ręcznie';

                        }

                        ?>

                    </small>


                    <?php if (
                        !empty(
                            $item['sentiment']
                        )
                    ): ?>

                        <div
                            class="history-flag"
                        >

                            <?= h(
                                sentiment_label(
                                    $item[
                                        'sentiment'
                                    ]
                                )
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $item['note']
                        )
                    ): ?>

                        <div
                            class="history-note"
                        >

                            <?= h(
                                $item['note']
                            ) ?>

                        </div>

                    <?php endif; ?>


                </a>


                <!-- ==========================================
                     CISCO
                     ========================================== -->

                <div class="cisco-box">


                    <form
                        method="post"
                        class="cisco-form"
                    >

                        <input
                            type="hidden"
                            name="cisco_update"
                            value="1"
                        >


                        <input
                            type="hidden"
                            name="cisco_id"
                            value="<?= (int)$item['id'] ?>"
                        >


                        <label
                            class="cisco-label"
                        >


                            <input
                                type="checkbox"
                                name="cisco_directory"
                                value="1"

                                <?= (
                                    (int)$item[
                                        'cisco_directory'
                                    ] === 1
                                )
                                    ? 'checked'
                                    : ''
                                ?>

                                onchange="
                                    this.form.submit();
                                "
                            >


                            Dodaj do książki Cisco


                        </label>


                        <?php if (
                            (int)$item[
                                'cisco_directory'
                            ] === 1
                        ): ?>


                            <div
                                class="
                                    cisco-status
                                    enabled
                                "
                            >

                                ✓ Numer jest w książce Cisco

                            </div>


                        <?php else: ?>


                            <div
                                class="cisco-status"
                            >

                                Numer nie jest w książce

                            </div>


                        <?php endif; ?>


                    </form>


                </div>


                <!-- ==========================================
                     USUWANIE
                     ========================================== -->

                <form
                    method="post"
                    class="delete-form"

                    onsubmit="
                        return confirm(
                            'Czy na pewno chcesz usunąć numer <?= h(
                                format_number(
                                    $item['number']
                                )
                            ) ?> z bazy?'
                        );
                    "
                >

                    <input
                        type="hidden"
                        name="delete_id"
                        value="<?= (int)$item['id'] ?>"
                    >


                    <button
                        type="submit"
                        class="delete-button"
                    >

                        🗑 USUŃ NUMER

                    </button>

                </form>


            </div>


        <?php endforeach; ?>


    <?php else: ?>


        <div class="empty">

            Brak numerów.

        </div>


    <?php endif; ?>


</aside>



<!-- ==========================================================
     PRAWA KOLUMNA
     ========================================================== -->

<main class="box">


    <!-- ======================================================
         WYSZUKIWANIE
         ====================================================== -->

    <form
        class="search"
        method="get"
    >

        <input
            type="text"
            name="number"
            value="<?= h($number) ?>"
            placeholder="+48 668 190 504"
            autocomplete="off"
        >


        <button
            class="button"
            type="submit"
        >

            SZUKAJ

        </button>

    </form>



    <?php if ($message): ?>

        <div class="success">

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">

            <?= h($error) ?>

        </div>

    <?php endif; ?>



    <!-- ======================================================
         DODAWANIE / EDYCJA
         ====================================================== -->

    <div class="add-number">

        <h2>

            <?php if ($history): ?>

                Edytuj informacje o numerze

            <?php else: ?>

                Dodaj własny numer

            <?php endif; ?>

        </h2>


        <form method="post">


            <div class="field">

                <label>

                    Numer telefonu

                </label>


                <input
                    type="text"
                    name="number"
                    value="<?= h($number) ?>"
                    placeholder="+48 668 190 504"
                    required
                >

            </div>



            <div class="field">

                <label>

                    Nazwa numeru

                </label>


                <input
                    type="text"
                    name="display_name"
                    value="<?= h(
                        $history[
                            'display_name'
                        ] ?? ''
                    ) ?>"
                    placeholder="np. Jan Kowalski / Firma ABC"
                >

            </div>



            <div class="field">

                <label>

                    Własna informacja

                </label>


                <textarea
                    name="note"
                    placeholder="Np. klient, znajomy, serwis, telemarketing..."
                ><?= h(
                    $history[
                        'note'
                    ] ?? ''
                ) ?></textarea>

            </div>



            <div class="field">

                <label>

                    Klasyfikacja numeru

                </label>


                <div class="options">


                    <label>

                        <input
                            type="radio"
                            name="sentiment"
                            value=""

                            <?= (
                                !$history ||
                                empty(
                                    $history[
                                        'sentiment'
                                    ]
                                )
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        Brak

                    </label>


                    <label>

                        <input
                            type="radio"
                            name="sentiment"
                            value="positive"

                            <?= (
                                $history &&
                                $history[
                                    'sentiment'
                                ] === 'positive'
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        🟢 Pozytywny

                    </label>


                    <label>

                        <input
                            type="radio"
                            name="sentiment"
                            value="neutral"

                            <?= (
                                $history &&
                                $history[
                                    'sentiment'
                                ] === 'neutral'
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        🟡 Neutralny

                    </label>


                    <label>

                        <input
                            type="radio"
                            name="sentiment"
                            value="negative"

                            <?= (
                                $history &&
                                $history[
                                    'sentiment'
                                ] === 'negative'
                            )
                                ? 'checked'
                                : ''
                            ?>
                        >

                        🔴 Negatywny

                    </label>


                </div>

            </div>



            <button
                class="button save-button"
                type="submit"
                name="save"
                value="1"
            >

                <?php if ($history): ?>

                    ZAPISZ ZMIANY

                <?php else: ?>

                    DODAJ NUMER

                <?php endif; ?>

            </button>


        </form>

    </div>



    <?php if ($number !== ''): ?>


        <?php if (
            $history ||
            $phone_data
        ): ?>


            <?php if (
                $history &&
                !empty(
                    $history[
                        'display_name'
                    ]
                )
            ): ?>

                <div class="main-name">

                    <?= h(
                        $history[
                            'display_name'
                        ]
                    ) ?>

                </div>

            <?php endif; ?>


            <div class="main-number">

                <?= h(
                    format_number(
                        $number
                    )
                ) ?>

            </div>



            <?php if (
                $history &&
                !empty(
                    $history[
                        'sentiment'
                    ]
                )
            ): ?>


                <div
                    class="
                        flag
                        <?= h(
                            $history[
                                'sentiment'
                            ]
                        ) ?>
                    "
                >

                    <?= h(
                        sentiment_label(
                            $history[
                                'sentiment'
                            ]
                        )
                    ) ?>

                </div>


            <?php endif; ?>



            <?php if (
                $history &&
                !empty(
                    $history['note']
                )
            ): ?>


                <div class="note">

                    <div class="note-title">

                        WŁASNA INFORMACJA

                    </div>


                    <?= nl2br(
                        h(
                            $history[
                                'note'
                            ]
                        )
                    ) ?>

                </div>


            <?php endif; ?>



            <?php if ($phone_data): ?>


                <div class="external">

                    <h2>

                        Dane z OdebracTelefon.pl

                    </h2>


                    <?php if (
                        !empty(
                            $phone_data[
                                'rating'
                            ]
                        )
                    ): ?>

                        <div class="external-rating">

                            <?= h(
                                $phone_data[
                                    'rating'
                                ]
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $phone_data[
                                'main_category'
                            ]
                        )
                    ): ?>

                        <div class="external-category">

                            Kategoria:

                            <strong>

                                <?= h(
                                    $phone_data[
                                        'main_category'
                                    ]
                                ) ?>

                            </strong>

                        </div>

                    <?php endif; ?>


                    <div class="stats">


                        <div class="stat">

                            <strong>

                                <?= h(
                                    $phone_data[
                                        'positive'
                                    ]
                                ) ?>

                            </strong>

                            Pozytywne

                        </div>


                        <div class="stat">

                            <strong>

                                <?= h(
                                    $phone_data[
                                        'negative'
                                    ]
                                ) ?>

                            </strong>

                            Negatywne

                        </div>


                        <div class="stat">

                            <strong>

                                <?= h(
                                    $phone_data[
                                        'neutral'
                                    ]
                                ) ?>

                            </strong>

                            Neutralne

                        </div>


                        <div class="stat">

                            <strong>

                                <?= h(
                                    $phone_data[
                                        'total'
                                    ]
                                ) ?>

                            </strong>

                            Wszystkie

                        </div>


                    </div>



                    <?php if (
                        !empty(
                            $phone_data[
                                'categories'
                            ]
                        )
                    ): ?>


                        <div class="note">

                            <div class="note-title">

                                Kategorie

                            </div>


                            <?= nl2br(
                                h(
                                    $phone_data[
                                        'categories'
                                    ]
                                )
                            ) ?>

                        </div>


                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $phone_data[
                                'checked_at'
                            ]
                        )
                    ): ?>


                        <div
                            style="
                                margin-top:15px;
                                color:#888;
                                font-size:13px;
                            "
                        >

                            Ostatnie sprawdzenie:

                            <?= h(
                                $phone_data[
                                    'checked_at'
                                ]
                            ) ?>

                        </div>


                    <?php endif; ?>


                </div>


            <?php else: ?>


                <div class="empty">

                    Brak danych tego numeru
                    w bazie OdebracTelefon.pl

                </div>


            <?php endif; ?>


        <?php endif; ?>


    <?php endif; ?>


</main>

</div>

</body>

</html>
