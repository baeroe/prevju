import { Head, useForm } from '@inertiajs/react';
import { CropFrame } from '@/components/crop-frame';
import { Logo } from '@/components/logo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    return (
        <main className="grid min-h-dvh place-items-center px-10 py-16">
            <Head title="Anmelden" />
            <CropFrame className="w-full max-w-sm border bg-sheet p-8">
                <Logo />
                <h1 className="sr-only">Anmelden</h1>
                <form
                    className="mt-10 grid gap-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/login', { onFinish: () => form.reset('password') });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="email">E-Mail</Label>
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
                        <Label htmlFor="password">Passwort</Label>
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
                        Anmelden
                    </Button>
                </form>
            </CropFrame>
        </main>
    );
}
