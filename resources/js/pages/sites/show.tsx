import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { AppLayout } from '@/components/app-layout';
import { CropFrame } from '@/components/crop-frame';
import { Dropzone } from '@/components/dropzone';
import { LinkBar } from '@/components/link-bar';
import { NameForm, PasswordForm } from '@/components/settings-forms';
import { SitePreview } from '@/components/site-preview';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Tooltip } from '@/components/ui/tooltip';
import { deleteWithUndo, useIsHidden } from '@/lib/delete-with-undo';
import { fileCount } from '@/lib/format';
import type { ProjectOption, SiteDetail } from '@/types';
import { path, t } from '@/lib/i18n';

export default function SiteShow({ site, projects }: { site: SiteDetail; projects: ProjectOption[] }) {
    const empty = site.file_count === 0;
    const project = projects.find((p) => p.id === site.project_id);

    return (
        <AppLayout>
            <Head title={site.name} />

            <Link href={path(project ? `/projects/${project.id}` : '/sites')} className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft className="size-4" aria-hidden />
                {project ? project.name : t('common.all_sites')}
            </Link>
            <h1 className="mt-4 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{site.name}</h1>

            <LinkBar url={site.url} locked={site.has_password} />

            <div className="mt-14 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section aria-label={t('site.preview_and_upload')} className="grid content-start gap-8 px-5">
                    {!empty && (
                        <CropFrame>
                            <SitePreview site={site} />
                        </CropFrame>
                    )}
                    <Dropzone siteId={site.id} empty={empty} />
                </section>

                <div className="grid content-start gap-12">
                    <FileList site={site} />
                    <NameForm endpoint={path(`/sites/${site.id}`)} id="site" name={site.name} />
                    <ProjectSelect site={site} projects={projects} />
                    <PasswordForm
                        endpoint={path(`/sites/${site.id}`)}
                        id="site"
                        url={site.url}
                        hasPassword={site.has_password}
                        openHint={t('site.open_hint')}
                        lockedHint={t('site.locked_hint')}
                    />
                    <section className="grid gap-3 border-t pt-8">
                        <h2 className="font-medium">{t('site.delete')}</h2>
                        <p className="text-sm text-ink-muted">{t('site.delete_hint')}</p>
                        <Button
                            variant="danger"
                            className="justify-self-start"
                            onClick={() =>
                                router.visit(path('/sites'), {
                                    onSuccess: () => deleteWithUndo({ key: `site:${site.id}`, url: path(`/sites/${site.id}`), message: t('common.deleted', { name: site.name }) }),
                                })
                            }
                        >
                            <Trash2 />
                            {t('site.delete')}
                        </Button>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function FileList({ site }: { site: SiteDetail }) {
    return (
        <section className="grid gap-3">
            <h2 className="flex items-baseline justify-between font-medium">
                {t('files.title')}
                <span className="font-mono text-xs font-normal text-ink-muted">{fileCount(site.file_count)}</span>
            </h2>
            {site.files.length === 0 ? (
                <p className="text-sm text-ink-muted">{t('files.empty')}</p>
            ) : (
                <ul className="max-h-96 overflow-y-auto border-t">
                    {site.files.map((file) => (
                        <FileRow key={file} site={site} file={file} />
                    ))}
                </ul>
            )}
        </section>
    );
}

function FileRow({ site, file }: { site: SiteDetail; file: string }) {
    const key = `file:${site.id}:${file}`;
    if (useIsHidden(key)) return null;

    return (
        <li className="group/row flex items-center gap-2 border-b py-1 pl-1 last:border-b-0">
            <a href={site.url + file} target="_blank" rel="noreferrer" className="min-w-0 flex-1 truncate font-mono text-xs hover:underline hover:underline-offset-4">
                {file}
            </a>
            <Tooltip label={t('files.delete')}>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={t('files.delete_name', { path: file })}
                    className="text-ink-muted hover:text-danger"
                    onClick={() => deleteWithUndo({ key, url: path(`/sites/${site.id}/files?path=${encodeURIComponent(file)}`), message: t('files.deleted', { path: file }) })}
                >
                    <Trash2 />
                </Button>
            </Tooltip>
        </li>
    );
}

function ProjectSelect({ site, projects }: { site: SiteDetail; projects: ProjectOption[] }) {
    if (projects.length === 0) return null;

    return (
        <section className="grid gap-3 border-t pt-8">
            <Label htmlFor="site-project" className="font-medium">
                {t('site.project')}
            </Label>
            <p className="text-sm text-ink-muted">{t('site.project_hint')}</p>
            <select
                id="site-project"
                value={site.project_id ?? ''}
                onChange={(e) => {
                    const value = e.target.value;
                    router.patch(
                        path(`/sites/${site.id}`),
                        { project_id: value ? Number(value) : null },
                        { preserveScroll: true, onSuccess: () => toast(value ? t('site.project_set') : t('site.project_cleared')) },
                    );
                }}
                className="h-10 w-full min-w-0 border border-hairline bg-sheet px-3 text-base text-ink hover:border-ink-muted focus-visible:border-ink focus-visible:outline-none md:text-sm"
            >
                <option value="">{t('site.no_project')}</option>
                {projects.map((p) => (
                    <option key={p.id} value={p.id}>
                        {p.name}
                    </option>
                ))}
            </select>
        </section>
    );
}
