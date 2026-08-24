# Kto Dzwoni?

Panel FreePBX/Asterisk do identyfikacji połączeń, historii numerów, własnych informacji i książki Cisco.

## Funkcje
- monitor aktywnego połączenia
- własna nazwa i notatka numeru
- flaga pozytywny / neutralny / negatywny
- historia połączeń
- ręczne dodawanie numerów
- usuwanie rekordów z call_history
- dane z odebractelefon_cache
- checkbox książki Cisco
- automatyczne generowanie cisco/directory.xml

## Instalacja
1. Skopiuj `telefon/` do `/var/www/html/telefon/`.
2. Skopiuj `config.php.example` do `config.php` i wpisz dane DB.
3. Uruchom `mysql asterisk < sql/install.sql`.
4. Dodaj konteksty z `asterisk/extensions_custom.conf.example` do `/etc/asterisk/extensions_custom.conf`.
5. `asterisk -rx "dialplan reload"`.
6. Ustaw prawa zapisu do `telefon/cisco/directory.xml`.

## Cisco
Książka jest generowana do `/var/www/html/telefon/cisco/directory.xml`.

## Bezpieczeństwo
`config.php` nie jest częścią repozytorium. Nie publikuj haseł do MySQL.
