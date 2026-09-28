# Reverse proxy

prevju speaks plain HTTP on port `7738`. HTTPS comes from a reverse proxy in front of it.

prevju trusts `X-Forwarded-Proto` from proxies in private networks (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, localhost). Proxies in the same Docker host or network are covered. If your proxy reaches prevju from a public IP, links and assets are generated as `http://` and browsers block them as mixed content.

## Caddy

```txt
preview.example.com {
    reverse_proxy localhost:7738
}
```

If Caddy runs in the same compose project, use `reverse_proxy prevju:8080` instead. Caddy gets the certificate on its own.

## Traefik

Add labels to the prevju service and leave out `ports`:

```yaml
services:
  prevju:
    image: baeroe/prevju:latest
    labels:
      - traefik.enable=true
      - traefik.http.routers.prevju.rule=Host(`preview.example.com`)
      - traefik.http.routers.prevju.entrypoints=websecure
      - traefik.http.routers.prevju.tls.certresolver=letsencrypt
      - traefik.http.services.prevju.loadbalancer.server.port=8080
```

Entrypoint and cert resolver names depend on your Traefik setup.

## Nginx Proxy Manager

1. Add a proxy host for `preview.example.com`.
2. Scheme `http`, forward to the Docker host (or the `prevju` container name if both share a network), port `7738` (or `8080` for the container name).
3. SSL tab: request a certificate, enable *Force SSL*.

## Checklist

- `APP_URL` in `.env` starts with `https://`.
- Page source of `/login` shows asset links starting with `https://`. If they start with `http://`, see the trusted-network note above.
- Uploads up to 100 MB per file must pass the proxy. Caddy and Traefik don't limit the body size by default; with plain nginx set `client_max_body_size 200m;`.
