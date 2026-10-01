# Projekte

Ein Projekt bündelt mehrere Sites hinter einem Link. Statt eines Links pro Entwurf schickst du dem Kunden den Projekt-Link: Er sieht alle Entwürfe des Projekts auf einer Seite, die zuletzt geänderten zuerst. Praktisch, wenn du iterierst: v1, v2, … v10 bleiben nebeneinander stehen, und der Kunde kann sagen „v3 war besser“.

## Projekt anlegen

Der Admin startet bei **Projekte**. Auf **Neues Projekt** klicken und danach Sites direkt im Projekt anlegen, mit **Site hinzufügen** auf der Projektseite. Auch **Neue Site** auf der Sites-Seite lässt dich ein Projekt wählen. Die Card eines Projekts blättert durch die Vorschauen seiner Sites.

![Die Projekte-Seite im prevju-Admin](/screenshots/de/projects.png)

## Sites in ein Projekt verschieben

**Sites** zeigt alle Sites, links stehen deine Projekte. Eine Site gehört zu höchstens einem Projekt.

- **Ziehen:** eine Site links auf ein Projekt ziehen. Auf **Ohne Projekt** gezogen, ist sie wieder draußen.
- **Oder klicken:** das Projekt-Label auf einer Card (**+ Projekt**, wenn sie keins hat) öffnet die Liste, dort ein Projekt oder **Kein Projekt** wählen. Das geht auch auf dem Handy und mit der Tastatur.
- Auf der Seite eines Projekts nimmt das **×** auf einer Card die Site aus dem Projekt.

Jedes Verschieben zeigt eine Meldung mit **Rückgängig**. Ein Klick auf ein Projekt links zeigt nur dessen Sites.

![Die Sites-Seite: links die Projekte, auf jeder Card ein Projekt-Label](/screenshots/de/sites.png)

![Ein Projekt im prevju-Admin: Link, enthaltene Sites, Name und Passwort](/screenshots/de/project.png)

## Die Seite für den Kunden

![Die Projektseite für den Kunden: alle Entwürfe als Karten, neueste zuerst](/screenshots/de/client.png)

Der Projekt-Link (`/project/<slug>`) zeigt für jede Site eine Card mit Live-Vorschau, sortiert nach letzter Änderung. Ein Klick öffnet die Site. Sites ohne HTML-Datei (etwa eine neue Version vor dem Upload) erscheinen dort noch nicht.

## Passwörter

![Passwortseite eines Projekts](/screenshots/de/password.png)

- **Projekt-Passwort:** Ein Passwort öffnet die Projektseite und alle Sites darin, auch Sites mit eigenem Passwort.
- **Site-Passwort:** gilt weiter. Der Einzel-Link einer Site funktioniert weiter, mit oder ohne Projekt.
- **Projekt ohne Passwort:** Jeder mit dem Link sieht die Liste. Sites mit eigenem Passwort zeigen dort keine Vorschau und fragen weiter nach ihrem Passwort.

Passwortseiten erlauben 10 Versuche pro Minute.

## Löschen

Ein gelöschtes Projekt entfernt nur das Projekt und seinen Link. Die Sites bleiben und stehen wieder einzeln in der Übersicht.

## Mit einem Agenten

Über [MCP](/de/mcp) legen Agenten mit `create-project`, `list-projects` und `project_id` bei `create-site` jede Version im Projekt ab.
