# REST-API

Alles, was der Admin kann, gibt es auch als JSON unter `<APP_URL>/api`: Sites, Projekte, Dateien und [Aufrufe](/de/visits). Gedacht für Skripte, CI-Pipelines und alles, was kein MCP-Client ist.

## Anmeldung

Die API nutzt dieselben Tokens wie [MCP](/de/mcp). Leg im Admin unter **API & MCP** eins an und schick es als Bearer-Token mit:

```bash
curl -H "Authorization: Bearer <token>" https://preview.example.com/api/sites
```

Ein Token hat dieselben Rechte wie der Admin-Login. Ohne gültiges Token bekommt jede Anfrage `401`.

## Endpunkte

| Methode | Pfad | Was er tut |
|---|---|---|
| `GET` | `/api/sites` | Sites auflisten |
| `POST` | `/api/sites` | Site anlegen: `name`, optional `password`, `project_id` |
| `GET` | `/api/sites/{id}` | Eine Site |
| `PATCH` | `/api/sites/{id}` | `name`, `password`, `clear_password: true`, `project_id` ändern (`null` nimmt sie aus dem Projekt) |
| `DELETE` | `/api/sites/{id}` | Site mit allen Dateien löschen |
| `GET` | `/api/sites/{id}/files` | Relative Pfade aller Dateien |
| `POST` | `/api/sites/{id}/files` | Eine Datei oder ein Zip hochladen (Multipart `file`, optional `path`, `replace`) |
| `DELETE` | `/api/sites/{id}/files/{path}` | Eine Datei löschen |
| `DELETE` | `/api/sites/{id}/files` | Alle Dateien löschen, die Site bleibt |
| `GET` | `/api/projects` | Projekte auflisten |
| `POST` | `/api/projects` | Projekt anlegen: `name`, optional `password` |
| `GET` | `/api/projects/{id}` | Ein Projekt mit seinen Sites |
| `PATCH` | `/api/projects/{id}` | `name`, `password`, `clear_password: true` ändern |
| `DELETE` | `/api/projects/{id}` | Projekt löschen, die Sites bleiben |
| `GET` | `/api/visits` | Seitenaufrufe und Passworteingaben, siehe [Aufrufe](/de/visits) |

Anfragen schicken JSON (`Content-Type: application/json`), außer beim Hochladen. Passwörter kommen nie zurück, nur `has_password`.

Eine Site sieht so aus:

```json
{
  "data": {
    "id": 3,
    "name": "Relaunch v2",
    "url": "https://preview.example.com/s/k3j9x0aq1b/",
    "has_password": true,
    "project_id": 1,
    "file_count": 14,
    "has_html": true,
    "updated_at": "2026-10-28T13:12:00+00:00",
    "view_count": 4,
    "last_viewed_at": "2026-10-28T14:32:10+00:00"
  }
}
```

## Hochladen

Eine Datei pro Anfrage, bis 100 MB. Ein Zip wird entpackt, ein einzelner umschließender Ordner fällt weg. `path` ist ohne Angabe der Dateiname. Mit `replace=1` ersetzt der Upload die ganze Site in einem Schritt: Die Live-Site ist zwischendurch nie leer.

```bash
curl -H "Authorization: Bearer <token>" \
  -F file=@dist.zip -F replace=1 \
  https://preview.example.com/api/sites/3/files
```

Prüf den Entwurf vorher gegen [Was funktioniert](/de/what-works).

## Filtern, sortieren, blättern

Listen nehmen Query-Parameter:

| Parameter | Beispiel | Gilt für |
|---|---|---|
| `filter[name]` | `filter[name]=relaunch` (Teil des Namens) | Sites, Projekte |
| `filter[project_id]` | `filter[project_id]=1` | Sites, Aufrufe |
| `filter[site_id]`, `filter[kind]`, `filter[visitor]` | `filter[kind]=unlock_failed` | Aufrufe |
| `filter[since]`, `filter[until]` | `filter[since]=2026-10-01` | Aufrufe |
| `sort` | `sort=name`, `sort=-updated_at` (`-` = absteigend) | alle |
| `include` | `include=sites` | Projekte |
| `page`, `per_page` | `per_page=100` (Standard 30, höchstens 100) | alle |

Sites lassen sich nach `name`, `created_at`, `updated_at`, `view_count` oder `last_viewed_at` sortieren, Standard ist `-updated_at`. Listen liefern `data`, `links` und `meta` (mit `total`, `last_page`).

## Fehler

Fehler folgen [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) (`Content-Type: application/problem+json`):

```json
{
  "type": "about:blank",
  "title": "Unprocessable Content",
  "status": 422,
  "detail": "The request data is invalid.",
  "violations": [{ "path": "name", "message": "The name field is required." }]
}
```

Die Fehlertexte sind immer Englisch.

| Status | Wann |
|---|---|
| `400` | Unbekannter Filter, Sortierung oder Include |
| `401` | Token fehlt oder wurde widerrufen |
| `404` | Site, Projekt oder Datei gibt es nicht |
| `422` | Ungültige Daten, `violations` nennt das Feld |
| `429` | Mehr als 120 Anfragen pro Minute mit einem Token. `Retry-After` sagt, wann es weitergeht |
