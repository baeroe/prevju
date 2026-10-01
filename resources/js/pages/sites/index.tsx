import { Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/app-layout';
import { CropFrame } from '@/components/crop-frame';
import { NewSiteDialog } from '@/components/new-site-dialog';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { moveSite, SITE_DRAG_TYPE } from '@/lib/move-site';
import { cn } from '@/lib/utils';
import type { ProjectOption, SiteCard } from '@/types';
import { Plus } from 'lucide-react';

/** 'all', 'none' (without project) or a project id. */
type Filter = 'all' | 'none' | number;

export default function SitesIndex({ sites, projects }: { sites: SiteCard[]; projects: ProjectOption[] }) {
    const [filter, setFilter] = useState<Filter>('all');
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const inFilter = (s: SiteCard) => filter === 'all' || (filter === 'none' ? !s.project_id : s.project_id === filter);
    const shown = sites.filter((s) => inFilter(s) && (!q || s.name.toLowerCase().includes(q) || s.url.includes(q)));
    const count = (f: 'none' | number) => sites.filter((s) => (f === 'none' ? !s.project_id : s.project_id === f)).length;

    return (
        <AppLayout actions={sites.length > 0 && <NewSiteDialog projects={projects} />}>
            <Head title={t('sites.title')} />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <h1 className="flex items-baseline gap-3 text-3xl leading-none font-semibold tracking-tight">
                    {t('sites.title')}
                    {sites.length > 0 && <span className="font-mono text-base font-normal text-ink-muted">{sites.length}</span>}
                </h1>
                {sites.length > 3 && (
                    <label className="relative w-full sm:w-64">
                        <span className="sr-only">{t('sites.search_label')}</span>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden />
                        <Input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder={t('common.search')} className="pl-9" />
                    </label>
                )}
            </div>

            {sites.length === 0 ? (
                <EmptyState projects={projects} />
            ) : (
                <div className="mt-10 grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-12">
                    <nav aria-label={t('sites.filter_label')} className="flex gap-1 overflow-x-auto pb-1 lg:sticky lg:top-6 lg:flex-col lg:self-start lg:overflow-visible">
                        <FilterItem label={t('common.all_sites')} count={sites.length} active={filter === 'all'} onSelect={() => setFilter('all')} />
                        <FilterItem
                            label={t('sites.without_project')}
                            count={count('none')}
                            active={filter === 'none'}
                            onSelect={() => setFilter('none')}
                            onDropSite={(id) => moveSite(sites.find((s) => s.id === id)!, null, projects)}
                        />
                        {projects.length > 0 && <p className="hidden px-2 pt-4 pb-1 font-mono text-xs text-ink-muted lg:block">{t('sites.projects')}</p>}
                        {projects.map((p) => (
                            <FilterItem
                                key={p.id}
                                label={p.name}
                                count={count(p.id)}
                                active={filter === p.id}
                                onSelect={() => setFilter(p.id)}
                                onDropSite={(id) => moveSite(sites.find((s) => s.id === id)!, p.id, projects)}
                            />
                        ))}
                    </nav>

                    {shown.length === 0 ? (
                        <p className="text-ink-muted">
                            {q ? t('sites.no_match', { query }) : t('sites.empty_filter')}
                            {q && (
                                <>
                                    {' '}
                                    <button className="cursor-pointer text-ink underline underline-offset-4" onClick={() => setQuery('')}>
                                        {t('sites.clear_search')}
                                    </button>
                                </>
                            )}
                        </p>
                    ) : (
                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-x-12 gap-y-14 px-5">
                            {shown.map((site) => (
                                <SiteTile key={site.id} site={site} projects={projects} />
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </AppLayout>
    );
}

/** A filter in the sidebar; projects and "without project" also take dropped site cards. */
function FilterItem({ label, count, active, onSelect, onDropSite }: { label: string; count: number; active: boolean; onSelect: () => void; onDropSite?: (siteId: number) => void }) {
    const [over, setOver] = useState(false);
    const accepts = (e: React.DragEvent) => !!onDropSite && e.dataTransfer.types.includes(SITE_DRAG_TYPE);

    return (
        <button
            type="button"
            aria-pressed={active}
            onClick={onSelect}
            onDragOver={(e) => {
                if (!accepts(e)) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                setOver(true);
            }}
            onDragLeave={() => setOver(false)}
            onDrop={(e) => {
                setOver(false);
                if (!accepts(e)) return;
                e.preventDefault();
                onDropSite!(Number(e.dataTransfer.getData(SITE_DRAG_TYPE)));
            }}
            className={cn(
                'flex shrink-0 cursor-pointer items-baseline justify-between gap-3 border border-dashed border-transparent px-2 py-1.5 text-left text-sm whitespace-nowrap text-ink-muted hover:text-ink lg:whitespace-normal',
                active && 'bg-sheet font-medium text-ink',
                over && 'border-signal bg-signal/10 text-ink',
            )}
        >
            <span className="min-w-0 lg:truncate">{label}</span>
            <span className="font-mono text-xs tabular-nums text-ink-muted">{count}</span>
        </button>
    );
}

function EmptyState({ projects }: { projects: ProjectOption[] }) {
    return (
        <CropFrame className="mt-14 mx-5 grid max-w-xl gap-4 border bg-sheet p-10">
            <h2 className="text-xl font-semibold tracking-tight">{t('sites.empty_title')}</h2>
            <p className="max-w-[48ch] text-ink-muted">
                {t('sites.empty_text')}
            </p>
            <NewSiteDialog
                projects={projects}
                trigger={
                    <Button className="justify-self-start">
                        <Plus />
                        {t('sites.empty_cta')}
                    </Button>
                }
            />
        </CropFrame>
    );
}
