import { Head, Link } from '@inertiajs/react';
import { FolderPlus, Layers, Lock, Search } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/app-layout';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { NewProjectDialog } from '@/components/new-site-dialog';
import { ProjectPreview } from '@/components/project-preview';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useIsHidden } from '@/lib/delete-with-undo';
import { shortUrl, siteCount, timeAgo } from '@/lib/format';
import { path, t } from '@/lib/i18n';
import type { ProjectCard } from '@/types';

export default function ProjectsIndex({ projects }: { projects: ProjectCard[] }) {
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const shown = q ? projects.filter((p) => p.name.toLowerCase().includes(q) || p.url.includes(q)) : projects;

    return (
        <AppLayout actions={projects.length > 0 && <NewProjectDialog />}>
            <Head title={t('projects.title')} />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <h1 className="flex items-baseline gap-3 text-3xl leading-none font-semibold tracking-tight">
                    {t('projects.title')}
                    {projects.length > 0 && <span className="font-mono text-base font-normal text-ink-muted">{projects.length}</span>}
                </h1>
                {projects.length > 3 && (
                    <label className="relative w-full sm:w-64">
                        <span className="sr-only">{t('projects.search_label')}</span>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" aria-hidden />
                        <Input type="search" value={query} onChange={(e) => setQuery(e.target.value)} placeholder={t('common.search')} className="pl-9" />
                    </label>
                )}
            </div>

            {projects.length === 0 ? (
                <CropFrame className="mx-5 mt-14 grid max-w-xl gap-4 border bg-sheet p-10">
                    <h2 className="text-xl font-semibold tracking-tight">{t('projects.empty_title')}</h2>
                    <p className="max-w-[48ch] text-ink-muted">{t('projects.empty_text')}</p>
                    <NewProjectDialog
                        trigger={
                            <Button className="justify-self-start">
                                <FolderPlus />
                                {t('projects.empty_cta')}
                            </Button>
                        }
                    />
                </CropFrame>
            ) : shown.length === 0 ? (
                <p className="mt-16 text-ink-muted">
                    {t('sites.no_match', { query })}{' '}
                    <button className="cursor-pointer text-ink underline underline-offset-4" onClick={() => setQuery('')}>
                        {t('sites.clear_search')}
                    </button>
                </p>
            ) : (
                <ul className="mt-12 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5">
                    {shown.map((project) => (
                        <ProjectTile key={project.id} project={project} />
                    ))}
                </ul>
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
            <Link href={path(`/projects/${project.id}`)} className="block" aria-label={t('sites.open_project', { name: project.name })}>
                <CropFrame>
                    <ProjectPreview sites={project.sites} paused={paused} />
                </CropFrame>
            </Link>
            <div className="mt-8 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h2 className="truncate font-medium">
                        <Link href={path(`/projects/${project.id}`)} className="hover:underline hover:underline-offset-4">
                            {project.name}
                        </Link>
                    </h2>
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
                        {t('common.password')}
                    </span>
                )}
                <span>{t('common.changed', { time: timeAgo(project.updated_at) })}</span>
            </p>
        </li>
    );
}
