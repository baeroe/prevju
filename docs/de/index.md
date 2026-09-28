---
layout: home

hero:
  name: prevju
  text: Schick deinen Kunden einen Link, kein ZIP.
  tagline: Selbst gehostete Vorschauen für HTML-Entwürfe. Ordner reinziehen, Link kopieren, optional mit Passwort schützen. Ein Container, kein Datenbankserver.
  actions:
    - theme: brand
      text: Loslegen
      link: /de/setup
    - theme: alt
      text: GitHub
      link: https://github.com/baeroe/prevju

features:
  - title: Ordner rein, Link raus
    details: Zieh einen ganzen Ordner, einzelne Dateien oder eine ZIP auf eine Site. Die Struktur bleibt erhalten, ein umschließender dist/-Ordner fällt weg. Der Link funktioniert sofort.
  - title: Passwort pro Site
    details: Schütz einen Entwurf mit einem Passwort oder lass ihn offen. Kunden sehen eine schlichte Freischalt-Seite, kein Login.
  - title: Gebaut für Agenten
    details: Über einen MCP-Server legen Claude Code und Codex Sites an, laden Entwürfe hoch und geben dir den Kundenlink. Neue Versionen ersetzen die alte in einem Schritt.
  - title: Ein Container
    details: SQLite und Dateien in einem Volume. docker compose up, fertig. Images für amd64 und arm64.
---

<div class="showcase">
  <div class="crop">
    <i></i><i></i><i></i><i></i>
    <img src="/screenshots/sites.png" alt="prevju-Admin: Sites als Karten mit Live-Vorschau jedes Entwurfs">
  </div>
</div>
