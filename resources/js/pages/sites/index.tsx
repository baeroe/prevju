import { Head, Link } from '@inertiajs/react';
import { ExternalLink, Lock, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/app-layout';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { NewSiteDialog } from '@/components/new-site-dialog';
import { SitePreview } from '@/components/site-preview';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tooltip } from '@/components/ui/tooltip';
import { useIsHidden } from '@/lib/delete-with-undo';
import { fileCount, shortUrl, timeAgo } from '@/lib/format';
import type { SiteCard } from '@/types';

export default function SitesIndex({ sites }: { sites: SiteCard[] }) {
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const shown = q ? sites.filter((s) => s.name.toLowerCase().includes(q) || s.url.includes(q)) : sites;

    return (
        <AppLayout actions={sites.length > 0 && <NewSiteDialog />}>
            <Head title="Sites" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <h1 className="flex items-baseline gap-3 text-3xl leading-none font-semibold tracking-tight">
                    Sites
                    {sites.length > 0 && <span className="font-mono text-base font-normal text-ink-muted">{sites.length}</span>}
                </h1>
                {sites.length > 3 && (
                    <label className="relative w-full sm:w-64">
                        <span className="sr-only">Sites durchsuchen</span>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden />
                        <Input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Suchen…" className="pl-9" />
                    </label>
                )}
            </div>

            {sites.length === 0 ? (
                <EmptyState />
            ) : shown.length === 0 ? (
                <p className="mt-16 text-ink-muted">
                    Keine Site passt zu „{query}“.{' '}
                    <button className="cursor-pointer text-ink underline underline-offset-4" onClick={() => setQuery('')}>
                        Suche leeren
                    </button>
                </p>
            ) : (
                <ul className="mt-12 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5">
                    {shown.map((site) => (
                        <Card key={site.id} site={site} />
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}

function Card({ site }: { site: SiteCard }) {
    if (useIsHidden(`site:${site.id}`)) return null;

    return (
        <li className="group min-w-0">
            <Link href={`/sites/${site.id}`} className="block" aria-label={`${site.name} öffnen`}>
                <CropFrame>
                    <SitePreview site={site} />
                </CropFrame>
            </Link>
            <div className="mt-8 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h2 className="truncate font-medium">
                        <Link href={`/sites/${site.id}`} className="hover:underline hover:underline-offset-4">
                            {site.name}
                        </Link>
                    </h2>
                    <p className="mt-0.5 truncate font-mono text-xs text-ink-muted">{shortUrl(site.url)}</p>
                </div>
                <div className="-mt-1.5 -mr-2 flex shrink-0">
                    <CopyLinkIcon url={site.url} />
                    <Tooltip label="Im neuen Tab öffnen">
                        <Button variant="ghost" size="icon-sm" asChild>
                            <a href={site.url} target="_blank" rel="noreferrer" aria-label={`${site.name} im neuen Tab öffnen`}>
                                <ExternalLink />
                            </a>
                        </Button>
                    </Tooltip>
                </div>
            </div>
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                <span>{fileCount(site.file_count)}</span>
                {site.has_password && (
                    <span className="inline-flex items-center gap-1">
                        <Lock className="size-3" aria-hidden />
                        Passwort
                    </span>
                )}
                <span>geändert {timeAgo(site.updated_at)}</span>
            </p>
        </li>
    );
}

function EmptyState() {
    return (
        <CropFrame className="mt-14 mx-5 grid max-w-xl gap-4 border bg-sheet p-10">
            <h2 className="text-xl font-semibold tracking-tight">Noch keine Entwürfe</h2>
            <p className="max-w-[48ch] text-ink-muted">
                Leg eine Site an, zieh den Ordner mit deinem HTML-Entwurf hinein und schick den Link an deinen Kunden.
            </p>
            <NewSiteDialog
                trigger={
                    <Button className="justify-self-start">
                        <Plus />
                        Erste Site anlegen
                    </Button>
                }
            />
        </CropFrame>
    );
}
