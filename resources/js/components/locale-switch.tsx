import { getLocale, t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const LOCALES = [
    ['en', 'EN', 'English'],
    ['de', 'DE', 'Deutsch'],
] as const;

/** Admin pages swap their /en|/de prefix; client pages go through /locale/{code}, which sets a cookie. Full reload either way. */
function switchUrl(code: string): string {
    const { pathname, search } = window.location;
    return /^\/(en|de)(\/|$)/.test(pathname) ? pathname.replace(/^\/(en|de)/, `/${code}`) + search : `/locale/${code}`;
}

export function LocaleSwitch({ className }: { className?: string }) {
    return (
        <nav aria-label={t('nav.language')} className={cn('flex text-sm', className)}>
            {LOCALES.map(([code, short, name]) => (
                <a
                    key={code}
                    href={switchUrl(code)}
                    lang={code}
                    aria-label={name}
                    aria-current={getLocale() === code ? 'true' : undefined}
                    className="px-1.5 py-1 text-ink-muted hover:text-ink aria-[current]:font-medium aria-[current]:text-ink"
                >
                    {short}
                </a>
            ))}
        </nav>
    );
}
