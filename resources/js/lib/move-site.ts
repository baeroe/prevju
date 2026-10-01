import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { path, t } from '@/lib/i18n';
import type { ProjectOption, SiteCard } from '@/types';

/** Put a site into a project (or none), with an undo in the toast. Used by drag & drop, the project menu and the remove button. */
export function moveSite(site: SiteCard, projectId: number | null, projects: ProjectOption[]) {
    if (site.project_id === projectId) return;
    const before = site.project_id;
    const send = (id: number | null, onSuccess?: () => void) =>
        router.patch(path(`/sites/${site.id}`), { project_id: id }, { preserveScroll: true, preserveState: true, onSuccess });

    send(projectId, () =>
        toast(
            projectId
                ? t('site.moved', { name: site.name, project: projects.find((p) => p.id === projectId)?.name ?? '' })
                : t('site.removed', { name: site.name }),
            { action: { label: t('common.undo'), onClick: () => send(before) } },
        ),
    );
}

/** dataTransfer type for dragging a site card onto a project. */
export const SITE_DRAG_TYPE = 'application/x-prevju-site';
