import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import * as React from 'react';
import { Logo } from '@/components/logo';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';
import { path, t } from '@/lib/i18n';
import { LocaleSwitch } from '@/components/locale-switch';

/** Projects and sites; on phones it moves to its own row under the header. */
function MainNav({ className }: { className?: string }) {
    const current = usePage().url.replace(/^\/(en|de)/, '');
    const items = [
        ['/projects', t('nav.projects')],
        ['/sites', t('nav.sites')],
    ];

    return (
        <nav aria-label={t('nav.main')} className={className}>
            {items.map(([href, label]) => (
                <Link
                    key={href}
                    href={path(href)}
                    aria-current={current.startsWith(href) ? 'page' : undefined}
                    className="px-2 py-1 text-sm text-ink-muted hover:text-ink aria-[current=page]:font-medium aria-[current=page]:text-ink aria-[current=page]:underline aria-[current=page]:decoration-2 aria-[current=page]:underline-offset-[6px]"
                >
                    {label}
                </Link>
            ))}
        </nav>
    );
}

export function AppLayout({ actions, children }: { actions?: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="min-h-dvh">
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-ink focus:px-3 focus:py-2 focus:text-sheet">
                {t('nav.skip')}
            </a>
            <header className="border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6">
                    <div className="flex items-center gap-4 sm:gap-6">
                        <Link href={path('/projects')} className="-mx-1 px-1">
                            <Logo />
                        </Link>
                        <MainNav className="hidden gap-1 sm:flex" />
                    </div>
                    <div className="flex items-center gap-1 sm:gap-2">
                        {actions}
                        <LocaleSwitch />
                        <Button variant="ghost" className="max-sm:px-2" asChild>
                            <Link href={path('/tokens')}>MCP</Link>
                        </Button>
                        <Tooltip label={t('nav.logout')}>
                            <Button variant="ghost" size="icon" aria-label={t('nav.logout')} onClick={() => router.post(path('/logout'))}>
                                <LogOut />
                            </Button>
                        </Tooltip>
                    </div>
                </div>
                <MainNav className="flex gap-1 border-t px-2 py-1 sm:hidden" />
            </header>
            <main id="main" className="mx-auto max-w-6xl scroll-mt-4 px-6 pt-10 pb-24">{children}</main>
        </div>
    );
}
