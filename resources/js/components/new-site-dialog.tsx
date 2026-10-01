import { useForm } from '@inertiajs/react';
import { FolderPlus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function NewSiteDialog({ trigger, projectId }: { trigger?: React.ReactNode; projectId?: number }) {
    return (
        <CreateDialog
            trigger={
                trigger ?? (
                    <Button>
                        <Plus />
                        <span className="max-sm:sr-only">Neue Site</span>
                    </Button>
                )
            }
            title="Neue Site"
            description="Dateien lädst du im nächsten Schritt hoch."
            url="/sites"
            projectId={projectId}
            placeholder="z. B. Relaunch Bäckerei Kurz…"
            passwordHint="Ohne Passwort sieht jeder mit dem Link die Site."
            submit="Site anlegen"
        />
    );
}

export function NewProjectDialog() {
    return (
        <CreateDialog
            trigger={
                <Button variant="outline">
                    <FolderPlus />
                    <span className="max-sm:sr-only">Neues Projekt</span>
                </Button>
            }
            title="Neues Projekt"
            description="Ein Link für den Kunden, der alle Sites des Projekts zeigt, neueste zuerst."
            url="/projects"
            placeholder="z. B. Bäckerei Kurz…"
            passwordHint="Ein Passwort öffnet alle Sites des Projekts. Ohne Passwort sieht jeder mit dem Link die Liste."
            submit="Projekt anlegen"
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
                        <Label htmlFor="name">Name</Label>
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
                            Passwort <span className="font-normal text-ink-muted">(optional)</span>
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
