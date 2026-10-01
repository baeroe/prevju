import { DropdownMenu as Primitive } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

const DropdownMenu = Primitive.Root;
const DropdownMenuTrigger = Primitive.Trigger;

function DropdownMenuContent({ className, ...props }: React.ComponentProps<typeof Primitive.Content>) {
    return (
        <Primitive.Portal>
            <Primitive.Content sideOffset={4} align="start" className={cn('z-50 grid min-w-44 border border-ink bg-sheet p-1 text-sm', className)} {...props} />
        </Primitive.Portal>
    );
}

function DropdownMenuLabel({ className, ...props }: React.ComponentProps<typeof Primitive.Label>) {
    return <Primitive.Label className={cn('px-2 py-1 font-mono text-xs text-ink-muted', className)} {...props} />;
}

function DropdownMenuItem({ className, ...props }: React.ComponentProps<typeof Primitive.Item>) {
    return (
        <Primitive.Item
            className={cn('flex cursor-pointer items-center justify-between gap-3 px-2 py-1.5 outline-none select-none data-[highlighted]:bg-paper', className)}
            {...props}
        />
    );
}

export { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger };
