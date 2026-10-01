# Hochladen

Leg im Admin eine Site an und zieh die Dateien darauf.

![Eine Site im prevju-Admin: Link, Live-Vorschau, Upload-Fläche und Dateiliste](/screenshots/site.png)

- **Ordner:** Zieh einen ganzen Ordner hinein, die Struktur bleibt erhalten. Beim ersten Upload fällt ein einzelner umschließender Ordner (`dist/…`) weg, `dist/index.html` wird also zur Startseite.
- **ZIPs:** werden beim Upload entpackt, ebenfalls ohne einzelnen umschließenden Ordner. `__MACOSX`-Einträge werden übersprungen.
- **Einzeldateien:** HTML, CSS, JS, Bilder, Schriften. Bis 100 MB pro Datei.
- **Startseite:** `index.html`. Fehlt sie, wird die erste `.html`-Datei ausgeliefert.

Uploads laufen Datei für Datei mit Fortschritt, fehlgeschlagene Dateien lassen sich erneut hochladen. Gelöschte Dateien und Sites kannst du fünf Sekunden lang über *Rückgängig* wiederherstellen.

Bevor du eine gebaute App hochlädst, wirf einen Blick auf [Was funktioniert](/de/what-works).

## Passwörter

Jede Site kann ein eigenes Passwort haben. Kunden sehen eine Freischalt-Seite mit dem Namen der Site. Eine Passwortänderung meldet niemanden ab, der bereits freigeschaltet hat; ohne Passwort ist die Site öffentlich.

Mehrere Entwürfe für denselben Kunden? Leg sie in ein [Projekt](/de/projects): ein Link, ein Passwort.

## Vorschau im Admin

Jede Site-Karte zeigt eine Live-Vorschau des Entwurfs. Die Vorschau läuft in einer Sandbox, Skripte im Entwurf kommen nicht an den Admin.
