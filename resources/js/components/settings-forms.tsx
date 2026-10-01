import { router, useForm } from '@inertiajs/react';
import { Lock, LockOpen } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';

export function NameForm({ endpoint, id, name }: { endpoint: string; id: string; name: string }) {
    const form = useForm({ name });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(endpoint, { preserveScroll: true, onSuccess: () => toast(t('settings.name_saved')) });
            }}
        >
            <Label htmlFor={`${id}-name`} className="font-medium">
                {t('common.name')}
            </Label>
            <div className="flex gap-2">
                <Input id={`${id}-name`} name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoComplete="off" required />
                <Button type="submit" variant="outline" loading={form.processing}>
                    {t('common.save')}
                </Button>
            </div>
            {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
        </form>
    );
}

type PasswordFormProps = { endpoint: string; id: string; url: string; hasPassword: boolean; openHint: string; lockedHint: string };

export function PasswordForm({ endpoint, id, url, hasPassword, openHint, lockedHint }: PasswordFormProps) {
    const form = useForm({ password: '' });

    return (
        <form
            className="grid gap-3 border-t pt-8"
            onSubmit={(e) => {
                e.preventDefault();
                form.patch(endpoint, {
                    preserveScroll: true,
                    onSuccess: () => (form.reset(), toast(hasPassword ? t('settings.password_changed') : t('settings.password_set'))),
                });
            }}
        >
            {/* lets password managers attribute the password to this link */}
            <input type="text" autoComplete="username" value={url} readOnly hidden />
            <Label htmlFor={`${id}-password`} className="font-medium">
                {t('common.password')}
            </Label>
            <p className="flex items-center gap-2 text-sm text-ink-muted">
                {hasPassword ? <Lock className="size-4" aria-hidden /> : <LockOpen className="size-4" aria-hidden />}
                {hasPassword ? lockedHint : openHint}
            </p>
            <div className="flex gap-2">
                <Input
                    id={`${id}-password`}
                    name="password"
                    required
                    type="password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    placeholder={hasPassword ? t('settings.new_password') : t('settings.set_password')}
                    autoComplete="new-password"
                />
                <Button type="submit" variant="outline" loading={form.processing}>
                    {hasPassword ? t('settings.change') : t('settings.set')}
                </Button>
            </div>
            {hasPassword && (
                <button
                    type="button"
                    className="-my-2 cursor-pointer justify-self-start py-2 text-sm text-ink-muted underline underline-offset-4 hover:text-danger"
                    onClick={() => router.patch(endpoint, { clear_password: true }, { preserveScroll: true, onSuccess: () => toast(t('settings.password_removed')) })}
                >
                    {t('settings.remove_password')}
                </button>
            )}
        </form>
    );
}
