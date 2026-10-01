import { getLocale, t, tn } from '@/lib/i18n';

const steps: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

export function timeAgo(iso: string): string {
    const rtf = new Intl.RelativeTimeFormat(getLocale(), { numeric: 'auto' });
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    for (const [unit, size] of steps) {
        if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit);
    }
    return t('time.just_now');
}

export function fileCount(n: number): string {
    return tn('files.count', n);
}

export function shortUrl(url: string): string {
    return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
}

export function siteCount(n: number): string {
    return tn('sites.count', n);
}
