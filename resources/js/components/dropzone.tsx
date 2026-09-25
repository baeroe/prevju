import { router } from '@inertiajs/react';
import { RotateCw, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { itemsFromDrop, itemsFromInput, stripWrapper, uploadFile, type UploadItem } from '@/lib/upload';
import { cn } from '@/lib/utils';

type Job = UploadItem & { id: number; progress: number; status: 'queued' | 'uploading' | 'done' | 'error'; error?: string };

/** Drop files, folders or zips; uploads one file per request, in order, with progress. */
export function Dropzone({ siteId, empty, className }: { siteId: number; empty: boolean; className?: string }) {
    const [over, setOver] = useState(false);
    const [jobs, setJobs] = useState<Job[]>([]);
    const files = useRef<HTMLInputElement>(null);
    const folder = useRef<HTMLInputElement>(null);
    const busy = jobs.some((j) => j.status === 'queued' || j.status === 'uploading');

    const nextId = useRef(0);

    const patch = (id: number, p: Partial<Job>) => setJobs((js) => js.map((j) => (j.id === id ? { ...j, ...p } : j)));

    async function run(list: Job[]) {
        let failed = 0;
        for (const job of list) {
            patch(job.id, { status: 'uploading', progress: 0, error: undefined });
            try {
                await uploadFile(`/sites/${siteId}/files`, job, (progress) => patch(job.id, { progress }));
                patch(job.id, { status: 'done', progress: 1 });
            } catch (e) {
                failed++;
                patch(job.id, { status: 'error', error: (e as Error).message });
            }
        }
        router.reload({ only: ['site'] });
        const ok = list.length - failed;
        if (ok) toast(list.length === 1 ? `${list[0].path} hochgeladen` : `${ok} Dateien hochgeladen`);
        if (!failed) setTimeout(() => setJobs((js) => js.filter((j) => j.status !== 'done')), 1200);
    }

    function start(items: UploadItem[]) {
        if (!items.length) return;
        // a first upload of a single folder ("dist/…") lands at the site root
        const list: Job[] = (empty ? stripWrapper(items) : items).map((i) => ({ ...i, id: nextId.current++, progress: 0, status: 'queued' }));
        setJobs((js) => [...js.filter((j) => j.status !== 'done'), ...list]);
        void run(list);
    }

    return (
        <div className={className}>
            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    setOver(true);
                }}
                onDragLeave={() => setOver(false)}
                onDrop={async (e) => {
                    e.preventDefault();
                    setOver(false);
                    start(await itemsFromDrop(e.dataTransfer));
                }}
                className={cn(
                    'grid place-items-center content-center gap-3 border border-dashed border-ink-muted bg-sheet px-6 text-center',
                    empty ? 'aspect-[16/10]' : 'py-8',
                    over && 'border-solid border-signal bg-signal/10',
                )}
            >
                <Upload className="size-5 text-ink-muted" aria-hidden />
                <p className="max-w-[46ch] text-sm text-balance">
                    {empty ? 'Zieh den Ordner mit deinem Entwurf hierher.' : 'Weitere Dateien hierher ziehen.'}
                    <span className="block text-ink-muted">HTML, CSS, JS, Bilder oder eine ZIP, bis 100 MB pro Datei.</span>
                </p>
                <div className="flex flex-wrap justify-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => files.current?.click()}>
                        Dateien wählen
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => folder.current?.click()}>
                        Ordner wählen
                    </Button>
                </div>
                <input ref={files} type="file" multiple hidden onChange={(e) => (start(itemsFromInput(e.target.files!)), (e.target.value = ''))} />
                <input
                    ref={folder}
                    type="file"
                    hidden
                    {...{ webkitdirectory: '' }}
                    onChange={(e) => (start(itemsFromInput(e.target.files!)), (e.target.value = ''))}
                />
            </div>

            {jobs.length > 0 && (
                <ul className="mt-3 grid gap-1" aria-live="polite" aria-busy={busy}>
                    {jobs.map((job) => (
                        <li key={job.id} className="relative flex items-center gap-3 border bg-sheet px-3 py-2 text-sm">
                            <span
                                className={cn('absolute inset-y-0 left-0 bg-signal/15', job.status === 'error' && 'hidden')}
                                style={{ width: `${job.progress * 100}%` }}
                                aria-hidden
                            />
                            <span className="relative min-w-0 flex-1 truncate font-mono text-xs">{job.path}</span>
                            <span className={cn('relative shrink-0 text-xs tabular-nums', job.status === 'error' ? 'text-danger' : 'text-ink-muted')}>
                                {job.status === 'queued' && 'Wartet'}
                                {job.status === 'uploading' && `${Math.round(job.progress * 100)} %`}
                                {job.status === 'done' && 'Fertig'}
                                {job.status === 'error' && job.error}
                            </span>
                            {job.status === 'error' && (
                                <Button variant="ghost" size="icon-sm" className="relative" aria-label={`${job.path} erneut hochladen`} onClick={() => void run([job])}>
                                    <RotateCw />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
