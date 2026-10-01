# MCP

prevju has an MCP server at `<APP_URL>/mcp`. With it, Claude Code or Codex can create sites, upload drafts and give you the client link.

## Connect

Create a token in the admin under **MCP**. The page generates the command with your token filled in, for Claude Code or Codex, for all projects or just the current one.

**Claude Code** (`--scope user` for all projects, `--scope local` for the current one):

```bash
claude mcp add --transport http --scope user prevju https://preview.example.com/mcp --header "Authorization: Bearer <token>"
```

**Codex:** add this to `~/.codex/config.toml` (all projects) or `.codex/config.toml` in a trusted project. Keep the project file out of git, it contains the token.

```toml
[mcp_servers.prevju]
url = "https://preview.example.com/mcp"
http_headers = { "Authorization" = "Bearer <token>" }
```

A token has the same rights as the admin login. Create one per device so you can revoke them one by one.

## Tools

| Tool | What it does |
|---|---|
| `get-compatibility` | Returns [What works](/en/what-works). Agents call it before uploading |
| `list-sites`, `get-site` | Sites with link, password status and files |
| `create-site` | New site, optionally with a password and in a project |
| `update-site` | Rename, set or remove the password, move into or out of a project |
| `list-projects`, `create-project` | [Projects](/en/projects): one client link for several sites |
| `write-files` | Write generated text files (HTML, CSS, JS) directly |
| `get-upload-url` | Signed 15-minute URL to upload a zip or binary file with `curl` |
| `delete-file`, `clear-files`, `delete-site` | Destructive, no undo |

For a new version of a draft, agents upload with `replace`: the live site switches over in one step and is never empty in between. A failed upload changes nothing. To keep versions side by side for the client, agents create each version as its own site in a [project](/en/projects).

## Limits

- Zips and other binary files are uploaded with `curl` to a signed URL, so they need a client with a shell (Claude Code, Codex).
- Connectors in claude.ai and Claude Desktop need OAuth, which prevju doesn't support yet.
