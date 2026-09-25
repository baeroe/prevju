# Design-Richtung

**Produkt**: Admin für prevju. Eine Person lädt HTML-Entwürfe hoch und schickt dem Kunden einen Link, optional mit Passwort. Gegenentwurf zu „Dateien per WeTransfer schicken“.
**Modus**: Product (Admin). Die Kunden-Passwortseite ist der einzige Screen, den Fremde sehen; gleiche Tokens, gleiche Ruhe.
**Richtung**: Proof Sheet — die App ist ein Andruckbogen. Jeder Entwurf ist ein Nutzen auf dem Bogen, gerahmt von Schneidmarken. Passt, weil prevju genau das digital macht: Entwürfe zur Freigabe vorlegen.
**Referenz**: keine

## Tokens

```css
:root {
  --paper:       oklch(0.968 0.003 95);   /* Bogen, Seitenhintergrund */
  --sheet:       oklch(0.993 0.002 95);   /* Flächen: Inputs, Dialoge, Preview-Rahmen */
  --ink:         oklch(0.23 0.025 255);   /* Text, Schneidmarken, Rahmen aktiv */
  --ink-muted:   oklch(0.50 0.015 255);   /* Sekundärtext */
  --hairline:    oklch(0.86 0.006 255);   /* Trenner, Rahmen ruhend */
  --signal:      oklch(0.70 0.19 45);     /* Signal-Orange: nur Primäraktion, Hover-Schneidmarken, Fortschritt */
  --signal-ink:  oklch(0.23 0.025 255);   /* Text auf Signal: Tinte, nicht Weiß (Kontrast) */
  --danger:      oklch(0.55 0.20 25);

  --font-display: "Instrument Sans", ui-sans-serif, sans-serif;
  --font-mono:    "JetBrains Mono", ui-monospace, monospace;

  --radius: 0;
  --dur: 120ms;
  --ease: steps(3, end);  /* Schneidmarken */
}
```

Spacing: Tailwind-Skala (4px-Raster), keine freien Werte. Keine Schatten. Keine Rundung, nirgends.

## Typo-Regeln

- Instrument Sans für alles, was Sprache ist: 400 Fließtext, 500 Namen, 600 Seitentitel (tracking −0.02em, leading 1.1).
- JetBrains Mono **nur** für echte Maschinenwerte: Links, Slugs, Dateipfade, Dateianzahl. Nie für Labels oder Deko.
- Satzschreibung, keine Großbuchstaben-Labels, keine Eyebrows über Überschriften.
- Metadaten stehen in eigenen Spans mit Abstand, nicht mit Mittelpunkten verkettet.

## Das eine markante Element

Schneidmarken (`CropMarks`): vier Winkel außerhalb der Ecken jeder Vorschau, des Login-Bogens und der Kunden-Passwortseite. Hairline in Tinte. Beim Hover einer Card rücken sie 3px nach außen und werden Signal-Orange (steps, 120ms). Sonst nirgends Deko.

## Motion

120ms, `steps()` für Schneidmarken, alles andere instant bzw. Opazität. Kein Fade-Rise beim Laden. `prefers-reduced-motion`: Schneidmarken bewegen sich nicht, nur Farbe.

## No-Gos

- Gerundete Ecken, Schatten, Gradients.
- Mono als Label-Font, ALL-CAPS-Labels, „A · B · C“-Meta-Strings, „→“ an Buttons.
- Weißer Text auf Signal-Orange (Kontrast < 4.5).
- Zweiter Akzent. Danger ist semantisch und erscheint nur bei destruktiven Aktionen.
- Dark Mode: bewusst nicht gebaut (ein Nutzer, Admin-Tool). Tokens sind so benannt, dass er nachrüstbar ist.

## Entscheidungen

- 2026-09-25: Richtung Proof Sheet gewählt (Nutzer).
- 2026-09-25: Sites als Cards mit Live-Vorschau (Nutzerwunsch). Vorschau = skaliertes, nicht interaktives iframe der echten Site, kein Screenshot-Dienst im Container.
- 2026-09-25: Signal-Buttons mit Tinte statt Weiß beschriftet — Weiß auf Orange fällt durch den Kontrast.
- 2026-09-25: Fokusring in Tinte statt Signal-Orange — Orange auf Papier erreicht keine 3:1 für Fokus-Indikatoren (web-design-guidelines, A11y sticht DESIGN.md).
