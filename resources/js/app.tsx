import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { getLocale, initI18n } from '@/lib/i18n';

createInertiaApp({
    title: (title) => (title ? `${title} – prevju` : 'prevju'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        return pages[`./pages/${name}.tsx`] as never;
    },
    setup({ el, App, props }) {
        initI18n(props.initialPage.props as never);
        // translations are set once per page load: a visit that lands in another language (e.g. an intended URL after login) reloads
        router.on('navigate', (event) => {
            if (event.detail.page.props.locale !== getLocale()) window.location.reload();
        });
        createRoot(el).render(
            <TooltipProvider delayDuration={300}>
                <App {...props} />
                <Toaster />
            </TooltipProvider>,
        );
    },
    progress: { color: 'oklch(0.7 0.19 45)', showSpinner: false },
});
