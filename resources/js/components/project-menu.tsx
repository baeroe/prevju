import { Check } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { t } from '@/lib/i18n';
import { moveSite } from '@/lib/move-site';
import { cn } from '@/lib/utils';
import type { ProjectOption, SiteCard } from '@/types';

/** The project label on a site card; a click opens the projects to move it to. The click path next to drag & drop (WCAG 2.5.7). */
export function ProjectMenu({ site, projects }: { site: SiteCard; projects: ProjectOption[] }) {
    const current = projects.find((p) => p.id === site.project_id);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className={cn(
                        'max-w-full cursor-pointer truncate border px-1.5 py-0.5 font-mono text-xs hover:border-ink',
                        current ? 'text-ink' : 'border-dashed text-ink-muted',
                    )}
                >
                    <span className="sr-only">{t('site.project_of', { name: site.name })}: </span>
                    {current ? current.name : t('site.chip_none')}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
                <DropdownMenuLabel>{t('site.project')}</DropdownMenuLabel>
                {projects.map((p) => (
                    <DropdownMenuItem key={p.id} onSelect={() => moveSite(site, p.id, projects)}>
                        {p.name}
                        {p.id === site.project_id && <Check className="size-4" aria-hidden />}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuItem onSelect={() => moveSite(site, null, projects)} className="text-ink-muted">
                    {t('site.no_project')}
                    {!site.project_id && <Check className="size-4" aria-hidden />}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
