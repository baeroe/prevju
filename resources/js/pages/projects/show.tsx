import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { AppLayout } from '@/components/app-layout';
import { LinkBar } from '@/components/link-bar';
import { NewSiteDialog } from '@/components/new-site-dialog';
import { NameForm, PasswordForm } from '@/components/settings-forms';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { deleteWithUndo } from '@/lib/delete-with-undo';
import { moveSite } from '@/lib/move-site';
import { siteCount } from '@/lib/format';
import type { ProjectCard, ProjectOption } from '@/types';
import { path, t } from '@/lib/i18n';

export default function ProjectShow({ project, projects }: { project: ProjectCard; projects: ProjectOption[] }) {
    return (
        <AppLayout>
            <Head title={project.name} />

            <Link href={path('/projects')} className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft className="size-4" aria-hidden />
                {t('projects.all')}
            </Link>
            <p className="mt-4 text-sm text-ink-muted">{t('project.label')}</p>
            <h1 className="mt-1 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{project.name}</h1>
            <LinkBar url={project.url} locked={project.has_password} />

            <div className="mt-14 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section aria-labelledby="project-sites" className="grid content-start gap-8">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="project-sites" className="flex items-baseline gap-3 font-medium">
                            {t('project.sites')}
                            <span className="font-mono text-xs font-normal text-ink-muted">{siteCount(project.site_count)}</span>
                        </h2>
                        <NewSiteDialog
                            projects={projects}
                            projectId={project.id}
                            trigger={
                                <Button variant="outline" size="sm">
                                    <Plus />
                                    {t('project.add_site')}
                                </Button>
                            }
                        />
                    </div>
                    {project.sites.length === 0 ? (
                        <p className="text-sm text-ink-muted">
                            {t('project.no_sites')}
                        </p>
                    ) : (
                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-x-12 gap-y-14 px-5">
                            {project.sites.map((site) => (
                                <SiteTile key={site.id} site={site} onRemove={() => moveSite(site, null, [])} />
                            ))}
                        </ul>
                    )}
                </section>

                <div className="grid content-start gap-12">
                    <NameForm endpoint={path(`/projects/${project.id}`)} id="project" name={project.name} />
                    <PasswordForm
                        endpoint={path(`/projects/${project.id}`)}
                        id="project"
                        url={project.url}
                        hasPassword={project.has_password}
                        openHint={t('project.open_hint')}
                        lockedHint={t('project.locked_hint')}
                    />
                    <section className="grid gap-3 border-t pt-8">
                        <h2 className="font-medium">{t('project.delete')}</h2>
                        <p className="text-sm text-ink-muted">
                            {t('project.delete_hint')}
                        </p>
                        <Button
                            variant="danger"
                            className="justify-self-start"
                            onClick={() =>
                                router.visit(path('/projects'), {
                                    onSuccess: () =>
                                        deleteWithUndo({ key: `project:${project.id}`, url: path(`/projects/${project.id}`), message: t('common.deleted', { name: project.name }) }),
                                })
                            }
                        >
                            <Trash2 />
                            {t('project.delete')}
                        </Button>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
