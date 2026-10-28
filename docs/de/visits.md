# Aufrufe

prevju zeigt dir, ob und wann dein Kunde einen Link geöffnet hat. Jede Site- und Projektkarte zeigt **geöffnet vor 2 Stunden** oder **noch nicht geöffnet**, die Seiten einer Site und eines Projekts listen die Aufrufe.

![Aufrufe auf einer Projektseite: welche Version wann und mit welchem Gerät geöffnet wurde](/screenshots/de/visits.png)

## Was gezählt wird

- **Seitenaufrufe:** jede HTML-Seite einer Site und die Kundenseite eines Projekts. Bilder, CSS und JS zählen nicht.
- **Passworteingaben:** richtige und falsche, damit du siehst, ob der Kunde reingekommen ist.
- **Pro Aufruf:** Zeitpunkt, Seite, Gerätetyp (Desktop, Handy, Tablet), Browser, Betriebssystem und die Domain, auf der der Link geklickt wurde (z. B. `mail.google.com`).

Auf einer Projektseite siehst du die Aufrufe des Projektlinks und aller seiner Sites, jeweils mit der Version.

## Was nicht gezählt wird

- **Deine eigenen Aufrufe**, solange du im Admin eingeloggt bist, und die Vorschauen im Admin.
- **Linkvorschauen** von WhatsApp, Slack, Teams, iMessage und ähnlichen. Die rufen die Seite ab, sobald du den Link verschickst, bevor jemand klickt.
- **IP-Adressen und Cookies.** prevju speichert keine IP und setzt kein Tracking-Cookie.
- **Link-Checker und Prefetches** von Browsern, die die Seite nur vorab abrufen.

Manche Mailfilter in Firmen (z. B. Microsoft Defender) öffnen Links mit einem normalen Browser, bevor sie die Mail zustellen. Die kann prevju nicht vom Kunden unterscheiden. Ein Aufruf direkt nach dem Verschicken kann also so ein Filter sein.

## Datenschutz

Aufrufe sind anonym. Statt einer IP bekommt jeder Aufruf eine Besucherkennung aus gekürzter IP, Browser und einem zufälligen Salt, der jeden Tag wechselt und nach zwei Tagen weggeworfen wird. Zwei Aufrufe mit derselben Kennung am selben Tag kommen aus demselben Browser. Über Tage hinweg und zurück zu einer Person führt die Kennung nicht.

Du siehst **wann** und **mit welchem Gerät**, nicht **wer**. Schickst du einen Link an mehrere Leute, kannst du sie nicht auseinanderhalten.

Weil prevju keine personenbezogenen Daten im Klartext speichert und keine Cookies setzt, braucht es in üblichen Setups keinen Einwilligungsbanner. Du betreibst prevju selbst und bist damit für die Daten verantwortlich: Erwähne sie in deiner Datenschutzerklärung (z. B. „Seitenaufrufe auf Vorschau-Links, ohne IP-Adressen und Cookies, Löschung nach 90 Tagen“).

## Aufbewahrung

Aufrufe werden nach 90 Tagen gelöscht. Ändern lässt sich das mit `VISITS_RETENTION_DAYS` in der `.env` neben der Compose-Datei, durchgereicht in `docker-compose.yml`:

```yaml
    environment:
      VISITS_RETENTION_DAYS: "${VISITS_RETENTION_DAYS:-90}"
```

`VISITS_RETENTION_DAYS=0` schaltet die Zählung ab. Alte Aufrufe werden nebenbei aufgeräumt, ohne Cronjob.

## Über API und MCP

- **API:** `GET /api/visits` mit `filter[site_id]`, `filter[project_id]`, `filter[kind]` (`view`, `unlock`, `unlock_failed`), `filter[since]` und `filter[until]`. Sites und Projekte haben `view_count` und `last_viewed_at`. Siehe [REST-API](/de/api).
- **MCP:** `list-visits` mit `site_id` oder `project_id`, dann kannst du deinen Agenten fragen „Hat der Kunde schon reingeschaut?“. Siehe [MCP](/de/mcp).
