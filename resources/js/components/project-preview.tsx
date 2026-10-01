import { useEffect, useState } from 'react';
import { SitePreview } from '@/components/site-preview';
import { cn } from '@/lib/utils';
import type { SiteCard } from '@/types';

const INTERVAL = 4000;

/**
 * Project thumbnail that cycles through its drafts like a slider. Only the current slide and its two
 * neighbours are mounted, so a project with ten versions still loads at most three iframes.
 * Pauses on hover/focus (WCAG 2.2.2) and stands still with reduced motion.
 */
export function ProjectPreview({ sites }: { sites: SiteCard[] }) {
    const slides = sites.filter((s) => s.has_html);
    const count = slides.length;
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);

    useEffect(() => {
        if (count < 2 || paused || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const timer = setInterval(() => setIndex((i) => i + 1), INTERVAL);
        return () => clearInterval(timer);
    }, [count, paused]);

    if (count === 0) {
        return sites.length > 0 ? (
            <SitePreview site={sites[0]} />
        ) : (
            <div className="grid aspect-[16/10] place-items-center border bg-sheet text-sm text-ink-muted">Noch keine Sites</div>
        );
    }

    const current = index % count; // count can shrink after an Inertia reload
    const mounted = new Set([current, (current + 1) % count, (current + count - 1) % count]);

    return (
        <div
            className="relative aspect-[16/10]"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
        >
            {slides.map((site, i) =>
                mounted.has(i) ? (
                    <div
                        key={site.id}
                        className={cn('absolute inset-0 transition-opacity duration-700 motion-reduce:transition-none', i === current ? 'opacity-100' : 'opacity-0')}
                    >
                        <SitePreview site={site} />
                    </div>
                ) : null,
            )}
            {count > 1 && (
                <span className="absolute bottom-2 left-2 max-w-[calc(100%-1rem)] truncate bg-sheet/90 px-2 py-0.5 text-xs text-ink-muted">
                    {slides[current].name}
                </span>
            )}
        </div>
    );
}
