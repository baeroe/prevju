import * as React from 'react';
import { cn } from '@/lib/utils';

function Input({ className, ...props }: React.ComponentProps<'input'>) {
    return (
        <input
            data-slot="input"
            className={cn(
                'h-10 w-full min-w-0 border border-hairline bg-sheet px-3 text-base text-ink placeholder:text-ink-muted hover:border-ink-muted focus-visible:border-ink focus-visible:outline-none disabled:opacity-50 aria-invalid:border-danger md:text-sm',
                className,
            )}
            {...props}
        />
    );
}

export { Input };
