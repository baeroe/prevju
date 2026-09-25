import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';

async function copy(url: string, done: () => void) {
    try {
        await navigator.clipboard.writeText(url);
        toast('Link kopiert');
        done();
    } catch {
        toast.error('Kopieren nicht erlaubt. Link bitte markieren und von Hand kopieren.');
    }
}

function useCopied() {
    const [copied, setCopied] = useState(false);
    return [copied, () => (setCopied(true), setTimeout(() => setCopied(false), 1500))] as const;
}

export function CopyLinkIcon({ url }: { url: string }) {
    const [copied, flash] = useCopied();
    return (
        <Tooltip label="Link kopieren">
            <Button variant="ghost" size="icon-sm" aria-label="Link kopieren" onClick={() => copy(url, flash)}>
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
            {copied ? 'Kopiert' : 'Link kopieren'}
        </Button>
    );
}
