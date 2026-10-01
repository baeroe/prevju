import { useForm } from '@inertiajs/react';
import { FolderPlus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { path, t } from '@/lib/i18n';
import type { ProjectOption } from '@/types';

/** projectId preselects the project field, e.g. when opened from a project's page. */
export function NewSiteDialog({ trigger, projects = [], projectId }: { trigger?: React.ReactNode; projects?: ProjectOption[]; projectId?: number }) {
    return (
        <CreateDialog
            trigger={
                trigger ?? (
                    <Button>
                        <Plus />
                        <span className="max-sm:sr-only">{t('site.new')}</span>
                    </Button>
                )
            }
            title={t('site.new')}
            description={t('site.new_hint')}
            url={path('/sites')}
            projects={projects}
            projectId={projectId}
            placeholder={t('site.name_placeholder')}
            passwordHint={t('site.new_password_hint')}
            submit={t('site.create')}
        />
    );
}

export function NewProjectDialog({ trigger }: { trigger?: React.ReactNode }) {
    return (
        <CreateDialog
            trigger={
                trigger ?? (
                    <Button variant="outline">
                        <FolderPlus />
                        <span className="max-sm:sr-only">{t('project.new')}</span>
                    </Button>
                )
            }
            title={t('project.new')}
            description={t('project.new_hint')}
            url={path('/projects')}
            placeholder={t('project.name_placeholder')}
            passwordHint={t('project.new_password_hint')}
            submit={t('project.create')}
        />
    );
}

type CreateDialogProps = {
    trigger: React.ReactNode;
    title: string;
    description: string;
    url: string;
    projects?: ProjectOption[];
    projectId?: number;
    placeholder: string;
    passwordHint: string;
    submit: string;
};

function CreateDialog({ trigger, title, description, url, projects = [], projectId, placeholder, passwordHint, submit }: CreateDialogProps) {
    const form = useForm({ name: '', password: '', project_id: projectId ?? null });

    return (
        <Dialog onOpenChange={(open) => !open && form.reset()}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <div className="grid gap-1">
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </div>
                <form
                    className="grid gap-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(url);
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('common.name')}</Label>
                        <Input
                            id="name"
                            name="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder={placeholder}
                            autoComplete="off"
                            aria-invalid={!!form.errors.name}
                            autoFocus
                            required
                        />
                        {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password">
                            {t('common.password')} <span className="font-normal text-ink-muted">{t('common.optional')}</span>
                        </Label>
                        <Input
                            id="new-password"
                            name="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            autoComplete="new-password"
                        />
                        <p className="text-sm text-ink-muted">{passwordHint}</p>
                    </div>
                    {projects.length > 0 && (
                        <div className="grid gap-2">
                            <Label htmlFor="new-project">
                                {t('site.project')} <span className="font-normal text-ink-muted">{t('common.optional')}</span>
                            </Label>
                            <select
                                id="new-project"
                                name="project_id"
                                value={form.data.project_id ?? ''}
                                onChange={(e) => form.setData('project_id', e.target.value ? Number(e.target.value) : null)}
                                className="h-10 w-full min-w-0 border border-hairline bg-sheet px-3 text-base text-ink hover:border-ink-muted focus-visible:border-ink focus-visible:outline-none md:text-sm"
                            >
                                <option value="">{t('site.no_project')}</option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.project_id && <p className="text-sm text-danger">{form.errors.project_id}</p>}
                        </div>
                    )}
                    <Button type="submit" loading={form.processing} className="justify-self-start">
                        {submit}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
