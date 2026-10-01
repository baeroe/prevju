# Projects

A project bundles several sites behind one link. Send your client the project link instead of a link per draft: they see all drafts of the project on one page, the most recently changed first. Handy when you iterate: v1, v2, … v10 stay side by side and the client can say "v3 was better".

## Create a project

In the admin, click **New project**. Then either create sites right inside it (**New site** on the project page) or open an existing site and pick the project under **Project**. A site belongs to at most one project.

In the admin overview, a project's card cycles through the previews of its sites.

## The client's page

The project link (`/project/<slug>`) shows a card with a live preview for each site, sorted by last change. A click opens the site. Sites without an HTML file (e.g. a new version before its upload) don't show up yet.

## Passwords

- **Project password:** one password opens the project page and every site in it, also sites that have their own password.
- **Site password:** still works. A site's own link keeps working, with or without a project.
- **Project without password:** anyone with the link sees the list. Sites with their own password show no preview there and still ask for their password.

Password pages allow 10 attempts per minute.

## Deleting

Deleting a project removes only the project and its link. Its sites stay and show up as standalone sites again.

## With an agent

Over [MCP](/mcp), agents use `create-project`, `list-projects` and `project_id` on `create-site` to put each version into the project.
