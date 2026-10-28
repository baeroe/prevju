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

/** 28 Oct, 14:32 (with the year only if it isn't this year). */
export function dateTime(iso: string): string {
    const date = new Date(iso);
    const sameYear = date.getFullYear() === new Date().getFullYear();
    return new Intl.DateTimeFormat(getLocale(), { day: 'numeric', month: 'short', year: sameYear ? undefined : 'numeric', hour: '2-digit', minute: '2-digit' }).format(date);
}
