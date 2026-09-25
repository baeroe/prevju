import { FileCode2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { SiteCard } from '@/types';

const VIEWPORT = 1280;

/**
 * Live, non-interactive thumbnail: the real site in an iframe rendered at desktop width and scaled down.
 * ponytail: one iframe per card loads each whole site; fine for dozens, screenshots if it gets to hundreds.
 */
export function SitePreview({ site }: { site: SiteCard }) {
    const box = useRef<HTMLDivElement>(null);
    const [scale, setScale] = useState(0);

    useEffect(() => {
        const el = box.current;
        if (!el) return;
        const ro = new ResizeObserver(([entry]) => setScale(entry.contentRect.width / VIEWPORT));
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    return (
        <div ref={box} className="relative aspect-[16/10] overflow-hidden border bg-sheet">
            {site.has_html ? (
                scale > 0 && (
                    <iframe
                        src={`${site.preview_url}?v=${encodeURIComponent(site.updated_at)}`}
                        title={`Vorschau ${site.name}`}
                        loading="lazy"
                        // no allow-same-origin: draft scripts must not reach the admin page or its session
                        sandbox="allow-scripts"
                        tabIndex={-1}
                        aria-hidden
                        className="pointer-events-none absolute top-0 left-0 origin-top-left border-0 bg-white"
                        style={{ width: VIEWPORT, height: VIEWPORT / 1.6, transform: `scale(${scale})` }}
                    />
                )
            ) : (
                <div className="grid h-full place-items-center content-center gap-2 text-sm text-ink-muted">
                    <FileCode2 className="size-5" aria-hidden />
                    {site.file_count ? 'Keine HTML-Datei' : 'Noch keine Dateien'}
                </div>
            )}
        </div>
    );
}
