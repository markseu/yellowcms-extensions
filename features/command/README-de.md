Command 0.8.10
==============
Befehle im Terminalfenster ausführen.

<p align="center"><img src="command-screenshot.png?raw=true" alt="Bildschirmfoto"></p>

## Wie man diese Erweiterung installiert

1. [Datenstrom Yellow herunterladen und installieren](https://github.com/datenstrom/yellow/).
2. [Erweiterung herunterladen](https://github.com/datenstrom/yellow-extensions/raw/master/zip/command.zip). Falls du Safari verwendest, rechtsklicke und wähle "Verknüpfte Datei laden unter".
3. Kopiere `command.zip` in dein `system/extensions`-Verzeichnis.

Die [Erweiterungsdateien](extension.ini) bitte nicht löschen, sie werden immer gebraucht.

## Wie man einen Befehl ausführt

Du kannst Befehle im Installations-Verzeichnis ausführen. Öffne ein Terminalfenster. Gehe ins Installations-Verzeichnis, dort wo sich die `yellow.php` befindet. Gib ein `php yellow.php` gefolgt von weiteren Argumenten. Um die vorhandenen Befehle anzuzeigen, gib einfach keine Argumente an.

## Wie man eine statische Webseite erstellt

Erstelle eine statische Webseite in der Befehlszeile. Öffne ein Terminalfenster. Gehe ins Installations-Verzeichnis, dort wo sich die `yellow.php` befindet. Gib ein `php yellow.php build`, du kannst wahlweise ein Verzeichnis und einen Ort hinzufügen. Das erstellt eine statische Webseite im `public`-Verzeichnis. Lade die statische Webseite auf dein Webserver hoch und erstelle bei Bedarf eine neue. Die [Systemeinstellungen](https://github.com/datenstrom/yellow-extensions/tree/master/features/core#settings) einer statischen Webseite kannst du in der Datei `system/settings/system.ini` festlegen.

Du kannst eine statische Webseite auch mit dem eingebauten Webserver testen. Das ist vor allem für Entwickler praktisch, da alles auf dem eigenem Computer läuft. Hier ist ein Beispiel: `php yellow.php serve`. Daraufhin ist die Webseite vorhanden als `http://localhost:8000/`.

## Wie man einen statischen Zwischenspeicher erstellt

Als Alternative zu einer statischen Webseite kannst du einen statischen Zwischenspeicher erstellen. Das beschleunigt deine Webseite deutlich, jedoch muss der Zwischenspeicher immer wieder aktualisiert werden. Hier ist ein Beispiel: `php yellow.php build cache`. Zum Löschen gibt man ein: `php yellow.php clean cache`.

## Befehle

Die folgenden Befehle sind verfügbar:

`php yellow.php about` = Version der Webseite anzeigen  
`php yellow.php build` = Statische Webseite erstellen  
`php yellow.php check` = Statische Webseite überprüfen  
`php yellow.php clean` = Statische Webseite löschen  
`php yellow.php install` = Erweiterungen hinzufügen mit der [Update-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/update)  
`php yellow.php release` = Erweiterungen veröffentlichen mit der [Release-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/release)  
`php yellow.php serve` = Eingebauten Webserver starten  
`php yellow.php traffic` = Zugriffsanalysen erstellen mit der [Traffic-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/traffic)  
`php yellow.php uninstall` = Erweiterungen entfernen mit der [Update-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/update)  
`php yellow.php update` = Webseite aktualisieren mit der [Update-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/update)  
`php yellow.php user` = Benutzerkonten aktualisieren mit der [Edit-Erweiterung](https://github.com/datenstrom/yellow-extensions/tree/master/features/edit)  

Diese Erweiterung benutzt die [cURL-Bibliothek](https://github.com/curl/curl) von Daniel Stenberg.

## Beispiele

Vorhandene Befehle anzeigen:

`php yellow.php`

~~~~
Datenstrom Yellow is for people who make websites.
Syntax: php yellow.php about
        php yellow.php build [directory location]
        php yellow.php check [directory location]
        php yellow.php clean [directory location]
        php yellow.php install [extension]
        php yellow.php release [directory]
        php yellow.php serve [directory url]
        php yellow.php traffic [days location filename]
        php yellow.php uninstall [extension]
        php yellow.php update [extension]
        php yellow.php user [option email password name]
~~~~

Statische Webseite in der Befehlszeile erstellen:

`php yellow.php build`  
`php yellow.php build public /blog/`  
`php yellow.php build public /wiki/`  

Statische Webseite in der Befehlszeile nach fehlerhaften Links überprüfen:

`php yellow.php check`  
`php yellow.php check public /blog/`  
`php yellow.php check public /wiki/`  

Eingebauten Webserver in der Befehlszeile starten:

`php yellow.php serve`  
`php yellow.php serve public http://localhost:8008/`  
`php yellow.php serve dynamic http://localhost:8008/`  

## Entwickler

Datenstrom. [Support finden](https://extensions.datenstrom.se/de/help/).

<p>
<a href="README-de.md"><img src="https://raw.githubusercontent.com/datenstrom/yellow-extensions/master/features/help/language-de.png" width="15" height="15" alt="Deutsch">&nbsp; Deutsch</a>&nbsp;
<a href="README.md"><img src="https://raw.githubusercontent.com/datenstrom/yellow-extensions/master/features/help/language-en.png" width="15" height="15" alt="English">&nbsp; English</a>&nbsp;
</p>