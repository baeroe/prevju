---
layout: home

hero:
  name: prevju
  text: Send clients a link, not a zip.
  tagline: Self-hosted previews for HTML drafts. Drop a folder, copy the link, optionally lock it with a password. One container, no database server.
  actions:
    - theme: brand
      text: Get started
      link: /en/setup
    - theme: alt
      text: GitHub
      link: https://github.com/baeroe/prevju

features:
  - title: Drop a folder, get a link
    details: Drag a whole folder, single files or a zip onto a site. Structure is kept, a wrapping dist/ folder is removed. The link works right away.
  - title: Password per site
    details: Lock a draft with a password or leave it open. Clients see a clean unlock page, not a login.
  - title: Built for agents
    details: An MCP server lets Claude Code and Codex create sites, upload drafts and hand you the client link. New versions replace the old one in a single step.
  - title: One container
    details: SQLite and files in one volume. docker compose up, done. Images for amd64 and arm64.
---

<div class="showcase">
  <div class="crop">
    <i></i><i></i><i></i><i></i>
    <img src="/screenshots/en/sites.png" alt="prevju admin: sites as cards with a live preview of each draft">
  </div>
</div>
