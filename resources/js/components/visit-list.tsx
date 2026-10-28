import { dateTime, timeAgo } from '@/lib/format';
import { t, tn } from '@/lib/i18n';
import type { Visit, VisitStats } from '@/types';

type Props = {
    stats: VisitStats;
    visits: Visit[];
    days: number;
    /** On a project's page: names of its sites, so each row says which version was opened. */
    siteNames?: Record<number, string>;
};

export function VisitList({ stats, visits, days, siteNames }: Props) {
    return (
        <section className="grid gap-3 border-t pt-8">
            <h2 className="flex items-baseline justify-between font-medium">
                {t('visits.title')}
                <span className="font-mono text-xs font-normal text-ink-muted">{tn('visits.views', stats.view_count)}</span>
            </h2>
            <p className="text-sm text-ink-muted">{t('visits.hint', { days })}</p>
            {visits.length === 0 ? (
                <p className="text-sm text-ink-muted">{t('visits.empty')}</p>
            ) : (
                <ul className="max-h-96 overflow-y-auto border-t">
                    {visits.map((visit) => (
                        <li key={visit.id} className="grid gap-0.5 border-b py-2 last:border-b-0">
                            <p className="flex flex-wrap items-baseline gap-x-3 text-sm">
                                <span className={visit.kind === 'unlock_failed' ? 'text-danger' : undefined}>{t(`visits.kind_${visit.kind}`)}</span>
                                {siteNames && <span className="min-w-0 truncate font-medium">{visit.site_id ? siteNames[visit.site_id] : t('visits.project_page')}</span>}
                                {visit.path && visit.path !== '/' && <span className="min-w-0 truncate font-mono text-xs text-ink-muted">{visit.path}</span>}
                                <time dateTime={visit.created_at} title={timeAgo(visit.created_at)} className="ml-auto shrink-0 text-xs text-ink-muted">
                                    {dateTime(visit.created_at)}
                                </time>
                            </p>
                            <p className="flex flex-wrap gap-x-4 text-xs text-ink-muted">
                                <span>{t(`visits.device_${visit.device}`)}</span>
                                {(visit.browser || visit.os) && <span>{[visit.browser, visit.os].filter(Boolean).join(', ')}</span>}
                                {visit.referrer && <span>{t('visits.via', { host: visit.referrer })}</span>}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

/** "opened 2 hours ago" / "not opened yet" for the cards. */
export function lastOpened(stats: VisitStats): string {
    return stats.last_viewed_at ? t('visits.opened', { time: timeAgo(stats.last_viewed_at) }) : t('visits.never');
}
