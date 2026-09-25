import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function NewSiteDialog({ trigger }: { trigger?: React.ReactNode }) {
    const form = useForm({ name: '', password: '' });

    return (
        <Dialog onOpenChange={(open) => !open && form.reset()}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button>
                        <Plus />
                        Neue Site
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <div className="grid gap-1">
                    <DialogTitle>Neue Site</DialogTitle>
                    <DialogDescription>Dateien lädst du im nächsten Schritt hoch.</DialogDescription>
                </div>
                <form
                    className="grid gap-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/sites');
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="z. B. Relaunch Bäckerei Kurz…"
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
                        <p className="text-sm text-ink-muted">Ohne Passwort sieht jeder mit dem Link die Site.</p>
                    </div>
                    <Button type="submit" loading={form.processing} className="justify-self-start">
                        Site anlegen
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
