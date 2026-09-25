import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { AppLayout } from '@/components/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip } from '@/components/ui/tooltip';
import { timeAgo } from '@/lib/format';

type Token = { id: number; name: string; created_at: string; last_used_at: string | null };

export default function Tokens({ mcp_url, tokens, new_token }: { mcp_url: string; tokens: Token[]; new_token: string | null }) {
    const form = useForm({ name: '' });
    const [client, setClient] = useState<Client>('claude');
    const [scope, setScope] = useState<Scope>('user');
    const setup = setupFor(client, scope, mcp_url, new_token ?? '<token>');

    return (
        <AppLayout>
            <Head title="MCP" />
            <h1 className="text-3xl leading-none font-semibold tracking-tight">MCP</h1>
            <p className="mt-4 max-w-[62ch] text-ink-muted">
                Mit einem Token kann Claude Sites anlegen, Entwürfe hochladen und verwalten. Jedes Token hat dieselben Rechte wie dein Login. Leg pro
                Gerät eins an, dann kannst du es einzeln widerrufen.
            </p>

            <div className="mt-12 grid gap-14 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <section className="grid content-start gap-8">
                    {new_token && (
                        <div className="grid gap-3 border border-ink bg-sheet p-5" role="status">
                            <h2 className="font-medium">Neues Token, nur jetzt sichtbar</h2>
                            <CopyField value={new_token} label="Token kopieren" />
                        </div>
                    )}

                    <div className="grid gap-3">
                        <h2 className="font-medium">Einrichten</h2>
                        <div className="flex flex-wrap gap-x-6 gap-y-3">
                            <Segmented
                                label="Client"
                                value={client}
                                onChange={setClient}
                                options={[
                                    ['claude', 'Claude Code'],
                                    ['codex', 'Codex'],
                                ]}
                            />
                            <Segmented
                                label="Gültig für"
                                value={scope}
                                onChange={setScope}
                                options={[
                                    ['user', 'User'],
                                    ['local', 'Lokal'],
                                ]}
                            />
                        </div>
                        <CopyField value={setup.command} label="Befehl kopieren" />
                        <p className="text-sm text-ink-muted">{setup.hint}</p>
                        <p className="text-sm text-ink-muted">
                            Endpoint <span className="font-mono text-xs text-ink">{mcp_url}</span>, Authentifizierung per Bearer-Token.
                        </p>
                    </div>
                </section>

                <section className="grid content-start gap-8">
                    <form
                        className="grid gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post('/tokens', { preserveScroll: true, onSuccess: () => form.reset() });
                        }}
                    >
                        <Label htmlFor="token-name" className="font-medium">
                            Neues Token
                        </Label>
                        <div className="flex gap-2">
                            <Input
                                id="token-name"
                                name="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="z. B. MacBook…"
                                autoComplete="off"
                                required
                            />
                            <Button type="submit" variant="outline" loading={form.processing}>
                                Anlegen
                            </Button>
                        </div>
                        {form.errors.name && <p className="text-sm text-danger">{form.errors.name}</p>}
                    </form>

                    <div className="grid gap-3">
                        <h2 className="flex items-baseline justify-between font-medium">
                            Aktive Tokens
                            <span className="font-mono text-xs font-normal text-ink-muted">{tokens.length}</span>
                        </h2>
                        {tokens.length === 0 ? (
                            <p className="text-sm text-ink-muted">Noch keine. Ohne Token nimmt der MCP-Endpoint keine Anfragen an.</p>
                        ) : (
                            <ul className="border-t">
                                {tokens.map((t) => (
                                    <li key={t.id} className="flex items-center gap-3 border-b py-2">
                                        <KeyRound className="size-4 shrink-0 text-ink-muted" aria-hidden />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">{t.name}</p>
                                            <p className="text-xs text-ink-muted">
                                                {t.last_used_at ? `zuletzt benutzt ${timeAgo(t.last_used_at)}` : 'noch nie benutzt'}
                                            </p>
                                        </div>
                                        <Tooltip label="Widerrufen">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                aria-label={`Token ${t.name} widerrufen`}
                                                className="text-ink-muted hover:text-danger"
                                                onClick={() =>
                                                    router.delete(`/tokens/${t.id}`, {
                                                        preserveScroll: true,
                                                        onSuccess: () => toast(`Token „${t.name}“ widerrufen`),
                                                    })
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </Tooltip>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

type Client = 'claude' | 'codex';
type Scope = 'user' | 'local';

function setupFor(client: Client, scope: Scope, url: string, token: string): { command: string; hint: string } {
    if (client === 'claude') {
        return {
            command: `claude mcp add --transport http --scope ${scope} prevju ${url} --header "Authorization: Bearer ${token}"`,
            hint:
                scope === 'user'
                    ? 'In allen Projekten verfügbar. Gespeichert in ~/.claude.json.'
                    : 'Nur im Projekt, in dem du den Befehl ausführst. Gespeichert in ~/.claude.json, nicht im Repo.',
        };
    }

    // codex mcp add only writes the user config and takes tokens only via env var, so append the block directly
    const file = scope === 'user' ? '~/.codex/config.toml' : '.codex/config.toml';
    const block = `[mcp_servers.prevju]\nurl = "${url}"\nhttp_headers = { "Authorization" = "Bearer ${token}" }`;

    return {
        command: `${scope === 'local' ? 'mkdir -p .codex && ' : ''}printf '\\n${block.replaceAll('\n', '\\n')}\\n' >> ${file}`,
        hint:
            scope === 'user'
                ? 'In allen Projekten verfügbar. Hängt den Eintrag an ~/.codex/config.toml an.'
                : 'Nur in diesem Projekt, und nur wenn Codex es als vertrauenswürdig kennt. Das Token steht dann in .codex/config.toml: die Datei in .gitignore aufnehmen.',
    };
}

function Segmented<T extends string>({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: T;
    onChange: (v: T) => void;
    options: [T, string][];
}) {
    return (
        <div role="radiogroup" aria-label={label} className="flex items-center gap-3">
            <span className="text-sm text-ink-muted">{label}</span>
            <div className="flex border">
                {options.map(([v, text]) => (
                    <button
                        key={v}
                        type="button"
                        role="radio"
                        aria-checked={value === v}
                        onClick={() => onChange(v)}
                        className="h-8 cursor-pointer px-3 text-sm aria-checked:bg-ink aria-checked:text-sheet not-aria-checked:hover:bg-ink/6"
                    >
                        {text}
                    </button>
                ))}
            </div>
        </div>
    );
}

function CopyField({ value, label }: { value: string; label: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <div className="flex items-stretch gap-2">
            <code className="min-w-0 flex-1 border bg-sheet px-3 py-2.5 font-mono text-xs break-all">{value}</code>
            <Button
                variant="outline"
                className="h-auto"
                onClick={async () => {
                    await navigator.clipboard.writeText(value);
                    setCopied(true);
                    toast('Kopiert');
                    setTimeout(() => setCopied(false), 1500);
                }}
            >
                {copied ? 'Kopiert' : label}
            </Button>
        </div>
    );
}
