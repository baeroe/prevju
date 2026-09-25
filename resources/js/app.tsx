import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';

createInertiaApp({
    title: (title) => (title ? `${title} – prevju` : 'prevju'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        return pages[`./pages/${name}.tsx`] as never;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <TooltipProvider delayDuration={300}>
                <App {...props} />
                <Toaster />
            </TooltipProvider>,
        );
    },
    progress: { color: 'oklch(0.7 0.19 45)', showSpinner: false },
});
