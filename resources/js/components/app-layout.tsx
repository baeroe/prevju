import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import * as React from 'react';
import { Logo } from '@/components/logo';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';
import { path, t } from '@/lib/i18n';
import { LocaleSwitch } from '@/components/locale-switch';

export function AppLayout({ actions, children }: { actions?: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="min-h-dvh">
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-ink focus:px-3 focus:py-2 focus:text-sheet">
                {t('nav.skip')}
            </a>
            <header className="border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6">
                    <Link href={path('/sites')} className="-mx-1 px-1">
                        <Logo />
                    </Link>
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
            </header>
            <main id="main" className="mx-auto max-w-6xl scroll-mt-4 px-6 pt-10 pb-24">{children}</main>
        </div>
    );
}
