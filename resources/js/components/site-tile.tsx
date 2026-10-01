import { Link } from '@inertiajs/react';
import { ExternalLink, Lock, X } from 'lucide-react';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { ProjectMenu } from '@/components/project-menu';
import { SitePreview } from '@/components/site-preview';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';
import { useIsHidden } from '@/lib/delete-with-undo';
import { fileCount, shortUrl, timeAgo } from '@/lib/format';
import { SITE_DRAG_TYPE } from '@/lib/move-site';
import type { ProjectOption, SiteCard } from '@/types';
import { path, t } from '@/lib/i18n';

type Props = {
    site: SiteCard;
    /** Projects to move the site to: shows the project label and makes the card draggable. */
    projects?: ProjectOption[];
    /** Shows a remove button, on a project's page. */
    onRemove?: () => void;
};

export function SiteTile({ site, projects, onRemove }: Props) {
    if (useIsHidden(`site:${site.id}`)) return null;
    const movable = !!projects?.length;

    return (
        <li
            className="group min-w-0"
            draggable={movable}
            onDragStart={
                movable
                    ? (e) => {
                          e.dataTransfer.setData(SITE_DRAG_TYPE, String(site.id));
                          e.dataTransfer.effectAllowed = 'move';
                          e.dataTransfer.setDragImage(e.currentTarget, 24, 24);
                      }
                    : undefined
            }
        >
            <Link href={path(`/sites/${site.id}`)} className="block" aria-label={t('common.open_name', { name: site.name })}>
                <CropFrame>
                    <SitePreview site={site} />
                </CropFrame>
            </Link>
            <div className="mt-8 flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="truncate font-medium">
                        <Link href={path(`/sites/${site.id}`)} className="hover:underline hover:underline-offset-4">
                            {site.name}
                        </Link>
                    </h3>
                    <p className="mt-0.5 truncate font-mono text-xs text-ink-muted">{shortUrl(site.url)}</p>
                </div>
                <div className="-mt-1.5 -mr-2 flex shrink-0">
                    <CopyLinkIcon url={site.url} />
                    {onRemove && (
                        <Tooltip label={t('project.remove_site')}>
                            <Button variant="ghost" size="icon-sm" aria-label={t('project.remove_site_name', { name: site.name })} className="hover:text-danger" onClick={onRemove}>
                                <X />
                            </Button>
                        </Tooltip>
                    )}
                    <Tooltip label={t('common.open_new_tab')}>
                        <Button variant="ghost" size="icon-sm" asChild>
                            <a href={site.url} target="_blank" rel="noreferrer" aria-label={t('common.open_name_new_tab', { name: site.name })}>
                                <ExternalLink />
                            </a>
                        </Button>
                    </Tooltip>
                </div>
            </div>
            {movable && (
                <div className="mt-2 flex min-w-0">
                    <ProjectMenu site={site} projects={projects} />
                </div>
            )}
            <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-muted">
                <span>{fileCount(site.file_count)}</span>
                {site.has_password && (
                    <span className="inline-flex items-center gap-1">
                        <Lock className="size-3" aria-hidden />
                        {t('common.password')}
                    </span>
                )}
                <span>{t('common.changed', { time: timeAgo(site.updated_at) })}</span>
            </p>
        </li>
    );
}
