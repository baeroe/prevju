import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Lock, LockOpen, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { AppLayout } from '@/components/app-layout';
import { CopyLinkButton } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { Dropzone } from '@/components/dropzone';
import { SitePreview } from '@/components/site-preview';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip } from '@/components/ui/tooltip';
import { deleteWithUndo, useIsHidden } from '@/lib/delete-with-undo';
import { fileCount } from '@/lib/format';
import type { SiteDetail } from '@/types';

export default function SiteShow({ site }: { site: SiteDetail }) {
    const empty = site.file_count === 0;

    return (
        <AppLayout>
            <Head title={site.name} />

            <Link href="/sites" className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft className="size-4" aria-hidden />
                Alle Sites
            </Link>
            <h1 className="mt-4 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{site.name}</h1>

            <div className="mt-6 flex flex-wrap items-stretch gap-2">
                <a
                    href={site.url}
                    target="_blank"
                    rel="noreferrer"
                    className="flex h-10 min-w-0 flex-1 basis-72 items-center gap-2 border bg-sheet px-3 font-mono text-sm hover:border-ink"
                >
                    {site.has_password ? <Lock className="size-4 shrink-0 text-ink-muted" aria-label="Passwortgeschützt" /> : null}
                    <span className="truncate">{site.url}</span>
                    <ExternalLink className="ml-auto size-4 shrink-0 text-ink-muted" aria-hidden />
                </a>
                <CopyLinkButton url={site.url} />
            </div>

            <div className="mt-14 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section aria-label="Vorschau und Upload" className="grid content-start gap-8 px-5">
                    {!empty && (
                        <CropFrame>
                            <SitePreview site={site} />
                        </CropFrame>
                    )}
                    <Dropzone siteId={site.id} empty={empty} />
                </section>

                <div className="grid content-start gap-12">
                    <FileList site={site} />
                    <NameForm site={site} />
                    <PasswordForm site={site} />
                    <section className="grid gap-3 border-t pt-8">
                        <h2 className="font-medium">Site löschen</h2>
                        <p className="text-sm text-ink-muted">Der Link funktioniert danach nicht mehr. Alle Dateien werden entfernt.</p>
                        <Button
                            variant="danger"
                            className="justify-self-start"
                            onClick={() =>
                                router.visit('/sites', {
                                    onSuccess: () => deleteWithUndo({ key: `site:${site.id}`, url: `/sites/${site.id}`, message: `„${site.name}“ gelöscht` }),
                                })
                            }
                        >
                            <Trash2 />
                            Site löschen
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
                Dateien
                <span className="font-mono text-xs font-normal text-ink-muted">{fileCount(site.file_count)}</span>
            </h2>
            {site.files.length === 0 ? (
                <p className="text-sm text-ink-muted">Noch nichts hochgeladen. Die Seite mit index.html wird als Startseite ausgeliefert.</p>
            ) : (
                <ul className="max-h-96 overflow-y-auto border-t">
                    {site.files.map((path) => (
                        <FileRow key={path} site={site} path={path} />
                    ))}
                </ul>
            )}
        </section>
    );
}

function FileRow({ site, path }: { site: SiteDetail; path: string }) {
    const key = `file:${site.id}:${path}`;
    if (useIsHidden(key)) return null;

    return (
        <li className="group/row flex items-center gap-2 border-b py-1 pl-1 last:border-b-0">
            <a href={site.url + path} target="_blank" rel="noreferrer" className="min-w-0 flex-1 truncate font-mono text-xs hover:underline hover:underline-offset-4">
                {path}
            </a>
            <Tooltip label="Datei löschen">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label={`${path} löschen`}
                    className="text-ink-muted hover:text-danger"
                    onClick={() => deleteWithUndo({ key, url: `/sites/${site.id}/files?path=${encodeURIComponent(path)}`, message: `${path} gelöscht` })}
                >
                    <Trash2 />
                </Button>
            </Tooltip>
        </li>
    );
}

function NameForm({ site }: { site: SiteDetail }) {
    const form = useForm({ name: site.name });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(`/sites/${site.id}`, { preserveScroll: true, onSuccess: () => toast('Name gespeichert') });
            }}
        >
            <Label htmlFor="site-name" className="font-medium">
                Name
            </Label>
            <div className="flex gap-2">
                <Input id="site-name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoComplete="off" required />
                <Button type="submit" variant="outline" loading={form.processing}>
                    Speichern
                </Button>
            </div>
            {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
        </form>
    );
}

function PasswordForm({ site }: { site: SiteDetail }) {
    const form = useForm({ password: '' });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(`/sites/${site.id}`, {
                    preserveScroll: true,
                    onSuccess: () => (form.reset(), toast(site.has_password ? 'Passwort geändert' : 'Passwort gesetzt')),
                });
            }}
        >
            {/* lets password managers attribute the password to this site */}
            <input type="text" autoComplete="username" value={site.url} readOnly hidden />
            <Label htmlFor="site-password" className="font-medium">
                Passwort
            </Label>
            <p className="flex items-center gap-2 text-sm text-ink-muted">
                {site.has_password ? <Lock className="size-4" aria-hidden /> : <LockOpen className="size-4" aria-hidden />}
                {site.has_password ? 'Geschützt. Kunden müssen das Passwort eingeben.' : 'Offen. Jeder mit dem Link sieht die Site.'}
            </p>
            <div className="flex gap-2">
                <Input
                    id="site-password"
                    name="password"
                    required
                    type="password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    placeholder={site.has_password ? 'Neues Passwort…' : 'Passwort festlegen…'}
                    autoComplete="new-password"
                />
                <Button type="submit" variant="outline" loading={form.processing}>
                    {site.has_password ? 'Ändern' : 'Setzen'}
                </Button>
            </div>
            {site.has_password && (
                <button
                    type="button"
                    className="-my-2 cursor-pointer justify-self-start py-2 text-sm text-ink-muted underline underline-offset-4 hover:text-danger"
                    onClick={() =>
                        router.patch(`/sites/${site.id}`, { clear_password: true }, { preserveScroll: true, onSuccess: () => toast('Passwort entfernt') })
                    }
                >
                    Passwort entfernen
                </button>
            )}
        </form>
    );
}
