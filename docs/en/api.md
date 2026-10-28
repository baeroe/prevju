# REST API

Everything the admin does is also available as JSON below `<APP_URL>/api`: sites, projects, files and [visits](/en/visits). Use it from scripts, CI pipelines or anything that isn't an MCP client.

## Authentication

The API uses the same tokens as [MCP](/en/mcp). Create one in the admin under **API & MCP** and send it as a bearer token:

```bash
curl -H "Authorization: Bearer <token>" https://preview.example.com/api/sites
```

A token has the same rights as the admin login. Without a valid token every request gets `401`.

## Endpoints

| Method | Path | What it does |
|---|---|---|
| `GET` | `/api/sites` | List sites |
| `POST` | `/api/sites` | Create a site: `name`, optional `password`, `project_id` |
| `GET` | `/api/sites/{id}` | One site |
| `PATCH` | `/api/sites/{id}` | Change `name`, `password`, `clear_password: true`, `project_id` (`null` takes it out of its project) |
| `DELETE` | `/api/sites/{id}` | Delete the site with all files |
| `GET` | `/api/sites/{id}/files` | Relative paths of all files |
| `POST` | `/api/sites/{id}/files` | Upload one file or zip (multipart `file`, optional `path`, `replace`) |
| `DELETE` | `/api/sites/{id}/files/{path}` | Delete one file |
| `DELETE` | `/api/sites/{id}/files` | Delete all files, the site stays |
| `GET` | `/api/projects` | List projects |
| `POST` | `/api/projects` | Create a project: `name`, optional `password` |
| `GET` | `/api/projects/{id}` | One project with its sites |
| `PATCH` | `/api/projects/{id}` | Change `name`, `password`, `clear_password: true` |
| `DELETE` | `/api/projects/{id}` | Delete the project, its sites stay |
| `GET` | `/api/visits` | Page views and password attempts, see [Visits](/en/visits) |

Request bodies are JSON (`Content-Type: application/json`), except file uploads. Passwords are never returned, only `has_password`.

A site looks like this:

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

## Uploading

One file per request, up to 100 MB. A zip is unpacked, a single wrapping folder is dropped. `path` defaults to the file name. With `replace=1` the upload replaces the whole site in one step: the live site is never empty in between.

```bash
curl -H "Authorization: Bearer <token>" \
  -F file=@dist.zip -F replace=1 \
  https://preview.example.com/api/sites/3/files
```

Check the draft against [What works](/en/what-works) first.

## Filtering, sorting, pages

Lists take query parameters:

| Parameter | Example | Works on |
|---|---|---|
| `filter[name]` | `filter[name]=relaunch` (part of the name) | sites, projects |
| `filter[project_id]` | `filter[project_id]=1` | sites, visits |
| `filter[site_id]`, `filter[kind]`, `filter[visitor]` | `filter[kind]=unlock_failed` | visits |
| `filter[since]`, `filter[until]` | `filter[since]=2026-10-01` | visits |
| `sort` | `sort=name`, `sort=-updated_at` (`-` = descending) | all |
| `include` | `include=sites` | projects |
| `page`, `per_page` | `per_page=100` (default 30, max 100) | all |

Sites sort by `name`, `created_at`, `updated_at`, `view_count` or `last_viewed_at`; the default is `-updated_at`. Lists return `data`, `links` and `meta` (with `total`, `last_page`).

## Errors

Errors follow [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) (`Content-Type: application/problem+json`):

```json
{
  "type": "about:blank",
  "title": "Unprocessable Content",
  "status": 422,
  "detail": "The request data is invalid.",
  "violations": [{ "path": "name", "message": "The name field is required." }]
}
```

| Status | When |
|---|---|
| `400` | Unknown filter, sort or include |
| `401` | Missing or revoked token |
| `404` | No such site, project or file |
| `422` | Invalid data, `violations` says which field |
| `429` | More than 120 requests per minute with one token. `Retry-After` says when to try again |
