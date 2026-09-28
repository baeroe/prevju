# Reverse-Proxy

prevju spricht einfaches HTTP auf Port `7738`. HTTPS kommt von einem Reverse-Proxy davor.

prevju vertraut `X-Forwarded-Proto` von Proxies in privaten Netzen (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, localhost). Proxies auf demselben Docker-Host oder im selben Netz sind damit abgedeckt. Erreicht dein Proxy prevju über eine öffentliche IP, entstehen Links und Assets als `http://`, und Browser blockieren sie als Mixed Content.

## Caddy

```txt
preview.example.com {
    reverse_proxy localhost:7738
}
```

Läuft Caddy im selben Compose-Projekt, nimm stattdessen `reverse_proxy prevju:8080`. Das Zertifikat holt Caddy selbst.

## Traefik

Labels an den prevju-Service hängen und `ports` weglassen:

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

Die Namen von Entrypoint und Cert-Resolver hängen von deinem Traefik-Setup ab.

## Nginx Proxy Manager

1. Einen Proxy Host für `preview.example.com` anlegen.
2. Scheme `http`, Weiterleitung an den Docker-Host (oder den Containernamen `prevju`, wenn beide im selben Netz sind), Port `7738` (bzw. `8080` beim Containernamen).
3. Tab SSL: Zertifikat anfordern, *Force SSL* aktivieren.

## Checkliste

- `APP_URL` in der `.env` beginnt mit `https://`.
- Im Seitenquelltext von `/login` beginnen die Asset-Links mit `https://`. Stehen dort `http://`-Links, gilt der Hinweis zu privaten Netzen oben.
- Uploads bis 100 MB pro Datei müssen durch den Proxy passen. Caddy und Traefik begrenzen die Größe standardmäßig nicht; bei reinem nginx `client_max_body_size 200m;` setzen.
