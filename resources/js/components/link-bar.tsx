import { ExternalLink, Lock } from 'lucide-react';
import { CopyLinkButton } from '@/components/copy-link';
import { t } from '@/lib/i18n';

/** The public link as a field to open, plus a copy button. */
export function LinkBar({ url, locked }: { url: string; locked: boolean }) {
    return (
        <div className="mt-6 flex flex-wrap items-stretch gap-2">
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className="flex h-10 min-w-0 flex-1 basis-72 items-center gap-2 border bg-sheet px-3 font-mono text-sm hover:border-ink"
            >
                {locked ? <Lock className="size-4 shrink-0 text-ink-muted" aria-label={t('common.password_protected')} /> : null}
                <span className="truncate">{url}</span>
                <ExternalLink className="ml-auto size-4 shrink-0 text-ink-muted" aria-hidden />
            </a>
            <CopyLinkButton url={url} />
        </div>
    );
}
