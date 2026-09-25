import { Tooltip as TooltipPrimitive } from 'radix-ui';
import * as React from 'react';

const TooltipProvider = TooltipPrimitive.Provider;

/** Wraps a single trigger element. The label doubles as visible hint; give the trigger its own aria-label. */
function Tooltip({ label, children }: { label: string; children: React.ReactElement }) {
    return (
        <TooltipPrimitive.Root>
            <TooltipPrimitive.Trigger asChild>{children}</TooltipPrimitive.Trigger>
            <TooltipPrimitive.Portal>
                <TooltipPrimitive.Content sideOffset={6} className="z-50 bg-ink px-2 py-1 text-xs text-sheet">
                    {label}
                </TooltipPrimitive.Content>
            </TooltipPrimitive.Portal>
        </TooltipPrimitive.Root>
    );
}

export { Tooltip, TooltipProvider };
