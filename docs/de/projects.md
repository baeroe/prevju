# Projekte

Ein Projekt bündelt mehrere Sites hinter einem Link. Statt eines Links pro Entwurf schickst du dem Kunden den Projekt-Link: Er sieht alle Entwürfe des Projekts auf einer Seite, die zuletzt geänderten zuerst. Praktisch, wenn du iterierst: v1, v2, … v10 bleiben nebeneinander stehen, und der Kunde kann sagen „v3 war besser“.

## Projekt anlegen

Im Admin auf **Neues Projekt** klicken. Danach Sites direkt im Projekt anlegen (**Neue Site** auf der Projektseite) oder eine bestehende Site öffnen und unter **Projekt** das Projekt wählen. Eine Site gehört zu höchstens einem Projekt.

In der Admin-Übersicht blättert die Card eines Projekts durch die Vorschauen seiner Sites.

## Die Seite für den Kunden

Der Projekt-Link (`/project/<slug>`) zeigt für jede Site eine Card mit Live-Vorschau, sortiert nach letzter Änderung. Ein Klick öffnet die Site. Sites ohne HTML-Datei (etwa eine neue Version vor dem Upload) erscheinen dort noch nicht.

## Passwörter

- **Projekt-Passwort:** Ein Passwort öffnet die Projektseite und alle Sites darin, auch Sites mit eigenem Passwort.
- **Site-Passwort:** gilt weiter. Der Einzel-Link einer Site funktioniert weiter, mit oder ohne Projekt.
- **Projekt ohne Passwort:** Jeder mit dem Link sieht die Liste. Sites mit eigenem Passwort zeigen dort keine Vorschau und fragen weiter nach ihrem Passwort.

Passwortseiten erlauben 10 Versuche pro Minute.

## Löschen

Ein gelöschtes Projekt entfernt nur das Projekt und seinen Link. Die Sites bleiben und stehen wieder einzeln in der Übersicht.

## Mit einem Agenten

Über [MCP](/de/mcp) legen Agenten mit `create-project`, `list-projects` und `project_id` bei `create-site` jede Version im Projekt ab.
