import { cva, type VariantProps } from 'class-variance-authority';
import { Loader2 } from 'lucide-react';
import { Slot } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 text-sm font-medium whitespace-nowrap select-none disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4',
    {
        variants: {
            variant: {
                // signal orange with ink text: white on orange fails contrast (DESIGN.md)
                default: 'bg-signal text-signal-ink hover:bg-ink hover:text-sheet',
                outline: 'border border-ink bg-sheet text-ink hover:bg-ink hover:text-sheet',
                ghost: 'text-ink hover:bg-ink/6',
                danger: 'border border-danger bg-sheet text-danger hover:bg-danger hover:text-sheet',
            },
            size: {
                default: 'h-10 px-4',
                sm: 'h-8 px-3',
                icon: 'size-10',
                'icon-sm': 'size-8 pointer-coarse:size-11',
            },
        },
        defaultVariants: { variant: 'default', size: 'default' },
    },
);

function Button({
    className,
    variant,
    size,
    asChild = false,
    loading = false,
    disabled,
    children,
    ...props
}: React.ComponentProps<'button'> & VariantProps<typeof buttonVariants> & { asChild?: boolean; loading?: boolean }) {
    const Comp = asChild ? Slot.Root : 'button';

    return (
        <Comp data-slot="button" className={cn(buttonVariants({ variant, size, className }))} disabled={disabled || loading} {...props}>
            {asChild ? (
                children
            ) : (
                <>
                    {loading && <Loader2 className="animate-spin" aria-hidden />}
                    {children}
                </>
            )}
        </Comp>
    );
}

export { Button, buttonVariants };
