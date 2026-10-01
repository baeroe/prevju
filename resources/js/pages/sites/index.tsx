import { Head, Link } from '@inertiajs/react';
import { Layers, Lock, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/app-layout';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { NewProjectDialog, NewSiteDialog } from '@/components/new-site-dialog';
import { ProjectPreview } from '@/components/project-preview';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useIsHidden } from '@/lib/delete-with-undo';
import { shortUrl, siteCount, timeAgo } from '@/lib/format';
import type { ProjectCard, SiteCard } from '@/types';

const GRID = 'mt-6 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5';

export default function SitesIndex({ projects, sites }: { projects: ProjectCard[]; sites: SiteCard[] }) {
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const matches = (x: { name: string; url: string }) => !q || x.name.toLowerCase().includes(q) || x.url.includes(q);
    const shownProjects = projects.filter(matches);
    const shownSites = sites.filter(matches);
    const total = sites.length + projects.reduce((n, p) => n + p.site_count, 0);
    const empty = projects.length === 0 && sites.length === 0;

    return (
        <AppLayout
            actions={
                !empty && (
                    <>
                        <NewProjectDialog />
                        <NewSiteDialog />
                    </>
                )
            }
        >
            <Head title="Sites" />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <h1 className="flex items-baseline gap-3 text-3xl leading-none font-semibold tracking-tight">
                    Sites
                    {total > 0 && <span className="font-mono text-base font-normal text-ink-muted">{total}</span>}
                </h1>
                {projects.length + sites.length > 3 && (
                    <label className="relative w-full sm:w-64">
                        <span className="sr-only">Sites und Projekte durchsuchen</span>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden />
                        <Input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Suchen…" className="pl-9" />
                    </label>
                )}
            </div>

            {empty ? (
                <EmptyState />
            ) : shownProjects.length + shownSites.length === 0 ? (
                <p className="mt-16 text-ink-muted">
                    Nichts passt zu „{query}“.{' '}
                    <button className="cursor-pointer text-ink underline underline-offset-4" onClick={() => setQuery('')}>
                        Suche leeren
                    </button>
                </p>
            ) : (
                <>
                    {shownProjects.length > 0 && (
                        <section aria-labelledby="projects-heading" className="mt-12">
                            <h2 id="projects-heading" className="font-medium text-ink-muted">
                                Projekte
                            </h2>
                            <ul className={GRID}>
                                {shownProjects.map((project) => (
                                    <ProjectTile key={project.id} project={project} />
                                ))}
                            </ul>
                        </section>
                    )}
                    {shownSites.length > 0 && (
                        <section aria-labelledby="sites-heading" className="mt-12">
                            <h2 id="sites-heading" className={projects.length > 0 ? 'font-medium text-ink-muted' : 'sr-only'}>
                                Sites ohne Projekt
                            </h2>
                            <ul className={GRID}>
                                {shownSites.map((site) => (
                                    <SiteTile key={site.id} site={site} />
                                ))}
                            </ul>
                        </section>
                    )}
                </>
            )}
        </AppLayout>
    );
}

function ProjectTile({ project }: { project: ProjectCard }) {
    const [paused, setPaused] = useState(false);
    if (useIsHidden(`project:${project.id}`)) return null;

    return (
        // focus events bubble in React, so tabbing to the card's links pauses the slider too
        <li
            className="group min-w-0"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
        >
            <Link href={`/projects/${project.id}`} className="block" aria-label={`Projekt ${project.name} öffnen`}>
                <CropFrame>
                    <ProjectPreview sites={project.sites} paused={paused} />
                </CropFrame>
            </Link>
            <div className="mt-8 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="truncate font-medium">
                        <Link href={`/projects/${project.id}`} className="hover:underline hover:underline-offset-4">
                            {project.name}
                        </Link>
                    </h3>
                    <p className="mt-0.5 truncate font-mono text-xs text-ink-muted">{shortUrl(project.url)}</p>
                </div>
                <div className="-mt-1.5 -mr-2 flex shrink-0">
                    <CopyLinkIcon url={project.url} />
                </div>
            </div>
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                <span className="inline-flex items-center gap-1">
                    <Layers className="size-3" aria-hidden />
                    {siteCount(project.site_count)}
                </span>
                {project.has_password && (
                    <span className="inline-flex items-center gap-1">
                        <Lock className="size-3" aria-hidden />
                        Passwort
                    </span>
                )}
                <span>geändert {timeAgo(project.updated_at)}</span>
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
