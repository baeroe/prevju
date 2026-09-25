import * as React from 'react';
import { cn } from '@/lib/utils';

/** Box with printer's crop marks outside its corners. The one decorative element (DESIGN.md). */
export function CropFrame({ className, children, ...props }: React.ComponentProps<'div'>) {
    return (
        <div className={cn('crop', className)} {...props}>
            <span className="crop-mark" aria-hidden />
            <span className="crop-mark" aria-hidden />
            <span className="crop-mark" aria-hidden />
            <span className="crop-mark" aria-hidden />
            {children}
        </div>
    );
}
