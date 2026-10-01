import { Head, useForm } from '@inertiajs/react';
import { CropFrame } from '@/components/crop-frame';
import { Logo } from '@/components/logo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { path, t } from '@/lib/i18n';
import { LocaleSwitch } from '@/components/locale-switch';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    return (
        <main className="relative grid min-h-dvh place-items-center px-10 py-16">
            <LocaleSwitch className="absolute top-4 right-6" />
            <Head title={t('login.title')} />
            <CropFrame className="w-full max-w-sm border bg-sheet p-8">
                <Logo />
                <h1 className="sr-only">{t('login.title')}</h1>
                <form
                    className="mt-10 grid gap-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(path('/login'), { onFinish: () => form.reset('password') });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('login.email')}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            autoComplete="username"
                            spellCheck={false}
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            aria-invalid={!!form.errors.email}
                            aria-describedby={form.errors.email ? 'login-error' : undefined}
                            autoFocus
                            required
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="password">{t('common.password')}</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            required
                        />
                    </div>
                    {form.errors.email && (
                        <p id="login-error" className="text-sm text-danger" role="alert">
                            {form.errors.email}
                        </p>
                    )}
                    <Button type="submit" loading={form.processing} className="mt-2">
                        {t('login.submit')}
                    </Button>
                </form>
            </CropFrame>
        </main>
    );
}
