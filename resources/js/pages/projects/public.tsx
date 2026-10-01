import { Head } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { CropFrame } from '@/components/crop-frame';
import { SitePreview } from '@/components/site-preview';
import { timeAgo } from '@/lib/format';
import type { PublicSite } from '@/types';
import { t } from '@/lib/i18n';
import { LocaleSwitch } from '@/components/locale-switch';

export default function ProjectPublic({ project, sites }: { project: { name: string }; sites: PublicSite[] }) {
    return (
        <main className="relative mx-auto max-w-6xl px-6 pt-16 pb-24">
            <LocaleSwitch className="absolute top-4 right-6" />
            <Head title={project.name}>
                <meta name="robots" content="noindex" />
            </Head>

            <p className="text-sm text-ink-muted">{t('client.intro_project')}</p>
            <h1 className="mt-1 text-3xl leading-tight font-semibold tracking-tight text-balance break-words">{project.name}</h1>

            {sites.length === 0 ? (
                <p className="mt-12 text-ink-muted">{t('client.empty')}</p>
            ) : (
                <ul className="mt-12 grid grid-cols-[repeat(auto-fill,minmax(min(100%,17rem),1fr))] gap-x-12 gap-y-14 px-5">
                    {sites.map((site) => (
                        <li key={site.id} className="group min-w-0">
                            <a href={site.url} className="block">
                                <CropFrame>{site.preview_url ? <SitePreview site={{ ...site, preview_url: site.preview_url }} /> : <Locked />}</CropFrame>
                                <h2 className="mt-8 truncate font-medium group-hover:underline group-hover:underline-offset-4">{site.name}</h2>
                            </a>
                            <p className="mt-1 text-xs text-ink-muted">{t('common.changed', { time: timeAgo(site.updated_at) })}</p>
                        </li>
                    ))}
                </ul>
            )}
        </main>
    );
}

function Locked() {
    return (
        <div className="grid aspect-[16/10] place-items-center content-center gap-2 border bg-sheet text-sm text-ink-muted">
            <Lock className="size-5" aria-hidden />
            {t('client.own_password')}
        </div>
    );
}
