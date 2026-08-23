# Kto Dzwoni?

Panel webowy do monitorowania połączeń przychodzących przez FreePBX/Asterisk.

## Funkcje

- live monitoring aktywnego połączenia
- animowany dzwonek przy połączeniu
- numer telefonu i Caller ID
- historia połączeń
- własna nazwa numeru
- własna informacja/notatka
- flaga: pozytywny / neutralny / negatywny
- boczna lista ostatnich numerów
- automatyczne odświeżanie monitora co sekundę

## Struktura

```text
telefon/
├── api.php
├── config.php
├── index.php
├── szukaj.php
├── live-call.php
├── live-call-hangup.php
├── schema.sql
├── extensions_custom.conf.example
├── extensions_override_freepbx.conf.example
└── README.md
```

## Instalacja

Skopiuj pliki do:

```bash
/var/www/html/telefon/
```

Uzupełnij `config.php`:

```php
return array(
    'db_host' => 'localhost',
    'db_name' => 'asterisk',
    'db_user' => 'freepbxuser',
    'db_pass' => 'HASLO',
);
```

## Baza

Jeżeli tabele jeszcze nie istnieją:

```bash
mysql asterisk < schema.sql
```

Jeżeli `call_history` już istnieje, a brakuje kolumn:

```sql
ALTER TABLE call_history
ADD COLUMN display_name VARCHAR(255) NULL AFTER callerid;

ALTER TABLE call_history
ADD COLUMN note TEXT NULL AFTER call_count;

ALTER TABLE call_history
ADD COLUMN sentiment ENUM('positive','neutral','negative') NULL AFTER note;
```

## Uprawnienia

```bash
chown -R asterisk:asterisk /var/www/html/telefon
chmod 755 /var/www/html/telefon
chmod 640 /var/www/html/telefon/config.php
chmod +x /var/www/html/telefon/live-call.php
chmod +x /var/www/html/telefon/live-call-hangup.php
```

## Asterisk

Do `extensions_custom.conf` dodaj sekcje z:

```text
extensions_custom.conf.example
```

Po zmianie:

```bash
asterisk -rx "dialplan reload"
```

Sprawdź:

```bash
asterisk -rx "dialplan show live-call"
asterisk -rx "dialplan show live-call-hangup"
```

## Hook po Superfecta

W aktualnej konfiguracji FreePBX hook powinien być wykonany po:

```text
Set(CALLERID(name)=${lookupcid})
```

Nie należy edytować ręcznie `extensions_additional.conf`.

Jeżeli używany jest:

```text
extensions_override_freepbx.conf
```

należy tam umieścić odpowiedni override dla `ext-did-0001`.

## Log

```bash
tail -f /var/log/asterisk/live-call.log
```

## Test API

```bash
curl -s http://127.0.0.1/telefon/api.php
```

Przy aktywnym połączeniu API powinno zwrócić:

```json
{
  "active": true,
  "uniqueid": "...",
  "number": "...",
  "display_name": "...",
  "callerid": "...",
  "note": "...",
  "sentiment": "positive"
}
```

## GitHub

Przed publikacją:

```bash
cp config.php config.php.local
```

W repozytorium trzymaj wyłącznie:

```text
config.php.example
```

Dodaj do `.gitignore`:

```text
config.php
*.log
```

Nie publikuj haseł do MySQL.

## Bezpieczeństwo

Panel jest przeznaczony do pracy w zaufanej sieci. Przed wystawieniem do Internetu należy dodać uwierzytelnianie HTTP, HTTPS i ograniczenie dostępu do panelu.
