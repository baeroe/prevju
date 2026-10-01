import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { AppLayout } from '@/components/app-layout';
import { LinkBar } from '@/components/link-bar';
import { NewSiteDialog } from '@/components/new-site-dialog';
import { NameForm, PasswordForm } from '@/components/settings-forms';
import { SiteTile } from '@/components/site-tile';
import { Button } from '@/components/ui/button';
import { deleteWithUndo } from '@/lib/delete-with-undo';
import { siteCount } from '@/lib/format';
import type { ProjectCard } from '@/types';

export default function ProjectShow({ project }: { project: ProjectCard }) {
    return (
        <AppLayout actions={<NewSiteDialog projectId={project.id} />}>
            <Head title={project.name} />

            <Link href="/sites" className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft className="size-4" aria-hidden />
                Alle Sites
            </Link>
            <p className="mt-4 text-sm text-ink-muted">Projekt</p>
            <h1 className="mt-1 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{project.name}</h1>
            <LinkBar url={project.url} locked={project.has_password} />

            <div className="mt-14 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section aria-labelledby="project-sites" className="grid content-start gap-8">
                    <h2 id="project-sites" className="flex items-baseline justify-between font-medium">
                        Sites
                        <span className="font-mono text-xs font-normal text-ink-muted">{siteCount(project.site_count)}</span>
                    </h2>
                    {project.sites.length === 0 ? (
                        <p className="text-sm text-ink-muted">
                            Noch keine Sites. Leg oben eine neue an oder ordne eine bestehende auf ihrer Seite diesem Projekt zu.
                        </p>
                    ) : (
                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] gap-x-12 gap-y-14 px-5">
                            {project.sites.map((site) => (
                                <SiteTile key={site.id} site={site} />
                            ))}
                        </ul>
                    )}
                </section>

                <div className="grid content-start gap-12">
                    <NameForm endpoint={`/projects/${project.id}`} id="project" name={project.name} />
                    <PasswordForm
                        endpoint={`/projects/${project.id}`}
                        id="project"
                        url={project.url}
                        hasPassword={project.has_password}
                        openHint="Offen. Jeder mit dem Link sieht die Liste. Sites mit eigenem Passwort bleiben geschützt."
                        lockedHint="Geschützt. Das Passwort öffnet alle Sites des Projekts."
                    />
                    <section className="grid gap-3 border-t pt-8">
                        <h2 className="font-medium">Projekt löschen</h2>
                        <p className="text-sm text-ink-muted">
                            Der Projekt-Link funktioniert danach nicht mehr. Die Sites bleiben erhalten und stehen wieder einzeln in der Übersicht.
                        </p>
                        <Button
                            variant="danger"
                            className="justify-self-start"
                            onClick={() =>
                                router.visit('/sites', {
                                    onSuccess: () =>
                                        deleteWithUndo({ key: `project:${project.id}`, url: `/projects/${project.id}`, message: `„${project.name}“ gelöscht` }),
                                })
                            }
                        >
                            <Trash2 />
                            Projekt löschen
                        </Button>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
