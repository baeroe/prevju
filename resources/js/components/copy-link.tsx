import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';
import { t } from '@/lib/i18n';

async function copy(url: string, done: () => void) {
    try {
        await navigator.clipboard.writeText(url);
        toast(t('common.link_copied'));
        done();
    } catch {
        toast.error(t('common.copy_failed'));
    }
}

function useCopied() {
    const [copied, setCopied] = useState(false);
    return [copied, () => (setCopied(true), setTimeout(() => setCopied(false), 1500))] as const;
}

export function CopyLinkIcon({ url }: { url: string }) {
    const [copied, flash] = useCopied();
    return (
        <Tooltip label={t('common.copy_link')}>
            <Button variant="ghost" size="icon-sm" aria-label={t('common.copy_link')} onClick={() => copy(url, flash)}>
                {copied ? <Check /> : <Copy />}
            </Button>
        </Tooltip>
    );
}

export function CopyLinkButton({ url }: { url: string }) {
    const [copied, flash] = useCopied();
    return (
        <Button onClick={() => copy(url, flash)}>
            {copied ? <Check /> : <Copy />}
            {copied ? t('common.copied') : t('common.copy_link')}
        </Button>
    );
}
