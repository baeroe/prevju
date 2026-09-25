import { Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import * as React from 'react';
import { Logo } from '@/components/logo';
import { Button } from '@/components/ui/button';
import { Tooltip } from '@/components/ui/tooltip';

export function AppLayout({ actions, children }: { actions?: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="min-h-dvh">
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-ink focus:px-3 focus:py-2 focus:text-sheet">
                Zum Inhalt springen
            </a>
            <header className="border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-6">
                    <Link href="/sites" className="-mx-1 px-1">
                        <Logo />
                    </Link>
                    <div className="flex items-center gap-2">
                        {actions}
                        <Button variant="ghost" asChild>
                            <Link href="/tokens">MCP</Link>
                        </Button>
                        <Tooltip label="Abmelden">
                            <Button variant="ghost" size="icon" aria-label="Abmelden" onClick={() => router.post('/logout')}>
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
