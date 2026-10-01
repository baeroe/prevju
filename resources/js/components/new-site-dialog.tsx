import { useForm } from '@inertiajs/react';
import { FolderPlus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { path, t } from '@/lib/i18n';

export function NewSiteDialog({ trigger, projectId }: { trigger?: React.ReactNode; projectId?: number }) {
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
            projectId={projectId}
            placeholder={t('site.name_placeholder')}
            passwordHint={t('site.new_password_hint')}
            submit={t('site.create')}
        />
    );
}

export function NewProjectDialog() {
    return (
        <CreateDialog
            trigger={
                <Button variant="outline">
                    <FolderPlus />
                    <span className="max-sm:sr-only">{t('project.new')}</span>
                </Button>
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
    projectId?: number;
    placeholder: string;
    passwordHint: string;
    submit: string;
};

function CreateDialog({ trigger, title, description, url, projectId, placeholder, passwordHint, submit }: CreateDialogProps) {
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
                    <Button type="submit" loading={form.processing} className="justify-self-start">
                        {submit}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
