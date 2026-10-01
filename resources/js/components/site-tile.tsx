import { Link } from '@inertiajs/react';
import { ExternalLink, Lock } from 'lucide-react';
import { CopyLinkIcon } from '@/components/copy-link';
import { CropFrame } from '@/components/crop-frame';
import { SitePreview } from '@/components/site-preview';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';
import { useIsHidden } from '@/lib/delete-with-undo';
import { fileCount, shortUrl, timeAgo } from '@/lib/format';
import type { SiteCard } from '@/types';

export function SiteTile({ site }: { site: SiteCard }) {
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
                    <h3 className="truncate font-medium">
                        <Link href={`/sites/${site.id}`} className="hover:underline hover:underline-offset-4">
                            {site.name}
                        </Link>
                    </h3>
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
