# ☎ Kto Dzwoni?

**Kto Dzwoni?** to lokalny panel identyfikacji numerów telefonicznych zintegrowany z **Asterisk / FreePBX**, lokalną bazą MySQL/MariaDB oraz bazą danych `OdebracTelefon`.

System pozwala rozpoznać numer przychodzący jeszcze podczas dzwonienia, zaprezentować własne informacje zapisane dla numeru, dane z lokalnego cache `OdebracTelefon`, historię połączeń oraz klasyfikację numeru.

Dodatkowo wybrane numery mogą być automatycznie udostępniane w książce telefonicznej **Cisco** w formacie `CiscoIPPhoneDirectory`.

---

## Spis treści

- [Najważniejsze funkcje](#najważniejsze-funkcje)
- [Jak działa system](#jak-działa-system)
- [Monitor aktywnego połączenia](#monitor-aktywnego-połączenia)
- [Wyszukiwanie numerów](#wyszukiwanie-numerów)
- [Ręczne dodawanie numerów](#ręczne-dodawanie-numerów)
- [Własne informacje o numerze](#własne-informacje-o-numerze)
- [Klasyfikacja numerów](#klasyfikacja-numerów)
- [Historia połączeń](#historia-połączeń)
- [Integracja z OdebracTelefon](#integracja-z-odebractelefon)
- [Książka telefoniczna Cisco](#książka-telefoniczna-cisco)
- [Integracja z Asterisk i FreePBX](#integracja-z-asterisk-i-freepbx)
- [Struktura katalogów](#struktura-katalogów)
- [Opis plików](#opis-plików)
- [Baza danych](#baza-danych)
- [Przepływ połączenia](#przepływ-połączenia)
- [Wymagania](#wymagania)
- [Instalacja](#instalacja)
- [Konfiguracja](#konfiguracja)
- [Uprawnienia](#uprawnienia)
- [Konfiguracja książki Cisco](#konfiguracja-książki-cisco)
- [Diagnostyka](#diagnostyka)
- [Bezpieczeństwo](#bezpieczeństwo)
- [Uwagi dotyczące wdrożenia](#uwagi-dotyczące-wdrożenia)

---

# Najważniejsze funkcje

Projekt posiada następujące funkcje:

- identyfikacja przychodzącego numeru,
- monitorowanie aktywnego połączenia,
- automatyczne odświeżanie informacji o połączeniu,
- prezentacja numeru telefonu,
- prezentacja `CallerID`,
- prezentacja własnej nazwy numeru,
- prezentacja własnej notatki/informacji,
- klasyfikacja numeru:
  - pozytywny,
  - neutralny,
  - negatywny,
- historia połączeń,
- licznik połączeń,
- data pierwszego połączenia,
- data ostatniego połączenia,
- ręczne dodawanie numerów,
- edycja numerów,
- usuwanie numerów,
- wyszukiwanie numerów,
- normalizacja polskich numerów telefonicznych,
- pobieranie informacji z `odebractelefon_cache`,
- prezentacja danych z OdebracTelefon podczas aktywnego połączenia,
- zachowanie własnych informacji zapisanych dla numeru,
- możliwość oznaczenia numeru do książki Cisco,
- automatyczne generowanie `directory.xml`,
- możliwość usunięcia numeru z książki Cisco przez odznaczenie checkboxa,
- integracja z Asterisk AGI,
- automatyczne czyszczenie tabeli aktywnych połączeń po zakończeniu rozmowy.

---

# Jak działa system

Ogólny przepływ wygląda następująco:

```text
                 POŁĄCZENIE PRZYCHODZĄCE
                           │
                           ▼
                    ┌──────────────┐
                    │   Asterisk   │
                    │   FreePBX    │
                    └──────┬───────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │  live-call.php  │
                  │      AGI        │
                  └────────┬────────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
       ┌──────────────┐          ┌──────────────┐
       │  live_calls  │          │ call_history │
       │ aktywne call │          │   historia   │
       └──────┬───────┘          └──────┬───────┘
              │                         │
              └────────────┬────────────┘
                           ▼
                    ┌──────────────┐
                    │ MySQL/MariaDB│
                    │   asterisk   │
                    └──────┬───────┘
                           │
                 ┌─────────┴─────────┐
                 ▼                   ▼
          ┌────────────┐      ┌─────────────┐
          │  api.php   │      │ szukaj.php  │
          │    API     │      │   panel     │
          └─────┬──────┘      └─────────────┘
                │
                ▼
          ┌────────────┐
          │ index.php  │
          │  MONITOR   │
          └────────────┘
```

Po zakończeniu rozmowy:

```text
Asterisk
   │
   ▼
live-call-hangup.php
   │
   ▼
DELETE FROM live_calls
```

Historia numeru pozostaje w `call_history`.

---

# Monitor aktywnego połączenia

Plik:

```text
telefon/index.php
```

jest głównym ekranem monitoringu.

Panel cyklicznie odpytuje:

```text
telefon/api.php
```

Jeżeli nie ma aktywnego połączenia:

```text
BRAK AKTYWNEGO POŁĄCZENIA

Oczekiwanie na połączenie...
```

Jeżeli pojawi się połączenie, panel prezentuje między innymi:

```text
🔔 POŁĄCZENIE PRZYCHODZĄCE

Jan Kowalski
+48 668 190 504

🟢 POZYTYWNY
```

oraz własną informację i dane z `OdebracTelefon`.

Panel posiada również licznik czasu aktywnego połączenia.

---

# Wyszukiwanie numerów

Plik:

```text
telefon/szukaj.php
```

jest głównym panelem zarządzania numerami.

Pozwala:

- wyszukać numer,
- zobaczyć historię numerów,
- wyświetlić informacje własne,
- zobaczyć dane z `OdebracTelefon`,
- edytować numer,
- usunąć numer,
- przypisać nazwę,
- zapisać notatkę,
- przypisać klasyfikację,
- dodać numer do książki Cisco,
- usunąć numer z książki Cisco.

---

# Ręczne dodawanie numerów

Numer nie musi wcześniej dzwonić.

Można ręcznie dodać np.:

```text
668190504
```

i przypisać:

```text
Nazwa:
Jan Kowalski

Informacja:
Klient — kontakt służbowy.

Klasyfikacja:
Pozytywny
```

Po zapisaniu numer znajduje się w:

```text
call_history
```

Jeżeli później ten numer zadzwoni:

1. Asterisk wykrywa numer,
2. `live-call.php` rozpoznaje istniejący wpis,
3. historia zostaje zaktualizowana,
4. własna nazwa pozostaje,
5. własna informacja pozostaje,
6. klasyfikacja pozostaje,
7. dane z `OdebracTelefon` zostają dołączone,
8. `index.php` prezentuje kompletny profil numeru.

---

# Własne informacje o numerze

Każdy numer może mieć własną nazwę:

```text
display_name
```

oraz własną informację:

```text
note
```

Przykład:

```text
Nazwa:
Serwis XYZ

Informacja:
Technik — kontakt tylko w godzinach pracy.
```

Te informacje są przechowywane lokalnie.

Są prezentowane zarówno w:

```text
szukaj.php
```

jak i podczas aktywnego połączenia w:

```text
index.php
```

---

# Klasyfikacja numerów

Numer może zostać oznaczony jako:

| Wartość w bazie | Prezentacja |
|---|---|
| `positive` | 🟢 POZYTYWNY |
| `neutral` | 🟡 NEUTRALNY |
| `negative` | 🔴 NEGATYWNY |

Klasyfikacja jest przechowywana w:

```text
call_history.sentiment
```

Jeżeli numer zadzwoni, klasyfikacja jest pobierana z lokalnej historii i prezentowana na monitorze.

---

# Historia połączeń

Tabela:

```text
call_history
```

przechowuje historię numerów.

Dla każdego numeru można przechowywać:

- numer telefonu,
- CallerID,
- nazwę,
- własną informację,
- klasyfikację,
- liczbę połączeń,
- pierwsze połączenie,
- ostatnie połączenie,
- informację o książce Cisco.

Przykład:

```text
Jan Kowalski
+48 668 190 504

Połączenia: 7
Ostatnie: 2026-08-23 19:30:17

🟢 POZYTYWNY
```

Historia jest niezależna od tabeli `live_calls`.

`live_calls` pokazuje stan bieżący.

`call_history` przechowuje historię.

---

# Integracja z OdebracTelefon

Projekt korzysta z lokalnej tabeli:

```text
odebractelefon_cache
```

Dane są wyszukiwane po znormalizowanym numerze.

Przykładowe informacje:

```text
rating
main_category
positive
negative
neutral
total
categories
checked_at
```

Na monitorze mogą być prezentowane razem z własnymi informacjami.

Przykładowo:

```text
Jan Kowalski
+48 668 190 504

🟢 POZYTYWNY

WŁASNA INFORMACJA
Klient

DANE Z ODEBRACTELEFON.PL
Pozytywna

Pozytywne: 12
Negatywne: 2
Neutralne: 3
Wszystkie: 17
```

Dane `OdebracTelefon` są traktowane jako dodatkowe informacje. Własna nazwa, notatka i klasyfikacja są przechowywane lokalnie w `call_history`.

---

# Książka telefoniczna Cisco

System pozwala wybrać, które numery mają być dostępne w książce telefonu Cisco.

W historii numeru znajduje się checkbox:

```text
☑ Dodaj do książki Cisco
```

Po zaznaczeniu:

```text
cisco_directory = 1
```

Po odznaczeniu:

```text
cisco_directory = 0
```

Następnie katalog jest generowany ponownie.

Plik:

```text
telefon/cisco/directory.xml
```

jest generowany na podstawie numerów posiadających:

```text
cisco_directory = 1
```

Przykład:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<CiscoIPPhoneDirectory>
    <DirectoryEntry>
        <Name>Jan Kowalski</Name>
        <Telephone>6xxXXXxxx</Telephone>
    </DirectoryEntry>
</CiscoIPPhoneDirectory>
```

---

# Integracja z Asterisk i FreePBX

Integracja wykorzystuje AGI.

Główny skrypt:

```text
telefon/live-call.php
```

pobiera informacje z Asterisk:

```text
agi_channel
agi_uniqueid
agi_linkedid
CALLERID(num)
CALLERID(name)
```

Numer jest normalizowany.

Przykłady:

```text
xxxXXXxxx
+48 xxx XXX xxx
48xxxXXXxxx
0048xxxXXXxxx
```

są sprowadzane do:

```text
xxxXXXxxx
```

Dzięki temu system może odnaleźć istniejący wpis niezależnie od sposobu prezentowania numeru przez operatora lub urządzenie.

---

# Struktura katalogów

```text
kto-dzwoni-github/
│
├── README.md
│
├── telefon/
│   ├── .gitignore
│   ├── api.php
│   ├── config.php.example
│   ├── index.php
│   ├── live-call.php
│   ├── live-call-hangup.php
│   ├── szukaj.php
│   │
│   └── cisco/
│       ├── directory.php
│       └── directory.xml
│
├── asterisk/
│   └── extensions_custom.conf.example
│
└── sql/
    └── install.sql
```

---

# Opis plików

## `README.md`

Dokumentacja projektu.

---

## `telefon/config.php.example`

Szablon konfiguracji bazy danych.

Przykład:

```php
<?php

return array(
    'db_host' => 'localhost',
    'db_name' => 'asterisk',
    'db_user' => 'freepbxuser',
    'db_pass' => 'CHANGE_ME',
);
```

Na serwerze należy utworzyć:

```text
config.php
```

na podstawie:

```text
config.php.example
```

---

## `telefon/api.php`

API dla panelu `index.php`.

Pobiera:

- aktywne połączenie,
- numer,
- CallerID,
- własną nazwę,
- własną notatkę,
- klasyfikację,
- dane z `odebractelefon_cache`.

Zwraca dane JSON.

Przykład:

```json
{
    "active": true,
    "number": "668190504",
    "display_name": "Jan Kowalski",
    "sentiment": "positive"
}
```

---

## `telefon/index.php`

Monitor aktywnego połączenia.

Odpowiada za interfejs operatora.

---

## `telefon/szukaj.php`

Panel wyszukiwania i zarządzania numerami.

Obsługuje:

- wyszukiwanie,
- dodawanie,
- edycję,
- usuwanie,
- klasyfikację,
- notatki,
- nazwy,
- książkę Cisco.

---

## `telefon/live-call.php`

Skrypt AGI wykonywany przez Asterisk podczas rozpoczęcia połączenia.

Odpowiada za:

1. odczyt CallerID,
2. normalizację numeru,
3. zapis/aktualizację `call_history`,
4. zapis `live_calls`.

Jeżeli numer był wcześniej dodany ręcznie, jego dane własne są zachowywane.

---

## `telefon/live-call-hangup.php`

Skrypt AGI wykonywany przy zakończeniu połączenia.

Usuwa rekord z:

```text
live_calls
```

Historia pozostaje w:

```text
call_history
```

---

## `telefon/cisco/directory.php`

Generator książki telefonicznej Cisco.

Pobiera wpisy:

```sql
WHERE cisco_directory = 1
```

i generuje:

```text
directory.xml
```

---

## `telefon/cisco/directory.xml`

XML udostępniany telefonowi Cisco.

---

## `asterisk/extensions_custom.conf.example`

Przykładowa konfiguracja kontekstów AGI:

```text
[live-call]
[live-call-hangup]
```

Konfigurację należy dopasować do istniejącego dialplanu FreePBX.

---

## `sql/install.sql`

Skrypt tworzący tabele wymagane przez projekt.

---

# Baza danych

Projekt korzysta z bazy:

```text
asterisk
```

## `live_calls`

Tabela przechowująca aktywne połączenia.

Struktura:

| Pole | Znaczenie |
|---|---|
| `uniqueid` | ID kanału Asterisk |
| `linkedid` | ID połączenia |
| `channel` | kanał Asterisk |
| `number` | numer telefonu |
| `callerid` | CallerID |
| `started_at` | rozpoczęcie połączenia |

Przykład:

```text
1787506216.296
PJSIP/goip1-00000052
xxxXXXxxx
48xxxXXXxxx
2026-08-23 19:30:17
```

Po zakończeniu rozmowy rekord jest usuwany.

---

## `call_history`

Tabela lokalnej historii numerów.

Aktualnie wykorzystywane pola:

| Pole | Znaczenie |
|---|---|
| `id` | ID rekordu |
| `uniqueid` | ostatni/aktualny identyfikator połączenia |
| `number` | numer telefonu |
| `callerid` | CallerID |
| `display_name` | własna nazwa |
| `note` | własna informacja |
| `sentiment` | klasyfikacja |
| `cisco_directory` | obecność w książce Cisco |
| `first_called_at` | pierwsze połączenie |
| `last_called_at` | ostatnie połączenie |
| `call_count` | liczba połączeń |

Kluczowa zasada:

```text
number = jeden numer = jeden rekord historii
```

Kolejne połączenia zwiększają:

```text
call_count
```

oraz aktualizują:

```text
last_called_at
```

---

## `odebractelefon_cache`

Tabela zawierająca lokalnie zapisane dane dotyczące numerów.

Projekt odczytuje z niej informacje związane z:

```text
rating
main_category
positive
negative
neutral
total
categories
checked_at
```

---

# Przepływ połączenia

## 1. Dzwoni numer

Przykład:

```text
+48 xxx XXX xxx
```

---

## 2. Asterisk otrzymuje CallerID

```text
CALLERID(num)
CALLERID(name)
```

---

## 3. Uruchamiany jest AGI

```text
live-call.php
```

---

## 4. Numer zostaje znormalizowany

```text
+48 668 XXX xxx
        ↓
668XXXxxx
```

---

## 5. System sprawdza `call_history`

Jeżeli numer istnieje:

```text
UPDATE call_history
```

Jeżeli nie istnieje:

```text
INSERT INTO call_history
```

---

## 6. System tworzy aktywne połączenie

```text
live_calls
```

---

## 7. `index.php` odpytuje `api.php`

Przeglądarka wykonuje zapytanie do:

```text
api.php
```

co około sekundę.

---

## 8. API pobiera dane

Łączone są:

```text
live_calls
+
call_history
+
odebractelefon_cache
```

---

## 9. Monitor pokazuje komplet informacji

Przykład:

```text
🔔 POŁĄCZENIE PRZYCHODZĄCE

Jan Kowalski
+48 668 XXX xxx

🟢 POZYTYWNY

WŁASNA INFORMACJA
Klient

DANE Z ODEBRACTELEFON.PL
...
```

---

## 10. Połączenie zostaje zakończone

Asterisk uruchamia:

```text
live-call-hangup.php
```

---

## 11. Rekord aktywnego połączenia zostaje usunięty

```sql
DELETE FROM live_calls
```

Historia pozostaje.

---

# Wymagania

Zalecane środowisko:

```text
Debian Linux
FreePBX
Asterisk
Apache lub Nginx
PHP
MariaDB / MySQL
```

PHP powinien posiadać:

```text
PDO
PDO_MySQL
DOM
```

Asterisk musi obsługiwać AGI.

---

# Instalacja

## 1. Umieszczenie projektu

Przykład:

```bash
cd /var/www/html
git clone https://github.com/USER/kto-dzwoni.git telefon
```

lub skopiowanie katalogu ręcznie.

---

## 2. Konfiguracja bazy

```bash
cd /var/www/html/telefon

cp config.php.example config.php

nano config.php
```

Uzupełnij dane:

```php
return array(
    'db_host' => 'localhost',
    'db_name' => 'asterisk',
    'db_user' => 'freepbxuser',
    'db_pass' => 'HASLO',
);
```

---

## 3. Utworzenie tabel

```bash
mysql asterisk < sql/install.sql
```

---

## 4. Konfiguracja AGI

Sprawdź, czy skrypty są wykonywalne:

```bash
chmod +x /var/www/html/telefon/live-call.php
chmod +x /var/www/html/telefon/live-call-hangup.php
```

---

## 5. Przeładowanie Asterisk

```bash
asterisk -rx "dialplan reload"
```

---

# Uprawnienia

Przykładowa konfiguracja:

```bash
chown -R asterisk:asterisk /var/www/html/telefon
```

Plik konfiguracji:

```bash
chmod 640 /var/www/html/telefon/config.php
```

Katalog Cisco:

```bash
chmod 775 /var/www/html/telefon/cisco
```

Plik XML:

```bash
chmod 664 /var/www/html/telefon/cisco/directory.xml
```

W zależności od konfiguracji Apache/PHP-FPM użytkownik wykonujący PHP może być inny niż `asterisk`.

Najważniejsze jest zapewnienie możliwości:

- odczytu `config.php`,
- zapisu `directory.xml`,
- zapisu logu Asterisk,
- połączenia PHP z MySQL/MariaDB.

---

# Konfiguracja książki Cisco

Po wygenerowaniu XML plik jest dostępny pod:

```text
http://ADRES_SERWERA/telefon/cisco/directory.xml
```

Przykład:

```text
http://192.168.1.10/telefon/cisco/directory.xml
```

Telefon Cisco musi mieć możliwość pobrania tego adresu HTTP.

Dokładna nazwa ustawienia zależy od modelu telefonu i firmware.

Format katalogu:

```xml
<CiscoIPPhoneDirectory>
    <DirectoryEntry>
        <Name>Jan Kowalski</Name>
        <Telephone>668XXXxxx</Telephone>
    </DirectoryEntry>
</CiscoIPPhoneDirectory>
```

---

# Diagnostyka

## Sprawdzenie aktywnych połączeń

```bash
mysql asterisk -e "
SELECT
    uniqueid,
    channel,
    number,
    callerid,
    started_at
FROM live_calls;
"
```

---

## Sprawdzenie historii

```bash
mysql asterisk -e "
SELECT
    id,
    uniqueid,
    number,
    callerid,
    display_name,
    sentiment,
    first_called_at,
    last_called_at,
    call_count
FROM call_history
ORDER BY last_called_at DESC;
"
```

---

## Sprawdzenie numerów w książce Cisco

```bash
mysql asterisk -e "
SELECT
    number,
    display_name,
    cisco_directory
FROM call_history
WHERE cisco_directory = 1;
"
```

---

## Sprawdzenie API

```bash
curl -s http://127.0.0.1/telefon/api.php
```

Brak aktywnego połączenia:

```json
{"active":false}
```

Aktywne połączenie powinno zwrócić:

```json
{
    "active": true,
    "number": "668XXXxxx"
}
```

---

## Log AGI

```bash
tail -f /var/log/asterisk/live-call.log
```

---

## Log Asterisk

```bash
tail -f /var/log/asterisk/full
```

---

## Konsola Asterisk

```bash
asterisk -rvvvvv
```

---

## Sprawdzenie dialplanu

```bash
asterisk -rx "dialplan show live-call"
```

oraz:

```bash
asterisk -rx "dialplan show live-call-hangup"
```

---

# Bezpieczeństwo

## `config.php`

Rzeczywisty plik:

```text
config.php
```

zawiera dane dostępowe do bazy.

Nie powinien być umieszczany w publicznym GitHub.

W repozytorium powinien znajdować się tylko:

```text
config.php.example
```

---

## `.gitignore`

Minimalnie:

```text
config.php
*.log
.DS_Store
```

---

## Dane numerów

`call_history` może zawierać:

- numery telefonów,
- nazwiska,
- nazwy firm,
- notatki,
- informacje o kontaktach.

Jeżeli projekt jest publikowany publicznie, nie należy umieszczać w repozytorium rzeczywistych danych użytkowników.

---

## Dostęp do panelu

Panel WWW nie powinien być wystawiany bezpośrednio do publicznego Internetu bez dodatkowego zabezpieczenia.

Zalecane rozwiązania:

```text
HTTPS
VPN
firewall
Basic Authentication
reverse proxy
ograniczenie adresów IP
```

---

# Uwagi dotyczące wdrożenia

Projekt jest przeznaczony przede wszystkim do pracy jako lokalny system na serwerze FreePBX/Asterisk.

Przed wdrożeniem na produkcji należy sprawdzić:

- uprawnienia PHP,
- użytkownika wykonującego AGI,
- dostęp PHP do MySQL,
- możliwość zapisu `directory.xml`,
- dostęp telefonu Cisco do HTTP,
- poprawność kontekstu dialplanu,
- poprawność `CALLERID(num)`,
- działanie `live-call-hangup.php`.

Po zmianach w dialplanie:

```bash
asterisk -rx "dialplan reload"
```

Po zmianach w PHP nie jest zazwyczaj wymagane przeładowanie dialplanu, ale przy PHP-FPM może być wymagane przeładowanie odpowiedniej usługi, zależnie od konfiguracji.

---

# Licencja

Projekt może zostać opublikowany na GitHub z wybraną przez autora licencją.

Przykładowe licencje:

```text
MIT
GPL-3.0
Apache-2.0
```

Przed publikacją należy dodać odpowiedni plik:

```text
LICENSE
```

zgodny z wybraną licencją.

---

# Podsumowanie

**Kto Dzwoni?** tworzy lokalną warstwę identyfikacji połączeń nad Asterisk/FreePBX.

Najważniejsze elementy:

```text
Asterisk
   ↓
live-call.php
   ↓
MySQL
   ├── live_calls
   ├── call_history
   └── odebractelefon_cache
          ↓
       api.php
          ↓
       index.php
```

oraz:

```text
call_history
      ↓
cisco_directory = 1
      ↓
directory.php
      ↓
directory.xml
      ↓
telefon Cisco
```

Dzięki temu jeden numer może mieć jednocześnie:

```text
własną nazwę
+
własną informację
+
własną klasyfikację
+
historię połączeń
+
dane OdebracTelefon
+
wpis w książce Cisco
```

i wszystkie te informacje mogą zostać zaprezentowane operatorowi w momencie nadejścia połączenia.
