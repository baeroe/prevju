const rtf = new Intl.RelativeTimeFormat('de', { numeric: 'auto' });
const steps: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

export function timeAgo(iso: string): string {
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    for (const [unit, size] of steps) {
        if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit);
    }
    return 'gerade eben';
}

export function fileCount(n: number): string {
    return n === 1 ? '1 Datei' : `${n} Dateien`;
}

export function shortUrl(url: string): string {
    return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
}
